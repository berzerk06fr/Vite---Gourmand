import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, NgForm } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './login.html',
  styleUrl: './login.css'
})
export class LoginComponent {
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  email = '';
  password = '';

  readonly isLoading = signal<boolean>(false);
  readonly errorMessage = signal<string | null>(null);
  readonly isForgotMode = signal<boolean>(false);
  readonly forgotSuccess = signal<boolean>(false);

  onSubmit(form: NgForm): void {
    if (form.invalid || this.isLoading()) return;

    this.isLoading.set(true);
    this.errorMessage.set(null);

    this.authService.login({ email: this.email, password: this.password }).subscribe({
      next: () => {
        this.isLoading.set(false);
        void this.router.navigate(['/menus']);
      },
      error: (err) => {
        this.isLoading.set(false);
        if (err.status === 401) {
          this.errorMessage.set('Identifiants invalides. Vérifiez votre email et mot de passe.');
        } else {
          this.errorMessage.set('Une erreur est survenue lors de la connexion. Veuillez réessayer.');
        }
      }
    });
  }

  sendResetLink(): void {
    if (!this.email) return;
    this.forgotSuccess.set(true);
  }
}