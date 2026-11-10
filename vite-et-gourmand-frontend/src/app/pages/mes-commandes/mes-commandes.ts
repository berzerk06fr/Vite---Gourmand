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

  getStatusClass(statut: string): string {
    switch (statut) {
      case 'accepté':
      case 'terminée':
        return 'bg-emerald-100 text-emerald-800 border-emerald-300';
      case 'en préparation':
      case 'en cours de livraison':
        return 'bg-blue-100 text-blue-800 border-blue-300';
      case 'annulée':
        return 'bg-red-100 text-red-800 border-red-300';
      default:
        return 'bg-amber-100 text-amber-800 border-amber-300';
    }
  }
}