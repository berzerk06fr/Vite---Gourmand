import { Component, OnInit, signal, inject, computed } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { CommonModule } from '@angular/common';
import { FormsModule, NgForm } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { AuthService } from '../../services/auth.service';

export interface ContactFormModel {
  email: string;
  titre: string;
  description: string;
}

@Component({
  selector: 'app-contact',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './contact.html',
  styleUrl: './contact.css'
})
export class ContactComponent implements OnInit {
  private readonly http = inject(HttpClient);
  private readonly authService = inject(AuthService);
  private readonly apiUrl = 'https://vite-et-gourmand-api.alwaysdata.net/api/contact';

  readonly currentUser = toSignal(this.authService.currentUser$);
  readonly isAdmin = computed(() => Boolean(this.currentUser()?.roles?.includes('ROLE_ADMIN')));

  // Réglage destinataire (Admin)
  readonly emailReception = signal<string>('contact@vite-et-gourmand.fr');
  readonly isEditingEmail = signal<boolean>(false);
  readonly tempEmailReception = signal<string>('');
  readonly isSavingConfig = signal<boolean>(false);
  readonly configMessage = signal<string | null>(null);
  readonly configError = signal<string | null>(null);

  // Formulaire de contact public
  formData: ContactFormModel = {
    email: '',
    titre: '',
    description: ''
  };

  readonly isSubmitting = signal<boolean>(false);
  readonly isSuccess = signal<boolean>(false);
  readonly submitError = signal<string | null>(null);

  ngOnInit(): void {
    this.chargerDestinataire();
  }

  chargerDestinataire(): void {
    this.http.get<{ email: string }>(`${this.apiUrl}/destinataire`).subscribe({
      next: (res) => this.emailReception.set(res.email),
      error: () => { /* Garde la valeur par défaut si indisponible */ }
    });
  }

  ouvrirEditionEmail(): void {
    this.tempEmailReception.set(this.emailReception());
    this.isEditingEmail.set(true);
    this.configMessage.set(null);
    this.configError.set(null);
  }

  sauvegarderEmailReception(): void {
    const email = this.tempEmailReception().trim();
    if (!email?.includes('@')) {
      this.configError.set('Veuillez saisir une adresse email valide.');
      return;
    }

    this.isSavingConfig.set(true);
    this.configError.set(null);

    this.http.put<{ message: string; email: string }>(`${this.apiUrl}/destinataire`, { email }).subscribe({
      next: (res) => {
        this.isSavingConfig.set(false);
        this.emailReception.set(res.email);
        this.isEditingEmail.set(false);
        this.configMessage.set(res.message);
        setTimeout(() => this.configMessage.set(null), 4000);
      },
      error: (err) => {
        this.isSavingConfig.set(false);
        this.configError.set(err.error?.error || 'Erreur lors de la mise à jour.');
      }
    });
  }

  onSubmit(form: NgForm): void {
    if (form.invalid) return;

    this.isSubmitting.set(true);
    this.submitError.set(null);

    this.http.post<{ message: string }>(this.apiUrl, this.formData).subscribe({
      next: () => {
        this.isSubmitting.set(false);
        this.isSuccess.set(true);
        form.resetForm();
      },
      error: (err) => {
        this.isSubmitting.set(false);
        this.submitError.set(err.error?.error || 'Impossible d\'envoyer le message pour le moment.');
      }
    });
  }
}
