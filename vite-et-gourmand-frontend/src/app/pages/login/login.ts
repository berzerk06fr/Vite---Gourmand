import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './login.html',
  
})
export class LoginComponent {
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);
  private readonly http = inject(HttpClient);
  private readonly apiUrl = 'https://vite-et-gourmand-api.alwaysdata.net/api';

  email = '';
  password = '';
  errorMessage = signal<string>('');
  loading = signal<boolean>(false);

  // Gestion Mot de passe oublié & Réinitialisation
  isForgotMode = signal<boolean>(false);
  isResetFormVisible = signal<boolean>(false);
  resetEmail = '';
  newPassword = '';
  confirmPassword = '';
  resetSuccess = signal<string>('');
  resetError = signal<string>('');
  devResetToken = signal<string>('');

  onSubmit(): void {
    if (!this.email || !this.password) {
      this.errorMessage.set('Veuillez remplir tous les champs.');
      return;
    }

    this.loading.set(true);
    this.errorMessage.set('');

    this.authService.login({ email: this.email, password: this.password }).subscribe({
      next: () => {
        this.loading.set(false);
        void this.router.navigate(['/menus']);
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMessage.set(err.error?.message || err.message || 'Identifiants invalides.');
      },
    });
  }

  toggleForgotMode(): void {
    this.isForgotMode.update((v) => !v);
    this.isResetFormVisible.set(false);
    this.errorMessage.set('');
    this.resetError.set('');
    this.resetSuccess.set('');
    this.devResetToken.set('');
    this.newPassword = '';
    this.confirmPassword = '';
    this.resetEmail = this.email;
  }

  sendResetLink(): void {
    const emailClean = this.resetEmail.trim();
    if (!emailClean?.includes('@')) {
      this.resetError.set('Veuillez renseigner une adresse email valide.');
      return;
    }

    this.loading.set(true);
    this.resetError.set('');
    this.resetSuccess.set('');
    this.devResetToken.set('');

    this.http.post<{ message: string; devResetUrl?: string }>(`${this.apiUrl}/forgot-password`, { email: emailClean }).subscribe({
      next: (res) => {
        this.loading.set(false);
        this.resetSuccess.set(res.message);
        const url = res.devResetUrl || '';
        this.devResetToken.set(url.split('token=')[1] ?? '');
      },
      error: (err) => {
        this.loading.set(false);
        this.resetError.set(err.error?.error || 'Impossible de générer le lien de réinitialisation.');
      }
    });
  }

  openNewPasswordForm(): void {
    this.isResetFormVisible.set(true);
    this.resetError.set('');
    this.resetSuccess.set('');
  }

  submitNewPassword(): void {
    if (this.newPassword !== this.confirmPassword) {
      this.resetError.set('Les deux mots de passe ne correspondent pas.');
      return;
    }

    this.loading.set(true);
    this.resetError.set('');
    this.resetSuccess.set('');

    this.http.post<{ message: string }>(`${this.apiUrl}/reset-password`, {
      token: this.devResetToken(),
      password: this.newPassword
    }).subscribe({
      next: (res) => {
        this.loading.set(false);
        this.resetSuccess.set(res.message);
        setTimeout(() => {
          this.isForgotMode.set(false);
          this.isResetFormVisible.set(false);
          this.email = this.resetEmail;
          this.password = '';
        }, 2000);
      },
      error: (err) => {
        this.loading.set(false);
        this.resetError.set(err.error?.error || 'Lien invalide ou mot de passe trop faible.');
      }
    });
  }
}
