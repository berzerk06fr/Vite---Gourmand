import { Routes } from '@angular/router';
import { HomeComponent } from './pages/home/home';
import { MenuList } from './pages/menu-list/menu-list';
import { MenuDetail } from './pages/menu-detail/menu-detail';
import { ContactComponent } from './pages/contact/contact';
import { CommandeComponent } from './pages/commande/commande';
import { LoginComponent } from './pages/login/login';
import { RegisterComponent } from './pages/register/register';
import { MesCommandesComponent } from './pages/mes-commandes/mes-commandes';
import { adminGuard } from './guards/admin.guard';
import { authGuard } from './guards/auth.guard';
import { staffGuard } from './guards/staff.guard';
import { AdminCommandesComponent } from './pages/admin-commandes/admin-commandes';
import { AdminStatistiquesComponent } from './pages/admin-statistiques/admin-statistiques';


export const routes: Routes = [
  { path: '', component: HomeComponent, pathMatch: 'full' },
  { path: 'menus', component: MenuList },
  { path: 'menus/:id', component: MenuDetail },
  { path: 'menu/:id', redirectTo: 'menus/:id' },
  { path: 'contact', component: ContactComponent },
  { path: 'commande', component: CommandeComponent, canActivate: [authGuard] },
  { path: 'mes-commandes', component: MesCommandesComponent, canActivate: [authGuard] },
  { path: 'admin/commandes', component: AdminCommandesComponent, canActivate: [authGuard, staffGuard] },
  { path: 'admin/statistiques', component: AdminStatistiquesComponent, canActivate: [authGuard, adminGuard] },
  { path: 'connexion', component: LoginComponent },
  { path: 'inscription', component: RegisterComponent },
  { path: '**', redirectTo: '' }
];