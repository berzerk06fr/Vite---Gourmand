import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { CommandeService, CommandeItem } from '../../services/commande.service';

@Component({
  selector: 'app-admin-commandes',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './admin-commandes.html',
  styleUrl: './admin-commandes.css'
})
export class AdminCommandesComponent implements OnInit {
  private readonly commandeService = inject(CommandeService);

  readonly commandes = signal<CommandeItem[]>([]);
  readonly isLoading = signal<boolean>(true);
  readonly errorMessage = signal<string | null>(null);
  readonly successMessage = signal<string | null>(null);

  // Signaux pour recherche et filtrage
  readonly rechercheTexte = signal<string>('');
  readonly filtreStatut = signal<string>('tous');

  // Commande sélectionnée pour le modal de détails (Chantier 2)
  readonly commandeSelectionnee = signal<CommandeItem | null>(null);

  readonly statutsPossibles = [
    'en_attente',
    'accepté',
    'en préparation',
    'en cours de livraison',
    'livré',
    'en attente du retour de matériel',
    'terminée',
    'annulée'
  ];

  // Liste filtrée dynamiquement via computed
  readonly commandesFiltrees = computed(() => {
    const texte = this.rechercheTexte().toLowerCase().trim();
    const statut = this.filtreStatut();

    return this.commandes().filter((cmd) => {
      const matchRecherche =
        !texte ||
        cmd.numeroCommande.toLowerCase().includes(texte) ||
        (cmd.client?.email?.toLowerCase().includes(texte) ?? false) ||
        (cmd.menu?.titre?.toLowerCase().includes(texte) ?? false);

      const matchStatut = statut === 'tous' || cmd.statut === statut;

      return matchRecherche && matchStatut;
    });
  });

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
        this.errorMessage.set(err.error?.error || 'Erreur lors de la récupération des commandes.');
        this.isLoading.set(false);
      }
    });
  }

  ouvrirDetails(cmd: CommandeItem): void {
    this.commandeSelectionnee.set(cmd);
  }

  fermerDetails(): void {
    this.commandeSelectionnee.set(null);
  }

  imprimerBonCommande(): void {
    window.print();
  }

  onChangementStatut(cmd: CommandeItem, event: Event): void {
    const selectElement = event.target as HTMLSelectElement;
    const nouveauStatut = selectElement.value;

    if (nouveauStatut === cmd.statut) return;

    if (nouveauStatut === 'annulée') {
      const motif = prompt('Motif de l\'annulation :');
      if (!motif) {
        selectElement.value = cmd.statut;
        return;
      }

      const contact = prompt('Mode de contact avec le client (gsm ou mail) :')?.toLowerCase();
      if (contact !== 'gsm' && contact !== 'mail') {
        alert('Mode de contact invalide : saisissez "gsm" ou "mail".');
        selectElement.value = cmd.statut;
        return;
      }

      this.appliquerStatut(cmd.id, selectElement, {
        statut: nouveauStatut,
        motifAnnulation: motif,
        modeContact: contact
      });
      return;
    }

    this.appliquerStatut(cmd.id, selectElement, { statut: nouveauStatut });
  }

  private appliquerStatut(
    id: number,
    selectElement: HTMLSelectElement,
    payload: { statut: string; motifAnnulation?: string; modeContact?: 'gsm' | 'mail' }
  ): void {
    this.commandeService.updateStatut(id, payload).subscribe({
      next: (res) => {
        this.successMessage.set(res.message);
        this.commandes.update((liste) =>
          liste.map((c) => (c.id === id ? { ...c, statut: payload.statut } : c))
        );
        setTimeout(() => this.successMessage.set(null), 4000);
      },
      error: (err) => {
        this.errorMessage.set(err.error?.error || 'Erreur lors de la modification du statut.');
        selectElement.value = this.commandes().find((c) => c.id === id)?.statut || 'en_attente';
        setTimeout(() => this.errorMessage.set(null), 4000);
      }
    });
  }

  formaterLibelleStatut(statut: string): string {
    switch (statut) {
      case 'en_attente': return 'En attente';
      case 'accepté': return 'Accepté';
      case 'en préparation': return 'En préparation';
      case 'en cours de livraison': return 'En cours de livraison';
      case 'livré': return 'Livré';
      case 'en attente du retour de matériel': return 'Retour matériel attendu';
      case 'terminée': return 'Terminée';
      case 'annulée': return 'Annulée';
      default: return statut;
    }
  }

  getStatusClass(statut: string): string {
    const s = (statut || '').toLowerCase().trim();
    if (s.startsWith('annul')) return 'bg-danger-subtle text-danger border-danger';
    if (['accepté', 'accepte', 'livré', 'livre', 'terminée', 'terminee'].includes(s)) {
      return 'bg-success-subtle text-success border-success';
    }
    if (s.includes('préparation') || s.includes('preparation') || s.includes('livraison')) {
      return 'bg-info-subtle text-info border-info';
    }
    return 'bg-warning-subtle text-warning border-warning';
  }
}