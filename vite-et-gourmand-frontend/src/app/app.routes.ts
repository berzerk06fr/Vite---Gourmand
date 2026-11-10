import { Routes } from '@angular/router';
import { HomeComponent } from './pages/home/home';
import { MenuList } from './pages/menu-list/menu-list';
import { MenuDetail } from './pages/menu-detail/menu-detail';
import { ContactComponent } from './pages/contact/contact';
import { CommandeComponent } from './pages/commande/commande';
import { LoginComponent } from './pages/login/login';
import { RegisterComponent } from './pages/register/register';
import { MesCommandesComponent } from './pages/mes-commandes/mes-commandes';
import { authGuard } from './guards/auth.guard';

export const routes: Routes = [
  { path: '', component: HomeComponent, pathMatch: 'full' },
  { path: 'menus', component: MenuList },
  { path: 'menus/:id', component: MenuDetail },
  { path: 'menu/:id', redirectTo: 'menus/:id' },
  { path: 'contact', component: ContactComponent },
  { path: 'commande', component: CommandeComponent, canActivate: [authGuard] },
  { path: 'mes-commandes', component: MesCommandesComponent, canActivate: [authGuard] },
  { path: 'connexion', component: LoginComponent },
  { path: 'inscription', component: RegisterComponent },
  { path: '**', redirectTo: '' }
];