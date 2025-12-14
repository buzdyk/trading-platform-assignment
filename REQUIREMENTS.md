🛠 Technology Stack
Backend: Laravel (latest stable preferred)
Frontend: Vue.js (latest stable preferred, Composition API encouraged)
Database: MySQL or PostgreSQL
Real-time: Pusher via Laravel Broadcasting
📌 Project Requirements
1. Backend – Laravel API
   Required Database Tables
   You must include at least:

users - Default Laravel columns + balance (decimal, USD funds)
assets - user_id, symbol (e.g., BTC, ETH), amount (decimal, available asset), locked_amount (decimal, reserved for open sell orders)
orders - user_id, symbol, side (buy/sell), price (decimal), amount (decimal), status (open=1, filled=2, cancelled=3), Timestamps
trades (Optional) - You may store executed matches here for bonus points, but not required
Mandatory API Endpoints
Method	Endpoint	Purpose
GET	/api/profile	Returns authenticated user's USD balance + asset balances
GET	/api/orders?symbol=BTC	Returns all open orders for orderbook (buy & sell)
POST	/api/orders	Creates a limit order
POST	/api/orders/{id}/cancel	Cancels an open order and releases locked USD or assets
POST	Internal matching or job-based match trigger	Matches new orders with the first valid counter order
Core Business Logic
Buy Order:

Check if users.balance >= amount * price
Deduct amount * price from users.balance
Mark order as open with locked USD value
Sell Order:

Check if assets.amount >= amount
Move amount into assets.locked_amount
Mark order as open
Matching Rules (Full Match Only – No Partial Required):
New BUY → match with first SELL where sell.price <= buy.price
New SELL → match with first BUY where buy.price >= sell.price
Commission (Must Stay):
Commission = 1.5% of the matched USD value

Example: 0.01 BTC @ 95,000 USD = 950 USD volume. Fee = 950 * 0.015 = 14.25 USD. USD fee must be deducted from buyer (sender) and/or asset fee from seller — your choice, but must be consistent.

2. Real-Time Integration (Mandatory)
   On every successful match, broadcast OrderMatched event via Pusher
   Deliver to both parties using private channels: private-user.{id}
   Front-end must update balance, asset and order list instantly without refresh
3. Frontend – Vue.js (Composition API) + Tailwind Latest Version
   You only need 2 custom screens + Login/Logout etc.:

A) Limit Order Form

Inputs: Symbol (BTC/ETH dropdown), Side (Buy/Sell), Price, Amount

Submit button: Place Order

B) Orders & Wallet Overview

Sections:

USD and Asset balances (via /api/profile)
All past orders (open + filled + cancelled)
Orderbook for selected symbol
Listen for OrderMatched event and: patch new trade into UI, update balance and asset, update order status in list
Bonus (optional): order filtering (by symbol/side/status), toast/alerts, volume calculation preview

4. Evaluation Focus
   We will review:

Balance & asset race safety
Atomic execution
Commission correctness
Real-time listener stability
Clean repository, security validation, fast setup, meaningful git commits
