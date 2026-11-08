import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, NgForm } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { AuthService, UserRole } from '../../services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './login.html',
  styleUrl: './login.css'
})
export class LoginComponent {
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  email = '';
  password = '';
  roleChoice: UserRole = 'utilisateur';

  readonly isForgotMode = signal<boolean>(false);
  readonly forgotSuccess = signal<boolean>(false);

  onSubmit(form: NgForm): void {
    if (form.invalid) return;

    this.authService.login(this.email, this.roleChoice);
    void this.router.navigate(['/menus']);
  }

  sendResetLink(): void {
    if (!this.email) return;
    this.forgotSuccess.set(true);
  }
}