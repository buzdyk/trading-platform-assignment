export interface User {
  id: number
  name: string
  email: string
  balance: string
  email_verified_at?: string
  created_at: string
  updated_at?: string
}

export interface Symbol {
  id: number
  code: string
  name: string
}

export interface Asset {
  symbol: string
  amount: string
  locked_amount: string
}

export interface Order {
  id: number
  symbol: string
  side: 'buy' | 'sell'
  price: string
  amount: string
  status: 'open' | 'filled' | 'cancelled'
  user?: string
  created_at: string
}
