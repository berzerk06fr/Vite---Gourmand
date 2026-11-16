import { Component, OnInit, OnDestroy, ElementRef, ViewChild, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient, HttpParams } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

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
  ventesComparatives?: VenteMenu[];
}

@Component({
  selector: 'app-admin-statistiques',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './admin-statistiques.html',
  styleUrls: ['./admin-statistiques.css']
})
export class AdminStatistiquesComponent implements OnInit, OnDestroy {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = 'https://vite-et-gourmand-api.alwaysdata.net/api/admin/statistiques';

  private chartCanvas?: ElementRef<HTMLCanvasElement>;
  private chartInstance: Chart | null = null;

  @ViewChild('commandesChart')
  set commandesChartRef(ref: ElementRef<HTMLCanvasElement> | undefined) {
    this.chartCanvas = ref;
    if (ref && this.stats()) {
      this.renderChart();
    }
  }

  stats = signal<StatistiquesResponse | null>(null);
  menusDisponibles = signal<string[]>([]);
  loading = signal<boolean>(true);
  errorMsg = signal<string | null>(null);

  // Filtres pour le chiffre d'affaires et la période
  filtreMenu = signal<string>('');
  filtreDateDebut = signal<string>('');
  filtreDateFin = signal<string>('');

  ngOnInit(): void {
    this.chargerStatistiques();
  }

  ngOnDestroy(): void {
    this.detruireGraphique();
  }

  chargerStatistiques(): void {
    this.loading.set(true);
    this.errorMsg.set(null);

    let params = new HttpParams();
    if (this.filtreMenu()) {
      params = params.set('menu', this.filtreMenu());
    }
    if (this.filtreDateDebut()) {
      params = params.set('dateDebut', this.filtreDateDebut());
    }
    if (this.filtreDateFin()) {
      params = params.set('dateFin', this.filtreDateFin());
    }

    this.http.get<StatistiquesResponse>(this.apiUrl, { params }).subscribe({
      next: (data) => {
        this.stats.set(data);

        // Alimente la liste déroulante des menus si elle est encore vide
        const sourceMenus = data.ventesComparatives ?? data.ventesParMenu;
        if (this.menusDisponibles().length === 0 && sourceMenus.length > 0) {
          this.menusDisponibles.set(sourceMenus.map((m) => m._id));
        }

        this.loading.set(false);
        this.renderChart();
      },
      error: (err) => {
        console.error('Erreur lors du chargement des statistiques', err);
        this.errorMsg.set('Impossible de récupérer les statistiques analytiques MongoDB.');
        this.loading.set(false);
      }
    });
  }

  reinitialiserFiltres(): void {
    this.filtreMenu.set('');
    this.filtreDateDebut.set('');
    this.filtreDateFin.set('');
    this.chargerStatistiques();
  }

  private renderChart(): void {
    const data = this.stats();
    const canvas = this.chartCanvas?.nativeElement;
    if (!data || !canvas) {
      return;
    }

    this.detruireGraphique();

    const donneesGraphique = data.ventesComparatives ?? data.ventesParMenu;
    const labels = donneesGraphique.map((item) => item._id);
    const volumes = donneesGraphique.map((item) => item.totalCommandes);
    const menuActif = this.filtreMenu();

    // Met en valeur la barre du menu filtré si un menu est sélectionné
    const couleursBarres = labels.map((label) =>
      !menuActif || label === menuActif ? 'rgba(139, 30, 63, 0.85)' : 'rgba(139, 30, 63, 0.25)'
    );

    this.chartInstance = new Chart(canvas, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          {
            label: 'Nombre de commandes',
            data: volumes,
            backgroundColor: couleursBarres,
            borderColor: 'rgba(139, 30, 63, 1)',
            borderWidth: 1,
            borderRadius: 6,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              afterLabel: (ctx) => {
                const vente = donneesGraphique[ctx.dataIndex];
                return vente ? `CA : ${vente.chiffreAffaires.toFixed(2)} € (${vente.totalConvives} convives)` : '';
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { stepSize: 1 }
          }
        }
      }
    });
  }

  private detruireGraphique(): void {
    if (this.chartInstance) {
      this.chartInstance.destroy();
      this.chartInstance = null;
    }
  }
}
