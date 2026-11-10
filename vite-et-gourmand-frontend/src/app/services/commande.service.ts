import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface CreateCommandePayload {
  menuId: number;
  nbPersonnes: number;
  ville: string;
  distanceKm: number;
  datePrevueLivraison?: string;
  nom?: string;
  prenom?: string;
  email?: string;
  gsm?: string;
  adresse?: string;
}

export interface CommandeResponse {
  message: string;
  numeroCommande: string;
  menu: string;
  nbPersonnes: number;
  prixMenu: number;
  reduction10Pourcent: boolean;
  fraisLivraison: number;
  prixTotal: number;
  statut: string;
  stockRestant: number;
}

export interface CommandeItem {
  id: number;
  numeroCommande: string;
  nbPersonnes: number;
  prixTotal: number;
  statut: string;
  client?: { id: number; email: string };
  menu?: { id: number; titre: string };
}

@Injectable({
  providedIn: 'root'
})
export class CommandeService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = 'http://127.0.0.1:8000/api/commandes';

  createCommande(payload: CreateCommandePayload): Observable<CommandeResponse> {
    return this.http.post<CommandeResponse>(this.apiUrl, payload);
  }

  getMyCommandes(): Observable<CommandeItem[]> {
    return this.http.get<CommandeItem[]>(this.apiUrl);
  }
}