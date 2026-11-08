import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, NgForm } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './register.html',
  styleUrl: './register.css'
})
export class RegisterComponent {
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  nom = '';
  prenom = '';
  gsm = '';
  email = '';
  adressePostale = '';
  password = '';

  readonly passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_\-#])[A-Za-z\d@$!%*?&_\-#]{10,}$/;

  readonly isRegistered = signal<boolean>(false);

  get isPasswordValid(): boolean {
    return this.passwordRegex.test(this.password);
  }

  onSubmit(form: NgForm): void {
    if (form.invalid || !this.isPasswordValid) return;

    this.isRegistered.set(true);

    setTimeout(() => {
      this.authService.login(this.email, 'utilisateur');
      void this.router.navigate(['/menus']);
    }, 1200);
  }
}