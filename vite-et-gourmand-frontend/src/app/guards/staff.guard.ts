import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

export const staffGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const router = inject(Router);

  const user = authService.getCurrentUser();
  const isStaff = !!user && (user.roles.includes('ROLE_ADMIN') || user.roles.includes('ROLE_EMPLOYE'));

  if (isStaff) {
    return true;
  }

  return router.createUrlTree(['/connexion']);
};