// API Types
export interface User {
  id: string;
  name: string;
  email: string;
  phone?: string;
  role: 'patient' | 'provider' | 'admin';
  provider_fields?: {
    specialization?: string;
    license_number?: string;
  };
}

export interface Patient {
  id: string;
  user_id: string;
  dob: string;
  gender: string;
  address: string;
  insurance_provider?: string;
  insurance_member_id?: string;
  emergency_contact?: {
    name: string;
    phone: string;
  };
  user?: User;
}

export interface Provider extends User {
  specialization?: string;
  license_number?: string;
}

export interface Appointment {
  id: string;
  patient_id: string;
  provider_id: string;
  status: 'requested' | 'scheduled' | 'completed' | 'cancelled';
  scheduled_at: string;
  reason: string;
  notes?: string;
  created_by: string;
  patient?: Patient;
  provider?: Provider;
}

export interface IntakeForm {
  id: string;
  patient_id: string;
  status: 'uploaded' | 'processing' | 'extracted' | 'failed';
  source_type: 'pdf' | 'image' | 'text';
  source_url?: string;
  extracted_payload?: ExtractedData;
  confidence?: number;
  processed_at?: string;
  created_at: string;
}

export interface ExtractedData {
  full_name?: string;
  dob?: string;
  symptoms?: string[];
  medications?: string[];
  allergies?: string[];
  insurance?: {
    provider?: string;
    member_id?: string;
  };
  [key: string]: any;
}

// Auth Types
export interface LoginRequest {
  email: string;
  password: string;
}

export interface RegisterRequest {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  phone?: string;
}

export interface AuthResponse {
  token: string;
  user: User;
}

// API Response Types
export interface ApiResponse<T = any> {
  data: T;
  message?: string;
  meta?: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}

// Form Types
export interface IntakeUploadRequest {
  file?: File;
  text?: string;
  source_type: 'pdf' | 'image' | 'text';
}

export interface AppointmentRequest {
  patient_id?: string;
  provider_id: string;
  scheduled_at: string;
  reason: string;
}

export interface ProfileUpdateRequest {
  name?: string;
  phone?: string;
  dob?: string;
  gender?: string;
  address?: string;
  insurance_provider?: string;
  insurance_member_id?: string;
  emergency_contact?: {
    name: string;
    phone: string;
  };
}

// Store Types
export interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  login: (credentials: LoginRequest) => Promise<void>;
  register: (data: RegisterRequest) => Promise<void>;
  logout: () => void;
  updateUser: (user: User) => void;
}

export interface ToastState {
  toasts: Toast[];
  addToast: (toast: Omit<Toast, 'id'>) => void;
  removeToast: (id: string) => void;
}

export interface Toast {
  id: string;
  title: string;
  description?: string;
  type: 'success' | 'error' | 'warning' | 'info';
  duration?: number;
}