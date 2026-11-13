import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient, HttpHeaders } from '@angular/common/http';

@Component({
  selector: 'app-admin-catalogue',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './admin-catalogue.html'
})
export class AdminCatalogueComponent implements OnInit {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = 'https://vite-et-gourmand-api.alwaysdata.net/api/menus';

  ongletActif: 'menus' | 'plats' = 'menus';
  menus: any[] = [];
  plats: any[] = [];
  chargement = false;
  uploadEnCours = false;
  messageSucces = '';
  messageErreur = '';

  // Formulaire Menu
  menuEnEditionId: number | null = null;
  formMenu = {
    titre: '',
    description: '',
    theme: 'Classique',
    regime: 'Carnivore',
    image: '',
    conditionsMenu: '',
    prixParPersonne: 25,
    nbPersonnesMin: 6,
    quantiteStock: 10,
    isActif: true,
    platIds: [] as number[]
  };

  // Formulaire Plat
  platEnEditionId: number | null = null;
  formPlat = {
    nom: '',
    description: '',
    type: 'Plat principal',
    allergenes: '',
    stock: 15,
    seuilAlerte: 5
  };

  ngOnInit(): void {
    this.chargerDonnees();
  }

  private getHeaders(json = true): HttpHeaders {
    const token = localStorage.getItem('auth_token') || '';
    const headersConfig: Record<string, string> = {
      Authorization: `Bearer ${token}`
    };
    if (json) {
      headersConfig['Content-Type'] = 'application/json';
    }
    return new HttpHeaders(headersConfig);
  }

  chargerDonnees(): void {
    this.chargement = true;
    this.http.get<any[]>(this.apiUrl).subscribe({
      next: (data) => {
        this.menus = data;
        this.chargement = false;
      },
      error: () => {
        this.afficherErreur('Impossible de charger les menus.');
        this.chargement = false;
      }
    });

    this.http.get<any[]>(`${this.apiUrl}/plats/all`).subscribe({
      next: (data) => (this.plats = data),
      error: () => this.afficherErreur('Impossible de charger les plats.')
    });
  }

  // --- GESTION DES MENUS & PHOTO ---

  onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (!input.files || input.files.length === 0) {
      return;
    }

    const formData = new FormData();
    formData.append('image', input.files[0]);

    this.uploadEnCours = true;
    this.http.post<{ url: string }>(`${this.apiUrl}/upload-image`, formData, { headers: this.getHeaders(false) }).subscribe({
      next: (res) => {
        this.formMenu.image = res.url;
        this.uploadEnCours = false;
        this.afficherSucces('Photo téléversée avec succès.');
      },
      error: (err) => {
        this.uploadEnCours = false;
        this.afficherErreur(err.error?.error || 'Erreur lors de l\'envoi de la photo.');
      }
    });
  }

  togglePlatDansMenu(platId: number): void {
    const index = this.formMenu.platIds.indexOf(platId);
    if (index === -1) {
      this.formMenu.platIds.push(platId);
    } else {
      this.formMenu.platIds.splice(index, 1);
    }
  }

  editerMenu(menu: any): void {
    this.menuEnEditionId = menu.id;
    this.formMenu = {
      titre: menu.titre,
      description: menu.description || '',
      theme: menu.theme || 'Classique',
      regime: menu.regime || 'Carnivore',
      image: menu.image || '',
      conditionsMenu: menu.conditionsMenu || '',
      prixParPersonne: menu.prixParPersonne,
      nbPersonnesMin: menu.nbPersonnesMin,
      quantiteStock: menu.quantiteStock,
      isActif: menu.isActif,
      platIds: (menu.plats || []).map((p: any) => p.id)
    };
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  reinitialiserFormMenu(): void {
    this.menuEnEditionId = null;
    this.formMenu = {
      titre: '',
      description: '',
      theme: 'Classique',
      regime: 'Carnivore',
      image: '',
      conditionsMenu: '',
      prixParPersonne: 25,
      nbPersonnesMin: 6,
      quantiteStock: 10,
      isActif: true,
      platIds: []
    };
  }

  sauvegarderMenu(): void {
    const req$ = this.menuEnEditionId
      ? this.http.put(`${this.apiUrl}/${this.menuEnEditionId}`, this.formMenu, { headers: this.getHeaders() })
      : this.http.post(this.apiUrl, this.formMenu, { headers: this.getHeaders() });

    req$.subscribe({
      next: () => {
        this.afficherSucces(this.menuEnEditionId ? 'Menu mis à jour.' : 'Nouveau menu créé.');
        this.reinitialiserFormMenu();
        this.chargerDonnees();
      },
      error: (err) => this.afficherErreur(err.error?.error || 'Erreur lors de l\'enregistrement du menu.')
    });
  }

  supprimerMenu(id: number): void {
    if (!confirm('Confirmer la suppression ou désactivation de ce menu ?')) {
      return;
    }
    this.http.delete<any>(`${this.apiUrl}/${id}`, { headers: this.getHeaders() }).subscribe({
      next: (res) => {
        this.afficherSucces(res.message || 'Menu supprimé.');
        this.chargerDonnees();
      },
      error: (err) => this.afficherErreur(err.error?.error || 'Action non autorisée.')
    });
  }

  // --- GESTION DES PLATS & STOCKS ---

  editerPlat(plat: any): void {
    this.platEnEditionId = plat.id;
    this.formPlat = {
      nom: plat.nom,
      description: plat.description || '',
      type: plat.type,
      allergenes: plat.allergenesRaw || '',
      stock: plat.stock,
      seuilAlerte: plat.seuilAlerte
    };
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  reinitialiserFormPlat(): void {
    this.platEnEditionId = null;
    this.formPlat = {
      nom: '',
      description: '',
      type: 'Plat principal',
      allergenes: '',
      stock: 15,
      seuilAlerte: 5
    };
  }

  sauvegarderPlat(): void {
    const req$ = this.platEnEditionId
      ? this.http.put(`${this.apiUrl}/plats/${this.platEnEditionId}`, this.formPlat, { headers: this.getHeaders() })
      : this.http.post(`${this.apiUrl}/plats`, this.formPlat, { headers: this.getHeaders() });

    req$.subscribe({
      next: () => {
        this.afficherSucces(this.platEnEditionId ? 'Plat et stock mis à jour.' : 'Nouveau plat ajouté.');
        this.reinitialiserFormPlat();
        this.chargerDonnees();
      },
      error: (err) => this.afficherErreur(err.error?.error || 'Erreur lors de l\'enregistrement du plat.')
    });
  }

  supprimerPlat(id: number): void {
    if (!confirm('Supprimer définitivement ce plat ?')) {
      return;
    }
    this.http.delete(`${this.apiUrl}/plats/${id}`, { headers: this.getHeaders() }).subscribe({
      next: () => {
        this.afficherSucces('Plat supprimé.');
        this.chargerDonnees();
      },
      error: (err) => this.afficherErreur(err.error?.error || 'Action réservée à l\'administrateur.')
    });
  }

  private afficherSucces(msg: string): void {
    this.messageSucces = msg;
    this.messageErreur = '';
    setTimeout(() => (this.messageSucces = ''), 4000);
  }

  private afficherErreur(msg: string): void {
    this.messageErreur = msg;
    this.messageSucces = '';
    setTimeout(() => (this.messageErreur = ''), 5000);
  }
}
