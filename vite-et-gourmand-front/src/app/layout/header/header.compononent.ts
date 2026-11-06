import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-header',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './header.component.html'
})
export class HeaderComponent {
  // Signal réactif pour piloter l'ouverture du menu mobile sans manipuler le DOM
  isNavCollapsed = signal(true);

  // État de connexion géré par Signal (prêt pour AuthService au J3)
  estConnecte = signal(false);

  toggleNavbar(): void {
    this.isNavCollapsed.update(collapsed => !collapsed);
  }

  closeNavbar(): void {
    this.isNavCollapsed.set(true);
  }
}