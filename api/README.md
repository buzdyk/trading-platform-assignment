# HOUSEHOLD API

Laravel 12 backend API for the HOUSEHOLD resource tracker application.

## Requirements

- PHP 8.2+
- Composer

## Development Commands

```bash
# Development server
composer dev
# Or directly:
php artisan serve

# Run tests
composer test
# Or directly:
php artisan test

# Run database migrations
php artisan migrate

# Fresh migration (drop all tables and re-migrate)
php artisan migrate:fresh

# Create a new migration
php artisan make:migration create_table_name

# Create a new model
php artisan make:model ModelName

# Create a controller
php artisan make:controller ControllerName

# Run code style fixer (Laravel Pint)
vendor/bin/pint

# Run PHPUnit tests directly
vendor/bin/phpunit

# Laravel Tinker (REPL)
php artisan tinker

# Clear application cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## Database Structure

Current migrations establish:
- **users**: Standard Laravel user authentication

## Code Organization

- **Models**: `app/Models/` - Eloquent models
- **Controllers**: `app/Http/Controllers/` - HTTP request handlers
- **Migrations**: `database/migrations/` - Database schema definitions
- **Routes**: `routes/web.php` - Web routes, `routes/console.php` - Artisan commands
- **Config**: `config/` - Application configuration files
- **Tests**: `tests/` - PHPUnit tests

## Laravel Conventions

- Use Eloquent ORM for database operations
- Follow PSR-4 autoloading: `App\` namespace maps to `app/` directory
- Migration naming: `YYYY_MM_DD_HHMMSS_descriptive_name.php`
- Use resource controllers for RESTful operations
- Leverage Laravel's built-in authentication system
