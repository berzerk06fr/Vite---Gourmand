import { Component, computed, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { toSignal } from '@angular/core/rxjs-interop';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-navbar',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './navbar.html',
  styleUrl: './navbar.css'
})
export class NavbarComponent {
  private readonly authService = inject(AuthService);

  readonly isMenuCollapsed = signal(true);

  readonly currentUser = toSignal(this.authService.currentUser$, { initialValue: null });

  // Vérifie si l'utilisateur connecté est un client (ni employé, ni admin)
  readonly isClient = computed(() => {
    const user = this.currentUser();
    if (!user) return false;
    const roles: string[] = user.roles || [];
    const isStaff = roles.includes('ROLE_EMPLOYE') || roles.includes('ROLE_ADMIN');
    return !isStaff;
  });

  // Utile si tu as d'autres liens staff (ex: Gestion Commandes)
  readonly isStaff = computed(() => {
    const user = this.currentUser();
    if (!user) return false;
    const roles: string[] = user.roles || [];
    return roles.includes('ROLE_EMPLOYE') || roles.includes('ROLE_ADMIN');
  });

  toggleMenu(): void {
    this.isMenuCollapsed.update(c => !c);
  }

  closeMenu(): void {
    this.isMenuCollapsed.set(true);
  }

  onLogout(): void {
    this.closeMenu();
    this.authService.logout();
  }
}