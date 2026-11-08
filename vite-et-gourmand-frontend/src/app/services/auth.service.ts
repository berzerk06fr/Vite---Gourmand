import { Injectable, signal, computed } from '@angular/core';

export type UserRole = 'visiteur' | 'utilisateur' | 'employe' | 'administrateur';

export interface UserSession {
  id: number;
  nom: string;
  prenom: string;
  email: string;
  gsm: string;
  adresse: string;
  ville: string;
  role: UserRole;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  
  readonly currentUser = signal<UserSession | null>(null);

  readonly isAuthenticated = computed(() => this.currentUser() !== null);
  readonly userRole = computed(() => this.currentUser()?.role ?? 'visiteur');

  login(email: string, role: UserRole = 'utilisateur'): void {
    this.currentUser.set({
      id: 1,
      nom: 'Goncalves',
      prenom: 'Angelo',
      email,
      gsm: '06 12 34 56 78',
      adresse: '12 Cours de l\'Intendance',
      ville: 'Bordeaux',
      role
    });
  }

  logout(): void {
    this.currentUser.set(null);
  }
}