import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { CommandeService, CommandeItem } from '../../services/commande.service';
import { AvisService } from '../../services/avis.service';
import { AuthService } from '../../services/auth.service';
import { Avis } from '../../models/avis.model';

@Component({
  selector: 'app-admin-commandes',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './admin-commandes.html',
  styleUrl: './admin-commandes.css'
})
export class AdminCommandesComponent implements OnInit {
  private readonly commandeService = inject(CommandeService);
  private readonly avisService = inject(AvisService);
  private readonly authService = inject(AuthService);

  // Conversion de l'Observable currentUser$ en Signal
  readonly currentUser = toSignal(this.authService.currentUser$);

  // Rôle Admin calculé dynamiquement
  readonly isAdmin = computed(() => {
    const user = this.currentUser();
    return Boolean(user?.roles?.includes('ROLE_ADMIN'));
  });

  // Navigation par onglets
  readonly ongletActif = signal<'commandes' | 'avis'>('commandes');

  // Signaux Commandes
  readonly commandes = signal<CommandeItem[]>([]);
  readonly isLoading = signal<boolean>(true);
  readonly errorMessage = signal<string | null>(null);
  readonly successMessage = signal<string | null>(null);
  readonly rechercheTexte = signal<string>('');
  readonly filtreStatut = signal<string>('tous');
  readonly commandeSelectionnee = signal<CommandeItem | null>(null);

  // Signaux Modération Avis
  readonly avisEnAttente = signal<Avis[]>([]);
  readonly isLoadingAvis = signal<boolean>(false);
  readonly actionEnCoursId = signal<number | null>(null);

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
    this.chargerAvisEnAttente();
  }

  // --- Gestion des onglets ---
  changerOnglet(onglet: 'commandes' | 'avis'): void {
    this.ongletActif.set(onglet);
    if (onglet === 'avis') {
      this.chargerAvisEnAttente();
    }
  }

  // --- Flux Commandes ---
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

  // --- Flux Modération Avis (US-13) ---
  chargerAvisEnAttente(): void {
    this.isLoadingAvis.set(true);
    this.avisService.getAvisEnAttente().subscribe({
      next: (data) => {
        this.avisEnAttente.set(data);
        this.isLoadingAvis.set(false);
      },
      error: () => {
        this.isLoadingAvis.set(false);
      }
    });
  }

  traiterAvis(avisId: number, action: 'valider' | 'refuser'): void {
    this.actionEnCoursId.set(avisId);

    this.avisService.modererAvis(avisId, action).subscribe({
      next: (res) => {
        this.successMessage.set(res.message);
        this.avisEnAttente.update((liste) => liste.filter((a) => a.id !== avisId));
        this.actionEnCoursId.set(null);
        setTimeout(() => this.successMessage.set(null), 4000);
      },
      error: (err) => {
        this.errorMessage.set(err.error?.error || 'Erreur lors de la modération de l\'avis.');
        this.actionEnCoursId.set(null);
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

    if (s.startsWith('annul')) {
      return 'vg-pill-annule';
    }

    if (['accepté', 'accepte', 'livré', 'livre', 'terminée', 'terminee'].includes(s)) {
      return 'vg-pill-livre';
    }

    if (s.includes('préparation') || s.includes('preparation') || s.includes('livraison')) {
      return 'vg-pill-prepa';
    }

    return 'vg-pill-attente';
  }
}
