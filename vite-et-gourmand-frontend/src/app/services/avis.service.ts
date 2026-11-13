import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { Avis, CreerAvisDto } from '../models/avis.model';

@Injectable({
  providedIn: 'root'
})
export class AvisService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = 'https://vite-et-gourmand-api.alwaysdata.net/api/avis';

  /**
   * Récupère la liste des avis validés pour affichage public
   */
  getAvisValides(): Observable<Avis[]> {
    return this.http.get<Avis[]>(this.apiUrl).pipe(
      catchError(() => of([]))
    );
  }

  /**
   * Récupère les avis en attente de modération (réservé employés/admin)
   */
  getAvisEnAttente(): Observable<Avis[]> {
    return this.http.get<Avis[]>(`${this.apiUrl}/en-attente`).pipe(
      catchError(() => of([]))
    );
  }

  /**
   * Permet à un client de soumettre un avis
   */
  creerAvis(payload: CreerAvisDto): Observable<{ message: string; id: number; statut: string }> {
    return this.http.post<{ message: string; id: number; statut: string }>(this.apiUrl, payload);
  }

  /**
   * Permet au staff de valider ou refuser un avis
   */
  modererAvis(id: number, action: 'valider' | 'refuser'): Observable<{ message: string; statut: string }> {
    return this.http.patch<{ message: string; statut: string }>(`${this.apiUrl}/${id}/moderation`, { action });
  }
}