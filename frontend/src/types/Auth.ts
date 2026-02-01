export interface User {
  id: number;
  email: string;
  name: string;
  roles: string[];
  active: boolean;
  created_at: string;
  updated_at: string;
}

export interface LoginCredentials {
  username: string;
  password: string;
}

export interface LoginResponse {
  token: string;
}

export interface UserCreate {
  email: string;
  name: string;
  password: string;
  roles?: string[];
  active?: boolean;
}

export interface UserUpdate {
  email?: string;
  name?: string;
  password?: string;
  roles?: string[];
  active?: boolean;
}

export interface UsersResponse {
  data: User[];
  meta: {
    page: number;
    limit: number;
    total: number;
    pages: number;
  };
}
