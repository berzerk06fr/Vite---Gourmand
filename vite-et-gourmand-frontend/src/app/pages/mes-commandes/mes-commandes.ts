import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { CommandeService, CommandeItem } from '../../services/commande.service';
import { AvisService } from '../../services/avis.service';

@Component({
  selector: 'app-mes-commandes',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './mes-commandes.html',
  styleUrl: './mes-commandes.css'
})
export class MesCommandesComponent implements OnInit {
  private readonly commandeService = inject(CommandeService);
  private readonly avisService = inject(AvisService);

  readonly commandes = signal<CommandeItem[]>([]);
  readonly isLoading = signal<boolean>(true);
  readonly errorMessage = signal<string | null>(null);

  // Signaux pour la modale de dépôt d'avis
  readonly selectedCommandeForReview = signal<CommandeItem | null>(null);
  readonly reviewNote = signal<number>(5);
  readonly reviewCommentaire = signal<string>('');
  readonly isSubmittingReview = signal<boolean>(false);
  readonly reviewFeedback = signal<{ message: string; isError: boolean } | null>(null);

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

  // Vérifie si la commande est éligible pour laisser un avis (livrée ou terminée)
  canReview(statut: string): boolean {
    const s = (statut || '').toLowerCase().trim();
    return ['livré', 'livre', 'terminée', 'terminee'].includes(s);
  }

  ouvrirModaleAvis(commande: CommandeItem): void {
    this.selectedCommandeForReview.set(commande);
    this.reviewNote.set(5);
    this.reviewCommentaire.set('');
    this.reviewFeedback.set(null);
  }

  fermerModaleAvis(): void {
    this.selectedCommandeForReview.set(null);
    this.reviewFeedback.set(null);
  }

  envoyerAvis(): void {
    const commande = this.selectedCommandeForReview();
    const commentaire = this.reviewCommentaire().trim();
    const note = this.reviewNote();

    if (!commande) return;

    if (!commentaire || commentaire.length < 5) {
      this.reviewFeedback.set({ message: 'Veuillez saisir au moins 5 caractères.', isError: true });
      return;
    }

    this.isSubmittingReview.set(true);
    this.reviewFeedback.set(null);

    this.avisService.creerAvis({
      commandeId: commande.id,
      note,
      commentaire
    }).subscribe({
      next: (res) => {
        this.isSubmittingReview.set(false);
        this.reviewFeedback.set({ message: res.message, isError: false });
        setTimeout(() => {
          this.fermerModaleAvis();
        }, 2200);
      },
      error: (err) => {
        this.isSubmittingReview.set(false);
        this.reviewFeedback.set({
          message: err.error?.error || 'Une erreur est survenue lors de la soumission de votre avis.',
          isError: true
        });
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
      return 'bg-rose-50 text-rose-700 border-rose-200';
    }

    if (['livré', 'livre', 'terminée', 'terminee'].includes(s)) {
      return 'bg-emerald-50 text-emerald-800 border-emerald-200';
    }

    if (['accepté', 'accepte'].includes(s)) {
      return 'bg-blue-50 text-blue-700 border-blue-200';
    }

    if (s.includes('préparation') || s.includes('preparation') || s.includes('livraison')) {
      return 'bg-amber-50 text-amber-800 border-amber-200';
    }

    return 'bg-stone-100 text-stone-700 border-stone-200';
  }
}
