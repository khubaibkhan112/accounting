# Accounting Software

A comprehensive accounting software built with Laravel and Vue.js, implementing double-entry bookkeeping principles.

## Features

- **Chart of Accounts**: Hierarchical account structure with account codes
- **Transaction Entry**: Record financial transactions with debit/credit entries
- **Journal Entries**: Multi-line journal entries with automatic balance validation
- **Ledger View**: Chronological view of account transactions with running balances
- **Financial Reports**:
  - Trial Balance
  - Balance Sheet
  - Income Statement (Profit & Loss)
- **Export Features**: Export to Excel and PDF formats
- **Print Functionality**: Print-friendly views for all reports
- **Customer Management**: Track customers and their transactions
- **Employee Management**: Manage employees and assign to customers
- **Vehicle Management**: Link vehicles to customers and transactions
- **Role-Based Access Control**: Admin, Accountant, Driver, and Viewer roles
- **User Authentication**: Secure login with password reset functionality

## Technology Stack

- **Backend**: Laravel 12.x, PHP 8.2+
- **Frontend**: Vue.js 3, Vue Router, Axios, Tailwind CSS
- **Database**: MySQL 8.0+
- **Export**: Laravel Excel, DomPDF

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and npm
- MySQL 8.0 or higher

### Setup Steps

1. **Clone the repository**
```bash
git clone <repository-url>
cd accounting-software
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Install Node dependencies**
```bash
npm install
```

4. **Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Update `.env` file** with your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=accounting_db
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

6. **Run migrations**
```bash
php artisan migrate
```

7. **Seed initial data**
```bash
php artisan db:seed
```

This creates:
- Default users (admin, accountant, driver)
- Default chart of accounts

8. **Build frontend assets**
```bash
npm run build
```

9. **Start development server**
```bash
php artisan serve
npm run dev
```

## Default Login Credentials

- **Admin**: `admin@example.com` / `password`
- **Accountant**: `accountant@example.com` / `password`
- **Driver**: `driver@example.com` / `password`

**Important:** Change these passwords after first login!

## Documentation

- [User Manual](docs/USER_MANUAL.md) - Complete user guide
- [Technical Documentation](docs/TECHNICAL_DOCUMENTATION.md) - API endpoints, database schema, code structure
- [Deployment Guide](docs/DEPLOYMENT.md) - Production deployment instructions

## API Documentation

All API endpoints require authentication. See [Technical Documentation](docs/TECHNICAL_DOCUMENTATION.md#api-endpoints) for complete API reference.

Base URL: `/api`

## Development

### Running Tests
```bash
php artisan test
```

### Code Style
```bash
composer pint
```

### Building Assets
```bash
# Development
npm run dev

# Production
npm run build
```

## Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/    # API controllers
│   ├── Middleware/         # Custom middleware
│   └── Requests/           # Form request validators
├── Models/                 # Eloquent models
└── Exports/                # Excel export classes

database/
├── migrations/             # Database migrations
└── seeders/               # Database seeders

resources/
├── js/                     # Vue.js frontend
│   ├── components/         # Vue components
│   ├── composables/       # Vue composables
│   ├── layouts/           # Layout components
│   └── pages/             # Page components
└── views/                 # Blade templates

routes/
├── api.php                 # API routes
└── web.php                # Web routes
```

## Security Features

- CSRF protection
- Input sanitization
- Role-based access control
- Password hashing
- Session-based authentication
- SQL injection prevention (Eloquent ORM)

## Performance Optimizations

- Database indexing on frequently queried columns
- Query optimization with eager loading
- Caching for reports (5 minutes)
- Stored procedures for complex reports
- Select only required columns

## License

[Your License Here]

## Support

For support, email support@example.com or create an issue in the repository.
