import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface CommandeItem {
  id: number;
  numeroCommande: string;
  nbPersonnes: number;
  prixMenu?: number;
  fraisLivraison?: number;
  prixTotal: number;
  statut: string;
  adresse?: string;
  ville?: string;
  dateCommande?: string;
  datePrevueLivraison?: string;
  client?: {
    id: number;
    email: string;
    nom?: string;
    prenom?: string;
    telephone?: string;
    adresse?: string;
  } | null;
  menu?: {
    id: number;
    titre: string;
  } | null;
}

export interface CreateCommandePayload {
  menuId: number;
  nbPersonnes: number;
  nom?: string;
  prenom?: string;
  email?: string;
  gsm?: string;
  telephone?: string;
  adresse?: string;
  codePostal?: string;
  ville?: string;
  distanceKm?: number;
  datePrevueLivraison?: string;
  heureLivraison?: string;
  commentaire?: string;
  [key: string]: unknown;
}

export interface CommandeResponse {
  message: string;
  numeroCommande: string;
  menu?: string;
  nbPersonnes?: number;
  prixMenu?: number;
  reduction10Pourcent?: boolean;
  fraisLivraison?: number;
  prixTotal: number;
  statut?: string;
  stockRestant?: number;
}

export interface UpdateCommandeClientPayload {
  menuId?: number;
  nbPersonnes: number;
  adresse: string;
  ville: string;
  distanceKm?: number;
  datePrevueLivraison?: string;
}

export interface UpdateCommandeStaffPayload {
  menuId?: number;
  nbPersonnes: number;
  adresse: string;
  ville: string;
  distanceKm?: number;
  datePrevueLivraison?: string;
}

@Injectable({
  providedIn: 'root'
})
export class CommandeService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = 'https://vite-et-gourmand-api.alwaysdata.net/api/commandes';

  getMyCommandes(): Observable<CommandeItem[]> {
    return this.http.get<CommandeItem[]>(this.apiUrl);
  }

  createCommande(payload: CreateCommandePayload): Observable<CommandeResponse> {
    return this.http.post<CommandeResponse>(this.apiUrl, payload);
  }

  updateStatut(
    id: number,
    payload: { statut: string; motifAnnulation?: string; modeContact?: 'gsm' | 'mail' }
  ): Observable<{ message: string; numeroCommande: string; statut: string }> {
    return this.http.patch<{ message: string; numeroCommande: string; statut: string }>(
      `${this.apiUrl}/${id}/statut`,
      payload
    );
  }

  modifierCommandeClient(
    id: number,
    payload: UpdateCommandeClientPayload
  ): Observable<{ message: string; numeroCommande: string; nbPersonnes: number; prixTotal: number; statut: string }> {
    return this.http.put<{ message: string; numeroCommande: string; nbPersonnes: number; prixTotal: number; statut: string }>(
      `${this.apiUrl}/${id}`,
      payload
    );
  }

  annulerCommandeClient(id: number): Observable<{ message: string; statut: string }> {
    return this.http.patch<{ message: string; statut: string }>(
      `${this.apiUrl}/${id}/annuler`,
      {}
    );
  }

  modifierCommandeStaff(
    id: number,
    payload: UpdateCommandeStaffPayload
  ): Observable<{ message: string; numeroCommande: string; nbPersonnes: number; prixTotal: number }> {
    return this.http.put<{ message: string; numeroCommande: string; nbPersonnes: number; prixTotal: number }>(
      `${this.apiUrl}/${id}/staff`,
      payload
    );
  }
}
