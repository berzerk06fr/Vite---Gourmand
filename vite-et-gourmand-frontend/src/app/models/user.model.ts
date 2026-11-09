export interface User {
  id: number;
  email: string;
  nom: string;
  prenom: string;
  telephone?: string;
  adresse?: string;
  roles: string[];
}

export interface AuthResponse {
  token: string;
}

export interface RegisterPayload {
  email: string;
  password: string;
  nom: string;
  prenom: string;
  telephone?: string;
  adressePostale?: string;
  ville?: string;
  cp?: string;
}