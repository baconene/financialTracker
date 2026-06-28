export interface User {
  id: number
  name: string
  email: string
}

export interface Account {
  id: number
  user_id: number
  name: string
  type: 'bank' | 'cash' | 'e-wallet' | 'credit' | 'investment'
  bank_name?: string
  account_number?: string
  balance: number
  currency: string
  color: string
  is_active: boolean
  transactions_count?: number
  created_at: string
  updated_at: string
}

export interface Category {
  id: number
  user_id: number
  name: string
  icon: string
  color: string
  type: 'income' | 'expense' | 'both'
  is_system: boolean
}

export interface Transaction {
  id: number
  user_id: number
  account_id: number
  category_id?: number
  type: 'income' | 'expense' | 'transfer'
  amount: number
  description: string
  notes?: string
  transaction_date: string
  reference_number?: string
  category?: Category
  account?: Account
  created_at: string
  updated_at: string
}

export interface SavingsGoal {
  id: number
  user_id: number
  name: string
  description?: string
  target_amount: number
  current_amount: number
  target_date?: string
  image_url?: string
  color: string
  icon: string
  status: 'active' | 'completed' | 'cancelled'
  progress_percentage: number
  created_at: string
  updated_at: string
}

export interface SavingsContribution {
  id: number
  savings_goal_id: number
  user_id: number
  amount: number
  notes?: string
  contribution_date: string
}

export interface Loan {
  id: number
  user_id: number
  name: string
  lender: string
  principal_amount: number
  remaining_balance: number
  interest_rate: number
  interest_type: 'simple' | 'compound'
  payment_frequency: 'weekly' | 'biweekly' | 'monthly' | 'quarterly'
  monthly_payment: number
  term_months: number
  start_date: string
  end_date: string
  next_payment_date?: string
  status: 'active' | 'paid' | 'defaulted'
  notes?: string
  paid_amount: number
  payments?: LoanPayment[]
}

export interface LoanPayment {
  id: number
  loan_id: number
  user_id: number
  amount: number
  principal_portion: number
  interest_portion: number
  payment_date: string
  reference_number?: string
  notes?: string
}

export interface Bill {
  id: number
  user_id: number
  name: string
  amount: number
  frequency: 'weekly' | 'biweekly' | 'monthly' | 'quarterly' | 'annually' | 'one-time'
  due_day?: number
  next_due_date: string
  category?: string
  payee?: string
  color: string
  icon: string
  auto_pay: boolean
  is_active: boolean
  notes?: string
  status: 'overdue' | 'due_soon' | 'upcoming'
}

export interface Budget {
  id: number
  user_id: number
  name: string
  month: number
  year: number
  total_budget: number
  budget_categories?: BudgetCategory[]
}

export interface BudgetCategory {
  id: number
  budget_id: number
  category_id: number
  allocated_amount: number
  category?: Category
}

export interface PaginatedData<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
  links: { url?: string; label: string; active: boolean }[]
}

export interface PageProps {
  auth: {
    user: User | null
  }
  flash: {
    success?: string
    error?: string
  }
}
