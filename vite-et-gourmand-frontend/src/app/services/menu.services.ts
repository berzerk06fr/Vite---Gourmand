import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, catchError, of } from 'rxjs';
import { Menu } from '../models/menu.model';

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
    images: ['https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800&q=80'],
    plats: [
      {
        id: 101,
        nom: 'Velouté de potimarron et châtaignes torréfiées',
        type: 'entree',
        description: 'Émulsion légère au lait d’amande et graines de courge croquantes.'
      },
      {
        id: 102,
        nom: 'Mitonnée de légumes racines et tofu fumé façon grand-mère',
        type: 'plat',
        description: 'Carottes pourpres, panais rôtis et jus réduit aux herbes sauvages.'
      },
      {
        id: 103,
        nom: 'Tartelette croustillante aux poires et noisettes',
        type: 'dessert',
        description: 'Pâte sablée végétale maison et coulis de chocolat noir 70%.'
      }
    ]
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
    images: ['https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=800&q=80'],
    plats: [
      {
        id: 201,
        nom: 'Tartare d’algues fraîches et carpaccio de fenouil',
        type: 'entree',
        description: 'Vinaigrette au citron vert et yuzu perlé.'
      },
      {
        id: 202,
        nom: 'Risotto crémeux aux champignons nobles et sarrasin grillé',
        type: 'plat',
        description: 'Riz carnaroli lié aux échalotes confites et copeaux de truffe d’été.'
      },
      {
        id: 203,
        nom: 'Dôme vanille de Madagascar et cœur coulant mangue',
        type: 'dessert',
        description: 'Biscuit dacquoise aux amandes et tuile dentelle.'
      }
    ]
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
    images: ['https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=800&q=80'],
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
    images: ['https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&w=800&q=80'],
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
    images: ['https://images.unsplash.com/photo-1540420773420-3366772f4999?auto=format&fit=crop&w=800&q=80'],
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
    images: ['https://images.unsplash.com/photo-1579871494447-9811cf80d66c?auto=format&fit=crop&w=800&q=80'],
    plats: []
  }
];

@Injectable({
  providedIn: 'root'
})

export class MenuService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = 'http://localhost:8080/api/menus'; /*URL port API*/

  getMenus(): Observable<Menu[]> {
    return this.http.get<Menu[]>(this.apiUrl).pipe(
      catchError(() => of(MOCK_MENUS))
    );
  }
  getMenuById(id: number): Observable<Menu | null> {
    return this.http.get<Menu>(`${this.apiUrl}/${id}`).pipe(
      catchError(() => {
        const found = MOCK_MENUS.find(m => m.id === id) || null;
        return of(found);
      })
    );
  }
}