import { Component, signal, inject, computed } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { CommonModule } from '@angular/common';
import { FormsModule, NgForm } from '@angular/forms';
import { RouterModule } from '@angular/router';
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
export class ContactComponent {
  private readonly authService = inject(AuthService);
  private readonly STORAGE_KEY = 'vg_contact_destinataire';

  readonly currentUser = toSignal(this.authService.currentUser$);
  readonly isAdmin = computed(() => Boolean(this.currentUser()?.roles?.includes('ROLE_ADMIN')));

  readonly emailReception = signal<string>(
    localStorage.getItem(this.STORAGE_KEY) || 'contact@vite-et-gourmand.fr'
  );
  readonly isEditingEmail = signal<boolean>(false);
  readonly tempEmailReception = signal<string>('');
  readonly configSaved = signal<boolean>(false);

  formData: ContactFormModel = {
    email: '',
    titre: '',
    description: ''
  };

  readonly isSubmitting = signal<boolean>(false);
  readonly isSuccess = signal<boolean>(false);

  ouvrirEditionEmail(): void {
    this.tempEmailReception.set(this.emailReception());
    this.isEditingEmail.set(true);
    this.configSaved.set(false);
  }

  sauvegarderEmailReception(): void {
    const email = this.tempEmailReception().trim();
    if (!email || !email.includes('@')) return;

    localStorage.setItem(this.STORAGE_KEY, email);
    this.emailReception.set(email);
    this.isEditingEmail.set(false);
    this.configSaved.set(true);
    setTimeout(() => this.configSaved.set(false), 3000);
  }

  onSubmit(form: NgForm): void {
    if (form.invalid) return;

    this.isSubmitting.set(true);

    setTimeout(() => {
      this.isSubmitting.set(false);
      this.isSuccess.set(true);
      form.resetForm();
    }, 600);
  }
}