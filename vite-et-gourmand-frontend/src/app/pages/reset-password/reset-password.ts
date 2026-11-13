import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';

@Component({
  selector: 'app-reset-password',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './reset-password.html'
})
export class ResetPasswordComponent implements OnInit {
  private readonly http = inject(HttpClient);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly apiUrl = 'https://vite-et-gourmand-api.alwaysdata.net/api';

  token = '';
  email = '';
  password = '';
  confirmPassword = '';

  readonly chargement = signal<boolean>(false);
  readonly messageSucces = signal<string>('');
  readonly messageErreur = signal<string>('');
  readonly devResetUrl = signal<string>('');

  ngOnInit(): void {
    this.route.queryParamMap.subscribe((params) => {
      this.token = params.get('token') ?? '';
      this.messageSucces.set('');
      this.messageErreur.set('');
    });
  }

  demanderLien(): void {
    const emailClean = this.email.trim();
    if (!emailClean) {
      this.messageErreur.set('Veuillez saisir votre adresse email.');
      return;
    }

    this.chargement.set(true);
    this.messageErreur.set('');
    this.messageSucces.set('');
    this.devResetUrl.set('');

    this.http.post<{ message: string; devResetUrl?: string }>(`${this.apiUrl}/forgot-password`, { email: emailClean }).subscribe({
      next: (res) => {
        this.chargement.set(false);
        this.messageSucces.set(res.message);
        this.devResetUrl.set(res.devResetUrl ?? '');
      },
      error: (err) => {
        this.chargement.set(false);
        this.messageErreur.set(err.error?.error || 'Une erreur est survenue.');
      }
    });
  }

  reinitialiserMotDePasse(): void {
    if (this.password !== this.confirmPassword) {
      this.messageErreur.set('Les deux mots de passe ne correspondent pas.');
      return;
    }

    this.chargement.set(true);
    this.messageErreur.set('');
    this.messageSucces.set('');

    this.http.post<{ message: string }>(`${this.apiUrl}/reset-password`, {
      token: this.token,
      password: this.password
    }).subscribe({
      next: (res) => {
        this.chargement.set(false);
        this.messageSucces.set(res.message);
        setTimeout(() => {
          void this.router.navigate(['/connexion']);
        }, 2500);
      },
      error: (err) => {
        this.chargement.set(false);
        this.messageErreur.set(err.error?.error || 'Lien invalide ou mot de passe trop faible.');
      }
    });
  }
}
