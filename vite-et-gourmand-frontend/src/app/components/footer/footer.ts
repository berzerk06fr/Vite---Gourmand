import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { HoraireService } from '../../services/horaire.service';

@Component({
  selector: 'app-footer',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './footer.html',
  styleUrl: './footer.css'
})
export class FooterComponent implements OnInit {
  private readonly horaireService = inject(HoraireService);

  footer = this.horaireService.footerData;

  ngOnInit(): void {
    this.horaireService.chargerFooter();
  }
}