import { Routes } from '@angular/router';

export const routes: Routes = [
  {
    path: '',
    pathMatch: 'full',
    redirectTo: 'menus'
  },
  {
    path: 'menus',
    loadComponent: () =>
      import('./pages/menu-list/menu-list').then(m => m.MenuList)
  }
];