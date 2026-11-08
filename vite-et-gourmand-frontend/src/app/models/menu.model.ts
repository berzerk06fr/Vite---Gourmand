export interface Plat {
  id: number;
  nom: string;
  type: 'entree' | 'plat' | 'dessert' | 'Entrée' | 'Plat' | 'Dessert';
  description?: string;
  allergenes?: string[];

}

export interface Menu {
  id: number;
  titre: string;
  description: string;
  theme: string;           
  regime: string;          
  nombrePersonneMin: number;
  prixParPersonne: number;
  stockDisponible: number;
  conditions: string;      
  images: string[];
  plats: Plat[];
}

export interface MenuFilter {
  prixMax?: number | null;
  prixMin?: number | null;
  theme?: string;
  regime?: string;
  minPersonnes?: number | null;
  searchTerm?: string;
}
