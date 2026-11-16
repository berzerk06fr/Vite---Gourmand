import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AuthService, UpdateProfilePayload } from '../../services/auth.service';

@Component({
  selector: 'app-mon-profil',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './mon-profil.html'
})
export class MonProfilComponent implements OnInit {
  private readonly authService = inject(AuthService);

  email = '';
  nom = '';
  prenom = '';
  telephone = '';
  adresse = '';
  newPassword = '';
  confirmPassword = '';

  loading = false;
  messageSucces = '';
  messageErreur = '';

  ngOnInit(): void {
    const current = this.authService.getCurrentUser();
    if (current) {
      this.remplirFormulaire(current);
    }

    this.authService.loadCurrentUser().subscribe({
      next: (user) => this.remplirFormulaire(user),
      error: () => {
        this.messageErreur = 'Impossible de charger vos informations.';
      }
    });
  }

  private remplirFormulaire(user: any): void {
    this.email = user.email ?? '';
    this.nom = user.nom ?? '';
    this.prenom = user.prenom ?? '';
    this.telephone = user.telephone ?? '';
    this.adresse = user.adresse ?? '';
  }

  onSubmit(): void {
    this.messageSucces = '';
    this.messageErreur = '';

    if (!this.nom.trim() || !this.prenom.trim()) {
      this.messageErreur = 'Le nom et le prénom sont obligatoires.';
      return;
    }

    if (this.newPassword && this.newPassword !== this.confirmPassword) {
      this.messageErreur = 'Les deux mots de passe ne correspondent pas.';
      return;
    }

    const payload: UpdateProfilePayload = {
      nom: this.nom.trim(),
      prenom: this.prenom.trim(),
      telephone: this.telephone.trim(),
      adresse: this.adresse.trim()
    };

    if (this.newPassword.trim() !== '') {
      payload.newPassword = this.newPassword;
    }

    this.loading = true;

    this.authService.updateProfile(payload).subscribe({
      next: (res) => {
        this.loading = false;
        this.messageSucces = res.message || 'Vos informations ont été mises à jour avec succès.';
        this.newPassword = '';
        this.confirmPassword = '';
      },
      error: (err) => {
        this.loading = false;
        this.messageErreur = err?.error?.error || 'Une erreur est survenue lors de la mise à jour.';
      }
    });
  }
}
