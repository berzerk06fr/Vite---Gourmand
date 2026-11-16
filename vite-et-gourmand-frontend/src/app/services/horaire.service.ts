import { Injectable, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';

export interface HoraireItem {
  jour: string;
  heures: string;
  highlight?: boolean;
}

export interface FooterData {
  description: string;
  reseaux: {
    twitter: string;
    facebook: string;
    instagram: string;
  };
  contact: {
    telephone: string;
    email: string;
    adresse: string;
    livraison: string;
  };
  horaires: HoraireItem[];
}

@Injectable({
  providedIn: 'root'
})
export class HoraireService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = 'https://vite-et-gourmand-api.alwaysdata.net/api/footer';

  footerData = signal<FooterData>({
    description: 'Traiteur gastronomique de saison, créateur de moments conviviaux et de réceptions sur-mesure.',
    reseaux: {
      twitter: '#',
      facebook: '#',
      instagram: '#'
    },
    contact: {
      telephone: '04 93 XX XX XX',
      email: 'contact@vite-et-gourmand.fr',
      adresse: 'Atelier & Cuisines : Bordeaux',
      livraison: 'Livraison Bordeaux & Gironde'
    },
    horaires: [
      { jour: 'Lun. – Jeu.', heures: '08h00 – 19h00', highlight: false },
      { jour: 'Ven. – Sam.', heures: '08h00 – 20h00', highlight: false },
      { jour: 'Dimanche', heures: '09h00 – 14h00', highlight: true }
    ]
  });

  chargerFooter(): void {
    this.http.get<FooterData>(this.apiUrl).subscribe({
      next: (data) => {
        if (data) {
          this.footerData.set(data);
        }
      },
      error: (err) => console.error('Erreur chargement footer', err)
    });
  }

  sauvegarderFooter(nouvellesDonnees: FooterData): Observable<FooterData> {
    return this.http.put<FooterData>(this.apiUrl, nouvellesDonnees).pipe(
      tap((data) => this.footerData.set(data))
    );
  }
}
