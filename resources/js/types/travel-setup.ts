export type ExpenseType = { id: number; name: string; description: string | null; is_default: boolean; is_transportation: boolean; is_active: boolean; created_at: string | null; updated_at: string | null; };
export type FundSource = { id: number; name: string; description: string | null; is_active: boolean; created_at: string | null; updated_at: string | null; };
export type Location = { id: number; name: string; description: string | null; is_active: boolean; created_at: string | null; updated_at: string | null; };
export type TravelClassification = { id: number; name: string; description: string | null; is_active: boolean; created_at: string | null; updated_at: string | null; };
export type PerDiemConfiguration = { id: number; name: string; effective_from: string | null; effective_until: string | null; daily_rate: string | null; is_active: boolean; created_at: string | null; updated_at: string | null; };
