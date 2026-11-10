export interface Plat {
  id: number;
  nom: string;
  description?: string | null;
  type: string;
  allergenes?: string[];
  stock?: number;
  seuilAlerte?: number;
  isEnRupture?: boolean;
  isStockFaible?: boolean;
}

export interface Menu {
  id: number;
  titre: string;
  description: string;
  conditions?: string;
  conditionsMenu?: string;
  prixParPersonne: number;
  nombrePersonneMin: number;
  nbPersonnesMin?: number;
  quantiteStock?: number;
  stockDisponible?: number;
  isActif?: boolean;
  theme: string;
  regime?: string;
  images?: string[];
  plats?: Plat[];
  allergenes?: string[];
}