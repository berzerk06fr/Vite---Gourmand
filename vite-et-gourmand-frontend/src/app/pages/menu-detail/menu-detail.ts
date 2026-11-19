import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterModule, Router } from '@angular/router';
import { Menu, Plat } from '../../models/menu.model';
import { MenuService } from '../../services/menu.services';
import { AuthService } from '../../services/auth.service';

export interface GallerySlide {
  url: string;
  label: string;
  caption: string;
  badgeType?: string;
  allergenes?: string[];
  isMenuMain: boolean;
}

@Component({
  selector: 'app-menu-detail',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './menu-detail.html',
  styleUrl: './menu-detail.css'
})
export class MenuDetail implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly menuService = inject(MenuService);
  private readonly authService = inject(AuthService);

  readonly menu = signal<Menu | null>(null);
  readonly isLoading = signal<boolean>(true);
  readonly errorMessage = signal<string | null>(null);
  readonly showAuthModal = signal<boolean>(false);

  readonly guestCount = signal<number>(1);
  readonly selectedImageIndex = signal<number>(0);

  // Galerie : associe chaque photo à son titre et sa description (menu ou plat)
  readonly gallerySlides = computed<GallerySlide[]>(() => {
    const currentMenu = this.menu();
    if (!currentMenu) return [];

    const slides: GallerySlide[] = [];
    const seenUrls = new Set<string>();

    // 1. Photo principale du menu
    const singleImg = (currentMenu as unknown as { image?: string }).image
      || (Array.isArray(currentMenu.images) ? currentMenu.images[0] : undefined);

    if (singleImg) {
      seenUrls.add(singleImg);
      slides.push({
        url: singleImg,
        label: currentMenu.titre,
        caption: currentMenu.description,
        isMenuMain: true
      });
    }

    // 2. Photos personnalisées des plats qui composent ce menu
    if (Array.isArray(currentMenu.plats)) {
      for (const plat of currentMenu.plats) {
        const platImg = (plat as unknown as { image?: string }).image;
        if (platImg && !seenUrls.has(platImg)) {
          seenUrls.add(platImg);
          slides.push({
            url: platImg,
            label: plat.nom,
            caption: plat.description?.trim() || `${plat.type} inclus dans le ${currentMenu.titre}.`,
            badgeType: plat.type,
            allergenes: this.extractPlatAllergenes(plat),
            isMenuMain: false
          });
        }
      }
    }

    return slides.slice(0, 5);
  });

  readonly activeSlide = computed<GallerySlide | null>(() => {
    const slides = this.gallerySlides();
    if (slides.length === 0) return null;
    const idx = this.selectedImageIndex();
    return slides[idx] ?? slides[0];
  });

  readonly activeImage = computed<string | null>(() => this.activeSlide()?.url ?? null);

  // Miniatures latérales cliquables (uniquement s'il y a plusieurs photos réelles)
  readonly sideThumbnails = computed<{ url: string; label: string; index: number }[]>(() => {
    const slides = this.gallerySlides();
    if (slides.length <= 1) return [];
    return slides.slice(0, 4).map((slide, index) => ({
      url: slide.url,
      label: slide.label,
      index
    }));
  });

  // Liste unique des allergènes présents sur l'ensemble du menu
  readonly menuAllergenes = computed<string[]>(() => {
    const currentMenu = this.menu();
    if (!currentMenu?.plats) return [];

    const set = new Set<string>();
    for (const plat of currentMenu.plats) {
      for (const a of this.extractPlatAllergenes(plat)) {
        set.add(a);
      }
    }
    return Array.from(set);
  });

  readonly totalPrice = computed(() => {
    const currentMenu = this.menu();
    if (!currentMenu) return 0;
    return currentMenu.prixParPersonne * this.guestCount();
  });

  readonly isGuestCountValid = computed(() => {
    const currentMenu = this.menu();
    if (!currentMenu) return false;
    return this.guestCount() >= currentMenu.nombrePersonneMin;
  });

  // US-12 : Signal calculé pour détecter une rupture sur le menu ou ses plats
  readonly isStockEpuise = computed(() => {
    const currentMenu = this.menu();
    if (!currentMenu) return false;

    const stockFormule = currentMenu.quantiteStock ?? currentMenu.stockDisponible;
    if (stockFormule !== undefined && stockFormule <= 0) {
      return true;
    }

    return currentMenu.plats?.some(plat => plat.isEnRupture || (plat.stock !== undefined && plat.stock <= 0)) ?? false;
  });

  ngOnInit(): void {
    const menuId = Number(this.route.snapshot.paramMap.get('id'));
    this.loadMenu(menuId);
  }

  loadMenu(id: number): void {
    this.isLoading.set(true);
    this.selectedImageIndex.set(0);

    this.menuService.getMenuById(id).subscribe({
      next: (data) => {
        if (data) {
          this.menu.set(data);
          this.guestCount.set(data.nombrePersonneMin);
        } else {
          this.errorMessage.set('Menu introuvable ou indisponible.');
        }
        this.isLoading.set(false);
      },
      error: () => {
        this.errorMessage.set('Erreur lors de la récupération du menu.');
        this.isLoading.set(false);
      }
    });
  }

  selectImage(index: number): void {
    this.selectedImageIndex.set(index);
  }

  extractPlatAllergenes(plat: Plat): string[] {
    if (Array.isArray(plat.allergenes) && plat.allergenes.length > 0) {
      return plat.allergenes.map(a => a.trim()).filter(Boolean);
    }
    const raw = (plat as unknown as { allergenesRaw?: string }).allergenesRaw;
    if (typeof raw === 'string' && raw.trim().length > 0) {
      return raw.split(',').map(a => a.trim()).filter(Boolean);
    }
    return [];
  }

  goToOrder(): void {
    const currentMenu = this.menu();
    if (!currentMenu || !this.isGuestCountValid() || this.isStockEpuise()) return;

    if (!this.authService.isAuthenticated()) {
      this.showAuthModal.set(true);
      return;
    }

    void this.router.navigate(['/commande'], {
      queryParams: {
        menuId: currentMenu.id,
        guests: this.guestCount()
      }
    });
  }

  closeAuthModal(): void {
    this.showAuthModal.set(false);
  }
}
