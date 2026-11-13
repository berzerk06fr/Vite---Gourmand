import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient, HttpHeaders } from '@angular/common/http';

@Component({
  selector: 'app-admin-employes',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './admin-employes.html'
})
export class AdminEmployesComponent implements OnInit {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = 'https://vite-et-gourmand-api.alwaysdata.net/api/admin/employes';

  employes: any[] = [];
  messageSucces = '';
  messageErreur = '';

  nouvelEmploye = {
    prenom: '',
    nom: '',
    email: '',
    telephone: '',
    password: ''
  };

  ngOnInit(): void {
    this.chargerEmployes();
  }

  private getHeaders(): HttpHeaders {
    const token = localStorage.getItem('auth_token') || '';
    return new HttpHeaders({
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`
    });
  }

  chargerEmployes(): void {
    this.http.get<any[]>(this.apiUrl, { headers: this.getHeaders() }).subscribe({
      next: (data) => (this.employes = data),
      error: () => (this.messageErreur = 'Erreur lors du chargement de l\'équipe.')
    });
  }

  creerEmploye(): void {
    this.messageErreur = '';
    this.messageSucces = '';

    this.http.post<any>(this.apiUrl, this.nouvelEmploye, { headers: this.getHeaders() }).subscribe({
      next: (res) => {
        this.messageSucces = res.message || 'Compte employé créé.';
        this.nouvelEmploye = { prenom: '', nom: '', email: '', telephone: '', password: '' };
        this.chargerEmployes();
      },
      error: (err) => {
        this.messageErreur = err.error?.error || 'Impossible de créer le compte employé.';
      }
    });
  }

  supprimerEmploye(id: number): void {
    if (!confirm('Révoquer définitivement l\'accès de cet employé ?')) {
      return;
    }

    this.http.delete<any>(`${this.apiUrl}/${id}`, { headers: this.getHeaders() }).subscribe({
      next: (res) => {
        this.messageSucces = res.message || 'Accès révoqué.';
        this.chargerEmployes();
      },
      error: (err) => {
        this.messageErreur = err.error?.error || 'Action impossible.';
      }
    });
  }
}
