import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';
import { filter, map, take } from 'rxjs';

export const staffGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (!authService.getToken()) {
    return router.createUrlTree(['/connexion']);
  }

  const currentUser = authService.getCurrentUser();
  if (currentUser) {
    const isStaff = currentUser.roles.includes('ROLE_ADMIN') || currentUser.roles.includes('ROLE_EMPLOYE');
    return isStaff ? true : router.createUrlTree(['/']);
  }

  return authService.currentUser$.pipe(
    filter(user => user !== null),
    take(1),
    map(user => {
      const isStaff = user.roles.includes('ROLE_ADMIN') || user.roles.includes('ROLE_EMPLOYE');
      return isStaff ? true : router.createUrlTree(['/']);
    })
  );
};
