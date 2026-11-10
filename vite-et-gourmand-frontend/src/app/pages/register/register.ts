import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, NgForm } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './register.html',
  styleUrl: './register.css'
})
export class RegisterComponent {
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  nom = '';
  prenom = '';
  gsm = '';
  email = '';
  adressePostale = '';
  codePostal = '';
  ville = '';
  password = '';

  readonly passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#_-])[A-Za-z\d@$!%*?&#_-]{10,}$/;

  readonly isLoading = signal<boolean>(false);
  readonly errorMessage = signal<string | null>(null);
  readonly isRegistered = signal<boolean>(false);

  get isPasswordValid(): boolean {
    return this.passwordRegex.test(this.password);
  }

  onSubmit(form: NgForm): void {
    if (form.invalid || !this.isPasswordValid || this.isLoading()) return;

    this.isLoading.set(true);
    this.errorMessage.set(null);

    const payload = {
      nom: this.nom,
      prenom: this.prenom,
      email: this.email,
      password: this.password,
      telephone: this.gsm,
      adressePostale: this.adressePostale,
      adresse: this.adressePostale,
      cp: this.codePostal,
      codePostal: this.codePostal,
      ville: this.ville
    };

    this.authService.register(payload).subscribe({
  next: () => {
    this.isRegistered.set(true);
    this.authService.login({ email: this.email, password: this.password }).subscribe({
      next: () => {
        this.isLoading.set(false);
        void this.router.navigate(['/menus']);
      },
      error: () => {
        this.isLoading.set(false);
        void this.router.navigate(['/connexion']);
      }
    });
  },
  error: (err) => {
    this.isLoading.set(false);
    // Affiche le message renvoyé par Symfony ('Champs obligatoires manquants.', 'Cet email est déjà utilisé.', etc.)
    const backendMessage = err?.error?.error || 'Une erreur est survenue lors de l\'inscription.';
    this.errorMessage.set(backendMessage);
    console.error('Erreur API Register :', err);
  }
});
    
  }
}