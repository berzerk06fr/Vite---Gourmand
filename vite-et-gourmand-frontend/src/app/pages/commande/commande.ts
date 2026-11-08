import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, NgForm } from '@angular/forms';
import { ActivatedRoute, RouterModule } from '@angular/router';
import { Menu } from '../../models/menu.model';
import { MenuService } from '../../services/menu.services';

@Component({
  selector: 'app-commande',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './commande.html',
  styleUrl: './commande.css'
})
export class CommandeComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly menuService = inject(MenuService);

  readonly allMenus = signal<Menu[]>([]);
  readonly selectedMenu = signal<Menu | null>(null);

  nom = signal<string>('Votre nom');
  prenom = signal<string>('Votre Prénom');
  email = signal<string>('VotreEmail@exemple.fr');
  gsm = signal<string>('0612345678');
  adresse = signal<string>('12 Avenue des Etoiles');
  ville = signal<string>('Bordeaux');
  isHorsBordeaux = signal<boolean>(false);
  distanceKm = signal<number>(0);
  datePrestation = signal<string>('');
  heurePrestation = signal<string>('12:00');

  guestCount = signal<number>(10);
  orderSubmitted = signal<boolean>(false);

  readonly isGuestsValid = computed(() => {
    const m = this.selectedMenu();
    return m ? this.guestCount() >= m.nombrePersonneMin : false;
  });

  readonly hasDiscount = computed(() => {
    const m = this.selectedMenu();
    return m ? this.guestCount() >= (m.nombrePersonneMin + 5) : false;
  });

  readonly baseMenuTotal = computed(() => {
    const m = this.selectedMenu();
    if (!m) return 0;
    return m.prixParPersonne * this.guestCount();
  });

  readonly discountAmount = computed(() => {
    if (!this.hasDiscount()) return 0;
    return +(this.baseMenuTotal() * 0.10).toFixed(2);
  });

  readonly menuTotalAfterDiscount = computed(() => {
    return +(this.baseMenuTotal() - this.discountAmount()).toFixed(2);
  });

  readonly deliveryCost = computed(() => {
    if (!this.isHorsBordeaux()) return 0;
    const km = Math.max(0, this.distanceKm());
    return +(5 + (km * 0.59)).toFixed(2);
  });

  readonly grandTotal = computed(() => {
    return +(this.menuTotalAfterDiscount() + this.deliveryCost()).toFixed(2);
  });

  ngOnInit(): void {
    this.menuService.getMenus().subscribe((menus) => {
      this.allMenus.set(menus);

      const menuIdParam = this.route.snapshot.queryParamMap.get('menuId');
      const guestsParam = this.route.snapshot.queryParamMap.get('guests');

      if (menuIdParam) {
        const found = menus.find((m) => m.id === Number(menuIdParam));
        if (found) {
          this.selectedMenu.set(found);
          const initialGuests = guestsParam
            ? Math.max(Number(guestsParam), found.nombrePersonneMin)
            : found.nombrePersonneMin;
          this.guestCount.set(initialGuests);
        }
      } else if (menus.length > 0) {
        this.selectedMenu.set(menus[0]);
        this.guestCount.set(menus[0].nombrePersonneMin);
      }
    });
  }

  onMenuChange(menuId: number): void {
    const m = this.allMenus().find((item) => item.id === Number(menuId)) || null;
    this.selectedMenu.set(m);
    if (m && this.guestCount() < m.nombrePersonneMin) {
      this.guestCount.set(m.nombrePersonneMin);
    }
  }

  submitOrder(form: NgForm): void {
    if (form.invalid || !this.isGuestsValid()) return;
    this.orderSubmitted.set(true);
  }
}