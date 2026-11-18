import { Component, OnInit, effect, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { FooterData, HoraireService } from '../../services/horaire.service';

@Component({
  selector: 'app-gestion-footer',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './gestion-footer.html'
})
export class GestionFooterComponent implements OnInit {
  private readonly horaireService = inject(HoraireService);

  formData: FooterData = structuredClone(this.horaireService.footerData());
  isSaving = signal(false);
  messageSucces = signal<string | null>(null);
  messageErreur = signal<string | null>(null);

  constructor() {
    effect(() => {
      this.formData = structuredClone(this.horaireService.footerData());
    });
  }

  ngOnInit(): void {
    this.horaireService.chargerFooter();
  }

  ajouterHoraire(): void {
    this.formData.horaires.push({
      jour: '',
      heures: '',
      highlight: false
    });
  }

  supprimerHoraire(index: number): void {
    this.formData.horaires.splice(index, 1);
  }

  enregistrer(): void {
    this.isSaving.set(true);
    this.messageSucces.set(null);
    this.messageErreur.set(null);

    this.horaireService.sauvegarderFooter(this.formData).subscribe({
      next: () => {
        this.isSaving.set(false);
        this.messageSucces.set('Les informations du bas de page ont bien été mises à jour.');
      },
      error: (err) => {
        this.isSaving.set(false);
        this.messageErreur.set('Une erreur est survenue lors de l’enregistrement. Vérifiez vos droits.');
      }
    });
  }
}
