import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-footer',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './footer.component.html'
})
export class FooterComponent {
  // Horaires imposés par le sujet (p. 4)
  horaires = [
    { jour: 'Lundi', heures: '08:30 - 19:00' },
    { jour: 'Mardi', heures: '08:30 - 19:00' },
    { jour: 'Mercredi', heures: '08:30 - 19:00' },
    { jour: 'Jeudi', heures: '08:30 - 19:00' },
    { jour: 'Vendredi', heures: '08:30 - 20:30' },
    { jour: 'Samedi', heures: '09:00 - 21:00' },
    { jour: 'Dimanche', heures: '09:30 - 15:00' }
  ];
}