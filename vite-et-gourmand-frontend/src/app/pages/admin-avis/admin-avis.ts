import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { AvisService } from '../../services/avis.service';
import { Avis } from '../../models/avis.model';

@Component({
  selector: 'app-admin-avis',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './admin-avis.html',
  styleUrls: ['./admin-avis.css']
})
export class AdminAvisComponent implements OnInit {
  private readonly avisService = inject(AvisService);

  readonly avisList = signal<Avis[]>([]);
  readonly isLoading = signal<boolean>(false);
  readonly errorMessage = signal<string | null>(null);
  readonly successMessage = signal<string | null>(null);

  ngOnInit(): void {
    this.chargerAvisEnAttente();
  }

  chargerAvisEnAttente(): void {
    this.isLoading.set(true);
    this.errorMessage.set(null);

    this.avisService.getAvisEnAttente().subscribe({
      next: (data) => {
        this.avisList.set(data);
        this.isLoading.set(false);
      },
      error: () => {
        this.errorMessage.set('Impossible de récupérer les avis en attente.');
        this.isLoading.set(false);
      }
    });
  }

  traiterAvis(id: number, action: 'valider' | 'refuser'): void {
    this.avisService.modererAvis(id, action).subscribe({
      next: (res) => {
        this.successMessage.set(res.message);
        // On retire immédiatement l'avis traité de la vue locale
        this.avisList.update(liste => liste.filter(item => item.id !== id));
        setTimeout(() => this.successMessage.set(null), 3500);
      },
      error: () => {
        this.errorMessage.set('Une erreur est survenue lors de la modération.');
        setTimeout(() => this.errorMessage.set(null), 3500);
      }
    });
  }
}
