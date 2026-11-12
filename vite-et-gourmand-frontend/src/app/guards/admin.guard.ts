import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';
import { filter, map, take } from 'rxjs';

export const adminGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const router = inject(Router);

  // Si aucun token n'existe, redirection directe vers la connexion
  if (!authService.getToken()) {
    return router.createUrlTree(['/connexion']);
  }

  // Si le user est déjà en mémoire dans le BehaviorSubject
  const currentUser = authService.getCurrentUser();
  if (currentUser) {
    const isAdmin = currentUser.roles.includes('ROLE_ADMIN');
    return isAdmin ? true : router.createUrlTree(['/']);
  }

  // Sinon (ex: refresh F5), on attend que l'appel /api/me émette le user
  return authService.currentUser$.pipe(
    filter(user => user !== null), // On attend la réponse de loadCurrentUser()
    take(1),
    map(user => {
      const isAdmin = user.roles.includes('ROLE_ADMIN');
      return isAdmin ? true : router.createUrlTree(['/']);
    })
  );
};