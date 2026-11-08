import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-navbar',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './navbar.html',
  styleUrl: './navbar.css'
})
export class NavbarComponent {
  readonly isMenuCollapsed = signal(true);
  toggleMenu(): void {
    this.isMenuCollapsed.update(c => !c);
  }
  closeMenu(): void {
    this.isMenuCollapsed.set(true);
  }
}