import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { CommandeService, CommandeItem } from '../../services/commande.service';

@Component({
  selector: 'app-mes-commandes',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './mes-commandes.html',
  styleUrl: './mes-commandes.css'
})
export class MesCommandesComponent implements OnInit {
  private readonly commandeService = inject(CommandeService);

  readonly commandes = signal<CommandeItem[]>([]);
  readonly isLoading = signal<boolean>(true);
  readonly errorMessage = signal<string | null>(null);

  ngOnInit(): void {
    this.chargerCommandes();
  }

  chargerCommandes(): void {
    this.isLoading.set(true);
    this.errorMessage.set(null);

    this.commandeService.getMyCommandes().subscribe({
      next: (data) => {
        this.commandes.set(data);
        this.isLoading.set(false);
      },
      error: (err) => {
        this.errorMessage.set(err.error?.error || 'Impossible de récupérer vos commandes.');
        this.isLoading.set(false);
      }
    });
  }

  formaterLibelleStatut(statut: string): string {
    const s = (statut || '').toLowerCase().trim();
    switch (s) {
      case 'en_attente':
        return 'En attente de validation';
      case 'accepté':
      case 'accepte':
        return 'Commande validée';
      case 'en préparation':
      case 'en preparation':
        return 'En cours de préparation';
      case 'en cours de livraison':
        return 'En cours de livraison';
      case 'livré':
      case 'livre':
        return 'Commande livrée';
      case 'en attente du retour de matériel':
        return 'Retour matériel attendu';
      case 'terminée':
      case 'terminee':
        return 'Commande clôturée';
      default:
        if (s.startsWith('annul')) {
          return 'Commande annulée';
        }
        return statut;
    }
  }

  getStatusClass(statut: string): string {
    const s = (statut || '').toLowerCase().trim();

    if (s.startsWith('annul')) {
      return 'bg-danger-subtle text-danger border-danger';
    }

    if (['accepté', 'accepte', 'livré', 'livre', 'terminée', 'terminee'].includes(s)) {
      return 'bg-success-subtle text-success border-success';
    }

    if (s.includes('préparation') || s.includes('preparation') || s.includes('livraison')) {
      return 'bg-info-subtle text-info border-info';
    }

    return 'bg-warning-subtle text-warning border-warning';
  }
}