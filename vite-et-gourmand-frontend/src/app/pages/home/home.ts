import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { AvisService } from '../../services/avis.service';
import { Avis } from '../../models/avis.model';

interface SuggestionMenu {
  id: number;
  badge: string;
  badgeClass: string;
  titre: string;
  descriptionPlats: string;
  image: string;
}

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './home.html',
  styleUrl: './home.css'
})
export class HomeComponent implements OnInit {
  private readonly avisService = inject(AvisService);

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

  // Témoignages par défaut si la base est encore vide
  readonly temoignagesParDefaut = [
    {
      id: 1,
      note: 5,
      commentaire: 'La cuisine de ce traiteur est une vraie déferlante de saveurs. Une prestation et une équipe d\'une élégance rare !',
      auteur: { pseudo: 'Sophie L.' },
      menuTitre: 'Prestation d\'entreprise'
    },
    {
      id: 2,
      note: 5,
      commentaire: 'Les plats atteignent un niveau au-delà de nos attentes. Les cuissons et accords étaient simplement parfaits.',
      auteur: { pseudo: 'Marc D.' },
      menuTitre: 'Buffet Champêtre & Gourmand'
    },
    {
      id: 3,
      note: 5,
      commentaire: 'Ponctualité, fraîcheur des produits et présentation soignée. Juste parfait pour notre mariage.',
      auteur: { pseudo: 'Camille & Julien' },
      menuTitre: 'Menu Prestige Océan'
    }
  ];

  readonly avisValides = signal<Avis[]>([]);
  readonly isLoadingAvis = signal<boolean>(true);

  ngOnInit(): void {
    this.chargerAvisValides();
  }

  chargerAvisValides(): void {
    this.avisService.getAvisValides().subscribe({
      next: (data) => {
        // Prend au maximum les 4 avis les plus récents
        this.avisValides.set(data.slice(0, 4));
        this.isLoadingAvis.set(false);
      },
      error: () => {
        this.isLoadingAvis.set(false);
      }
    });
  }
}