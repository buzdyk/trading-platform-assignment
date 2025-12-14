# Trading Platform API

Laravel 12 backend API for a cryptocurrency trading platform.

## Requirements

- PHP 8.2+
- Composer

## Development Commands

```bash
# Development server
php artisan serve

# Run database migrations
php artisan migrate

# Fresh migration with seed data
php artisan migrate:fresh --seed

# Run tests
php artisan test

# Laravel Tinker (REPL)
php artisan tinker
```

## Database Structure

### Tables
- **users** - User accounts with USD balance
- **symbols** - Trading symbols (BTC, ETH)
- **assets** - User crypto holdings per symbol
- **orders** - Limit orders (buy/sell)
- **trades** - Executed trade history

### Decimal Precision

All monetary columns use `decimal(18, 8)`:

| Type | Precision | Rationale |
|------|-----------|-----------|
| USD (balance, total, commission) | 8 decimals | Avoids rounding errors in calculations. Display rounds to 2. |
| Crypto amounts (BTC, ETH) | 8 decimals | BTC standard is 8 (satoshi). ETH has 18 (wei) but exchanges use 6-8 for trading. |
| Price (USD/crypto) | 8 decimals | High precision for rate calculations |

**Sources:**
- BTC uses 8 decimals (satoshi = 0.00000001 BTC)
- ETH technically has 18 decimals (wei) but major exchanges like Kraken use 6-8 for trading pairs
- See: [Gemini Crypto Denominations](https://www.gemini.com/cryptopedia/satoshi-value-gwei-to-ether-to-wei-converter-eth-gwei)

## Code Organization

- **Models**: `app/Models/` - User, Symbol, Asset, Order, Trade
- **Controllers**: `app/Http/Controllers/`
- **Migrations**: `database/migrations/`
- **Routes**: `routes/api.php` - API endpoints
