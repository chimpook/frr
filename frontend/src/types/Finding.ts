export type RiskRange = 'Low' | 'Medium' | 'High';

export interface Finding {
  id: string;
  location: string;
  risk_range: RiskRange;
  comment: string;
  recommendations: string;
  resolved: boolean;
  created_at: string;
  updated_at: string;
}

export interface FindingCreate {
  location: string;
  risk_range: RiskRange;
  comment: string;
  recommendations: string;
  resolved?: boolean;
}

export interface FindingUpdate {
  location?: string;
  risk_range?: RiskRange;
  comment?: string;
  recommendations?: string;
  resolved?: boolean;
}

export interface PaginationMeta {
  page: number;
  limit: number;
  total: number;
  pages: number;
}

export interface FindingsResponse {
  data: Finding[];
  meta: PaginationMeta;
}
