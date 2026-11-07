export interface Plat {
  id: number;
  nom: string;
  description?: string;
  type: 'entree' | 'plat' | 'dessert';
  allergenes: string[];
}

export interface Menu {
  id: number;
  titre: string;
  description: string;
  theme: string;           // ex: Noël, Pâques, Classique, Évènement
  regime: string;          // ex: Végétarien, Vegan, Carnivore, Poisson, Classique
  nombrePersonneMin: number;
  prixParPersonne: number;
  stockDisponible: number;
  conditions: string;      // Délais, stockage
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