import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterModule, Router } from '@angular/router'; // Router ajouté
import { Menu } from '../../models/menu.model';
import { MenuService } from '../../services/menu.services';

@Component({
  selector: 'app-menu-detail',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './menu-detail.html',
  styleUrl: './menu-detail.css'
})
export class MenuDetail implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router); // Injecté pour la navigation
  private readonly menuService = inject(MenuService);

  readonly menu = signal<Menu | null>(null);
  readonly isLoading = signal<boolean>(true);
  readonly errorMessage = signal<string | null>(null);

  readonly guestCount = signal<number>(1);

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

  ngOnInit(): void {
    const menuId = Number(this.route.snapshot.paramMap.get('id'));
    this.loadMenu(menuId);
  }

  loadMenu(id: number): void {
    this.isLoading.set(true);

    this.menuService.getMenuById(id).subscribe((data) => {
      if (data) {
        this.menu.set(data);
        this.guestCount.set(data.nombrePersonneMin);
      } else {
        this.errorMessage.set('Menu introuvable ou indisponible.');
      }
      this.isLoading.set(false);
    });
  }

goToOrder(): void {
    const currentMenu = this.menu();
    if (!currentMenu || !this.isGuestCountValid()) return;

    void this.router.navigate(['/commande'], {
      queryParams: {
        menuId: currentMenu.id,
        guests: this.guestCount()
      }
    });
  }
}