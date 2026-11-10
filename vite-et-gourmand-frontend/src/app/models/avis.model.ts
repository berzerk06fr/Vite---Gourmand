export interface Avis {
  id: number;
  note: number;
  commentaire: string;
  statut: 'en_attente' | 'valide' | 'refuse';
  dateCreation?: string;
  auteur?: {
    id?: number;
    pseudo: string;
  };
  commande?: {
    id: number;
    numeroCommande: string;
  };
  menuTitre?: string;
}

export interface CreerAvisDto {
  note: number;
  commentaire: string;
  commandeId?: number;
}