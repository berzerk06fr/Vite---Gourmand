import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, NgForm } from '@angular/forms';
import { ActivatedRoute, RouterModule, Router } from '@angular/router';
import { Menu } from '../../models/menu.model';
import { MenuService } from '../../services/menu.services';
import { CommandeService, CommandeResponse } from '../../services/commande.service';

@Component({
  selector: 'app-commande',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './commande.html',
  styleUrl: './commande.css'
})
export class CommandeComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly menuService = inject(MenuService);
  private readonly commandeService = inject(CommandeService);

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
  
  // États de soumission
  isSubmitting = signal<boolean>(false);
  orderSubmitted = signal<boolean>(false);
  errorMessage = signal<string | null>(null);
  orderResult = signal<CommandeResponse | null>(null);

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
    const menu = this.selectedMenu();
    if (form.invalid || !this.isGuestsValid() || !menu) return;

    this.isSubmitting.set(true);
    this.errorMessage.set(null);

    const fullDateDelivery = this.datePrestation() 
      ? `${this.datePrestation()} ${this.heurePrestation() || '12:00'}:00` 
      : undefined;

    this.commandeService.createCommande({
      menuId: menu.id,
      nbPersonnes: this.guestCount(),
      ville: this.isHorsBordeaux() ? this.ville() : 'Bordeaux',
      distanceKm: this.isHorsBordeaux() ? this.distanceKm() : 0,
      datePrevueLivraison: fullDateDelivery,
      nom: this.nom(),
      prenom: this.prenom(),
      email: this.email(),
      gsm: this.gsm(),
      adresse: this.adresse()
    }).subscribe({
      next: (res) => {
        this.isSubmitting.set(false);
        this.orderResult.set(res);
        this.orderSubmitted.set(true);
        // Redirection directe vers l'espace client avec la liste des commandes
        void this.router.navigate(['/mes-commandes']);
      },
      error: (err) => {
        this.isSubmitting.set(false);
        if (err.status === 401) {
          this.errorMessage.set('Veuillez vous connecter pour valider votre commande.');
          void this.router.navigate(['/login']);
        } else {
          this.errorMessage.set(err.error?.error || 'Une erreur est survenue lors de la commande.');
        }
      }
    });
  }
}