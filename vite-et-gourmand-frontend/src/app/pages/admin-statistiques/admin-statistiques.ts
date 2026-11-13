import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { RouterLink } from '@angular/router';

interface Indicateurs {
  caTotal: number;
  nbCommandes: number;
  totalConvives: number;
}

interface VenteMenu {
  _id: string;
  chiffreAffaires: number;
  totalCommandes: number;
  totalConvives: number;
}

interface StatistiquesResponse {
  indicateurs: Indicateurs;
  ventesParMenu: VenteMenu[];
}

@Component({
  selector: 'app-admin-statistiques',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './admin-statistiques.html',
  styleUrls: ['./admin-statistiques.css']
})
export class AdminStatistiquesComponent implements OnInit {
  private readonly http = inject(HttpClient);

  stats = signal<StatistiquesResponse | null>(null);
  loading = signal<boolean>(true);
  errorMsg = signal<string | null>(null);

  ngOnInit(): void {
    this.chargerStatistiques();
  }

  chargerStatistiques(): void {
    this.loading.set(true);
    this.errorMsg.set(null);

    this.http.get<StatistiquesResponse>('https://vite-et-gourmand-api.alwaysdata.net/api/admin/statistiques').subscribe({
      next: (data) => {
        this.stats.set(data);
        this.loading.set(false);
      },
      error: (err) => {
        console.error('Erreur lors du chargement des statistiques', err);
        this.errorMsg.set('Impossible de récupérer les statistiques analytiques MongoDB.');
        this.loading.set(false);
      }
    });
  }
}
