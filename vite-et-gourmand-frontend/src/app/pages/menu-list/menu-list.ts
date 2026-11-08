import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { catchError, of } from 'rxjs';
import { Menu } from '../../models/menu.model';


const MOCK_MENUS: Menu[] = [
  {
    id: 1,
    titre: 'Menu du Terroir & Saveurs',
    description: 'Velouté maraîcher aux éclats de châtaignes, mitonnée de saison et tartelette croustillante.',
    theme: 'Classique',
    regime: 'Vegan',
    nombrePersonneMin: 10,
    prixParPersonne: 48,
    stockDisponible: 12,
    conditions: 'Commande au minimum 7 jours avant la date de livraison.',
    images: ['https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&q=80'],
    plats: []
  },
  {
    id: 2,
    titre: 'Menu Prestige Océan',
    description: "Noix de Saint-Jacques snackées, émulsion d'agrumes, risotto crémeux et croustillant de sarrasin.",
    theme: 'Évènement',
    regime: 'Végétarien',
    nombrePersonneMin: 8,
    prixParPersonne: 62,
    stockDisponible: 5,
    conditions: 'Transport réfrigéré assuré. Maintien au frais obligatoire.',
    images: ['https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=600&q=80'],
    plats: []
  },
  {
    id: 3,
    titre: 'Buffet Champêtre & Gourmand',
    description: 'Magret de canard du Sud-Ouest, mousseline de panais et jus réduit au romarin.',
    theme: 'Classique',
    regime: 'Carnivore',
    nombrePersonneMin: 15,
    prixParPersonne: 39,
    stockDisponible: 20,
    conditions: 'Prêt de vaisselle inclus sous réserve de restitution sous 10 jours.',
    images: ['https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=600&q=80'],
    plats: []
  },
  {
    id: 4,
    titre: 'Saveurs Marines & Encre de Seiche',
    description: "Linguines à l'encre de seiche, poulpe snacké aux herbes fraîches et émulsion citronnée.",
    theme: 'Évènement',
    regime: 'Poisson',
    nombrePersonneMin: 10,
    prixParPersonne: 42,
    stockDisponible: 10,
    conditions: 'À déguster chaud dès réception.',
    images: ['https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&w=600&q=80'],
    plats: []
  },
  {
    id: 5,
    titre: 'Déclinaison Végétale & Fraîcheur',
    description: 'Fines lamelles de légumes croquants, agrumes rôtis et vinaigrette acidulée aux herbes du jardin.',
    theme: 'Classique',
    regime: 'Végétarien',
    nombrePersonneMin: 10,
    prixParPersonne: 34,
    stockDisponible: 15,
    conditions: '100% biologique et local.',
    images: ['https://images.unsplash.com/photo-1540420773420-3366772f4999?auto=format&fit=crop&w=600&q=80'],
    plats: []
  },
  {
    id: 6,
    titre: 'Plateau Sushi & Créations Fusion',
    description: 'Assortiment de sushis et makis roulés, saumon braisé, avocat et perles de sésame.',
    theme: 'Évènement',
    regime: 'Poisson',
    nombrePersonneMin: 12,
    prixParPersonne: 55,
    stockDisponible: 15,
    conditions: 'À consommer dans les 24h suivant la livraison.',
    images: ['https://images.unsplash.com/photo-1579871494447-9811cf80d66c?auto=format&fit=crop&w=600&q=80'],
    plats: []
  }
];

@Component({
  selector: 'app-menu-list',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './menu-list.html',
  styleUrl: './menu-list.css'
})
export class MenuList implements OnInit {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = 'http://localhost:8000/api/menus';

  
  readonly menus = signal<Menu[]>(MOCK_MENUS);
  readonly isLoading = signal<boolean>(false);
  readonly apiError = signal<string | null>(null);

  
  readonly selectedRegime = signal<string>('tous');
  readonly selectedTheme = signal<string>('tous');
  readonly maxPrice = signal<number | null>(null);
  readonly minPersons = signal<number | null>(null);

  
  readonly filteredMenus = computed(() => {
    const regime = this.selectedRegime().toLowerCase();
    const theme = this.selectedTheme().toLowerCase();
    const maxP = this.maxPrice();
    const minP = this.minPersons();

    return this.menus().filter((menu) => {
      if (regime !== 'tous' && menu.regime.toLowerCase() !== regime) {
        return false;
      }
      if (theme !== 'tous' && menu.theme.toLowerCase() !== theme) {
        return false;
      }
      if (maxP !== null && menu.prixParPersonne > maxP) {
        return false;
      }
      if (minP !== null && menu.nombrePersonneMin > minP) {
        return false;
      }
      return true;
    });
  });

  ngOnInit(): void {
    this.fetchMenus();
  }

  fetchMenus(): void {
    this.isLoading.set(true);
    this.http.get<Menu[]>(this.apiUrl).pipe(
      catchError(() => {
        
        this.apiError.set('Mode démonstration : données locales');
        return of(MOCK_MENUS);
      })
    ).subscribe((data) => {
      this.menus.set(data);
      this.isLoading.set(false);
    });
  }

  resetFilters(): void {
    this.selectedRegime.set('tous');
    this.selectedTheme.set('tous');
    this.maxPrice.set(null);
    this.minPersons.set(null);
  }
}