import { Component, inject, signal } from '@angular/core';
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