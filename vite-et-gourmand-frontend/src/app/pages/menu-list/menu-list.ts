import { Component, computed, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { Menu } from '../../models/menu.model';

@Component({
  selector: 'app-menu-list',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './menu-list.html',
  styleUrl: './menu-list.css'
})
export class MenuList {
  // Données de démonstration conformes à la maquette et au sujet ECF
  readonly menus = signal<Menu[]>([
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
      description: 'Noix de Saint-Jacques snackées, émulsion d\'agrumes, risotto crémeux et croustillant de sarrasin.',
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
      description: 'Linguines à l\'encre de seiche, poulpe snacké aux herbes fraîches et émulsion citronnée.',
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
  ]);

  // Signaux pour les filtres
  readonly selectedRegime = signal<string>('tous');
  readonly selectedTheme = signal<string>('tous');
  readonly maxPrice = signal<number | null>(null);
  readonly minPersons = signal<number | null>(null);

  // Filtrage dynamique sans rechargement de page (computed signal)
  readonly filteredMenus = computed(() => {
    return this.menus().filter(menu => {
      if (this.selectedRegime() !== 'tous' && menu.regime.toLowerCase() !== this.selectedRegime().toLowerCase()) {
        return false;
      }
      if (this.selectedTheme() !== 'tous' && menu.theme.toLowerCase() !== this.selectedTheme().toLowerCase()) {
        return false;
      }
      if (this.maxPrice() !== null && menu.prixParPersonne > this.maxPrice()!) {
        return false;
      }
      if (this.minPersons() !== null && menu.nombrePersonneMin > this.minPersons()!) {
        return false;
      }
      return true;
    });
  });

  resetFilters(): void {
    this.selectedRegime.set('tous');
    this.selectedTheme.set('tous');
    this.maxPrice.set(null);
    this.minPersons.set(null);
  }
}