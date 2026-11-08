import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';

interface SuggestionMenu {
  id: number;
  badge: string;
  badgeClass: string;
  titre: string;
  descriptionPlats: string;
  image: string;
}

interface Temoignage {
  id: number;
  note: number;
  titre: string;
  texte: string;
}

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './home.html',
  styleUrl: './home.css'
})
export class HomeComponent {
  readonly suggestions: SuggestionMenu[] = [
    {
      id: 1,
      badge: 'Végan',
      badgeClass: 'badge-vegan',
      titre: 'Menu du Terroir & Saveurs.',
      descriptionPlats: '• Velouté maraîcher aux éclats de châtaignes, mitonnée de saison et tartelette croustillante. •',
      image: 'https://images.unsplash.com/photo-1540420773420-3366772f4999?auto=format&fit=crop&w=700&q=80'
    },
    {
      id: 2,
      badge: 'Végétarien',
      badgeClass: 'badge-vege',
      titre: 'Menu Prestige Océan.',
      descriptionPlats: '• Noix de Saint-Jacques snackées, émulsion d\'agrumes, risotto crémeux et croustillant de sarrazin. •',
      image: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=700&q=80'
    },
    {
      id: 3,
      badge: 'Carnivore',
      badgeClass: 'badge-carni',
      titre: 'Buffet Champêtre & Gourmand.',
      descriptionPlats: '• Magret de canard du Sud-Ouest, mousseline de panais et jus réduit au romarin. •',
      image: 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=700&q=80'
    }
  ];

  readonly temoignages: Temoignage[] = [
    {
      id: 1,
      note: 5,
      titre: 'Une fusion parfaite !',
      texte: 'La cuisine de ce traiteur est une vraie déferlante d\'énergie. Les saveurs m\'ont fait dresser les cheveux sur la tête (dans le bon sens) et l\'équipe qui encadrait cela téléportait l\'élégance !'
    },
    {
      id: 2,
      note: 5,
      titre: 'Une expérience transcendante.',
      texte: 'Les plats atteignent un niveau au-delà de toutes les limites. Les accords sont si parfaits que les ingrédients ne font plus qu\'un seul être parfait. Une table digne d\'un dieu de la destruction !'
    },
    {
      id: 3,
      note: 5,
      titre: 'En deux mots.',
      texte: 'Juste parfait.'
    }
  ];
}