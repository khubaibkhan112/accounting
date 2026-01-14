# Accounting Software - Technical Documentation

## Table of Contents
1. [System Overview](#system-overview)
2. [Technology Stack](#technology-stack)
3. [Project Structure](#project-structure)
4. [Database Schema](#database-schema)
5. [API Endpoints](#api-endpoints)
6. [Models](#models)
7. [Controllers](#controllers)
8. [Authentication & Authorization](#authentication--authorization)
9. [Validation](#validation)
10. [Caching](#caching)
11. [Export Features](#export-features)
12. [Deployment](#deployment)

## System Overview

This is a Laravel-based accounting software with Vue.js frontend, implementing double-entry bookkeeping principles.

## Technology Stack

### Backend
- **Framework**: Laravel 12.x
- **PHP**: 8.2+
- **Database**: MySQL 8.0+
- **Cache**: Laravel Cache (configurable: database, file, redis)

### Frontend
- **Framework**: Vue.js 3 (Composition API)
- **Router**: Vue Router 4
- **HTTP Client**: Axios
- **Table Component**: vxe-table
- **Styling**: Tailwind CSS

### Packages
- **Excel Export**: maatwebsite/excel
- **PDF Export**: barryvdh/laravel-dompdf

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── AccountController.php
│   │   │   ├── CustomerController.php
│   │   │   ├── JournalEntryController.php
│   │   │   ├── LedgerController.php
│   │   │   ├── ReportController.php
│   │   │   ├── TransactionController.php
│   │   │   └── VehicleController.php
│   │   ├── Auth/
│   │   │   └── AuthController.php
│   │   └── EmployeeController.php
│   ├── Middleware/
│   │   └── CheckRole.php
│   └── Requests/
│       ├── BaseFormRequest.php
│       ├── StoreAccountRequest.php
│       ├── UpdateAccountRequest.php
│       ├── StoreTransactionRequest.php
│       ├── UpdateTransactionRequest.php
│       ├── StoreJournalEntryRequest.php
│       └── UpdateJournalEntryRequest.php
├── Models/
│   ├── Account.php
│   ├── Customer.php
│   ├── Employee.php
│   ├── JournalEntry.php
│   ├── JournalEntryItem.php
│   ├── Transaction.php
│   ├── User.php
│   └── Vehicle.php
└── Exports/
    ├── BalanceSheetExport.php
    ├── IncomeStatementExport.php
    ├── LedgerExport.php
    └── TrialBalanceExport.php

database/
├── migrations/
├── seeders/
│   └── DatabaseSeeder.php
└── factories/

resources/
├── js/
│   ├── components/
│   ├── composables/
│   ├── layouts/
│   ├── pages/
│   └── router/
└── views/
    └── exports/
```

## Database Schema

### Tables

#### accounts
- `id` (bigint, primary key)
- `account_code` (string, unique)
- `account_name` (string)
- `account_type` (enum: asset, liability, equity, revenue, expense)
- `parent_account_id` (bigint, nullable, foreign key)
- `opening_balance` (decimal)
- `description` (text, nullable)
- `is_active` (boolean)
- `created_at`, `updated_at` (timestamps)

#### transactions
- `id` (bigint, primary key)
- `date` (date)
- `account_id` (bigint, foreign key)
- `customer_id` (bigint, nullable, foreign key)
- `employee_id` (bigint, nullable, foreign key)
- `vehicle_id` (bigint, nullable, foreign key)
- `description` (text)
- `debit_amount` (decimal)
- `credit_amount` (decimal)
- `reference_number` (string, nullable)
- `transaction_type` (string, nullable)
- `running_balance` (decimal)
- `created_by` (bigint, foreign key to users)
- `created_at`, `updated_at` (timestamps)

#### journal_entries
- `id` (bigint, primary key)
- `entry_date` (date)
- `description` (text)
- `reference_number` (string, nullable)
- `total_debit` (decimal)
- `total_credit` (decimal)
- `created_by` (bigint, foreign key to users)
- `created_at`, `updated_at` (timestamps)

#### journal_entry_items
- `id` (bigint, primary key)
- `journal_entry_id` (bigint, foreign key)
- `account_id` (bigint, foreign key)
- `debit_amount` (decimal)
- `credit_amount` (decimal)
- `description` (text, nullable)
- `created_at`, `updated_at` (timestamps)

#### customers
- `id` (bigint, primary key)
- `customer_code` (string, unique)
- `company_name` (string, nullable)
- `first_name` (string, nullable)
- `last_name` (string, nullable)
- `email` (string, nullable)
- `phone` (string, nullable)
- `customer_type` (enum: individual, business)
- `payment_terms` (string)
- `assigned_to` (bigint, nullable, foreign key to employees)
- `current_balance` (decimal)
- `is_active` (boolean)
- `created_at`, `updated_at` (timestamps)

#### employees
- `id` (bigint, primary key)
- `employee_id` (string, unique)
- `first_name` (string)
- `last_name` (string)
- `email` (string, nullable, unique)
- `phone` (string, nullable)
- `position` (string, nullable)
- `department` (string, nullable)
- `employment_type` (enum: full-time, part-time, contract, intern)
- `hire_date` (date, nullable)
- `is_active` (boolean)
- `user_id` (bigint, nullable, foreign key to users)
- `created_at`, `updated_at` (timestamps)

#### vehicles
- `id` (bigint, primary key)
- `customer_id` (bigint, foreign key)
- `vehicle_number` (string)
- `chassis_number` (string, nullable)
- `created_at`, `updated_at` (timestamps)

#### users
- `id` (bigint, primary key)
- `name` (string)
- `email` (string, unique)
- `password` (string, hashed)
- `role` (enum: admin, accountant, driver, viewer)
- `is_active` (boolean)
- `remember_token` (string, nullable)
- `email_verified_at` (timestamp, nullable)
- `created_at`, `updated_at` (timestamps)

### Indexes
- `accounts`: account_code (unique), account_type, parent_account_id, is_active
- `transactions`: account_id, date, customer_id, employee_id, created_by, transaction_type
- `journal_entries`: entry_date, created_by
- `journal_entry_items`: journal_entry_id, account_id
- `customers`: customer_code (unique), assigned_to, is_active
- `employees`: employee_id (unique), user_id, is_active
- `vehicles`: customer_id, vehicle_number

## API Endpoints

All API routes require authentication (`auth` middleware).

Base URL: `/api`

### Authentication
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout (requires auth)
- `GET /api/auth/me` - Get current user (requires auth)
- `POST /api/auth/forgot-password` - Request password reset
- `POST /api/auth/reset-password` - Reset password

### Accounts
- `GET /api/accounts` - List accounts (paginated, filterable)
- `GET /api/accounts/tree` - Get account tree structure
- `GET /api/accounts/types` - Get account types
- `GET /api/accounts/type/{type}` - Get accounts by type
- `GET /api/accounts/{account}` - Get account details
- `GET /api/accounts/{account}/balance-summary` - Get account balance summary
- `POST /api/accounts` - Create account (requires admin/accountant)
- `PUT /api/accounts/{account}` - Update account (requires admin/accountant)
- `DELETE /api/accounts/{account}` - Delete account (requires admin/accountant)

### Transactions
- `GET /api/transactions` - List transactions (paginated, filterable)
- `GET /api/transactions/{transaction}` - Get transaction details
- `POST /api/transactions` - Create transaction (requires admin/accountant)
- `PUT /api/transactions/{transaction}` - Update transaction (requires admin/accountant)
- `DELETE /api/transactions/{transaction}` - Delete transaction (requires admin/accountant)

### Journal Entries
- `GET /api/journal-entries` - List journal entries (paginated, filterable)
- `GET /api/journal-entries/{journal_entry}` - Get journal entry details
- `POST /api/journal-entries` - Create journal entry (requires admin/accountant)
- `PUT /api/journal-entries/{journal_entry}` - Update journal entry (requires admin/accountant)
- `DELETE /api/journal-entries/{journal_entry}` - Delete journal entry (requires admin/accountant)

### Ledger
- `GET /api/ledger` - Get ledger (requires account_id parameter)
- `GET /api/ledger/account/{account}` - Get ledger for specific account
- `GET /api/ledger/export/excel` - Export ledger to Excel
- `GET /api/ledger/export/pdf` - Export ledger to PDF

### Reports
- `GET /api/reports/trial-balance` - Generate trial balance report
- `GET /api/reports/trial-balance/export/excel` - Export trial balance to Excel
- `GET /api/reports/trial-balance/export/pdf` - Export trial balance to PDF
- `GET /api/reports/balance-sheet` - Generate balance sheet
- `GET /api/reports/balance-sheet/export/excel` - Export balance sheet to Excel
- `GET /api/reports/balance-sheet/export/pdf` - Export balance sheet to PDF
- `GET /api/reports/income-statement` - Generate income statement
- `GET /api/reports/income-statement/export/excel` - Export income statement to Excel
- `GET /api/reports/income-statement/export/pdf` - Export income statement to PDF

### Customers
- `GET /api/customers` - List customers (paginated, filterable)
- `GET /api/customers/generate-code` - Generate customer code
- `GET /api/customers/{customer}` - Get customer details
- `GET /api/customers/{customer}/transactions` - Get customer transactions
- `POST /api/customers` - Create customer
- `PUT /api/customers/{customer}` - Update customer
- `DELETE /api/customers/{customer}` - Delete customer

### Employees
- `GET /api/employees` - List employees (paginated, filterable)
- `GET /api/employees/generate-id` - Generate employee ID
- `GET /api/employees/departments` - Get departments list
- `GET /api/employees/{employee}` - Get employee details
- `POST /api/employees` - Create employee
- `PUT /api/employees/{employee}` - Update employee
- `DELETE /api/employees/{employee}` - Delete employee

### Vehicles
- `GET /api/vehicles` - List vehicles (filterable by customer_id)
- `GET /api/vehicles/{vehicle}` - Get vehicle details
- `POST /api/vehicles` - Create vehicle
- `PUT /api/vehicles/{vehicle}` - Update vehicle
- `DELETE /api/vehicles/{vehicle}` - Delete vehicle

## Models

### Account Model
**Relationships:**
- `parentAccount()` - Belongs to Account
- `childAccounts()` - Has many Account
- `transactions()` - Has many Transaction

**Scopes:**
- `active()` - Active accounts only
- `ofType($type)` - Filter by account type
- `parentAccounts()` - Accounts without parent
- `childrenOf($parentId)` - Children of specific parent
- `search($term)` - Search by code or name

**Methods:**
- `current_balance` (accessor) - Calculate current balance
- `total_debits` (accessor) - Total debit transactions
- `total_credits` (accessor) - Total credit transactions
- `hasTransactions()` - Check if account has transactions
- `hasChildren()` - Check if account has child accounts
- `getTreeStructure()` - Static method to get tree structure

### Transaction Model
**Relationships:**
- `account()` - Belongs to Account
- `customer()` - Belongs to Customer
- `employee()` - Belongs to Employee
- `vehicle()` - Belongs to Vehicle
- `creator()` - Belongs to User

**Methods:**
- Balance calculations handled via model events

### JournalEntry Model
**Relationships:**
- `items()` - Has many JournalEntryItem
- `creator()` - Belongs to User

**Validation:**
- Total debits must equal total credits

## Controllers

All API controllers extend `App\Http\Controllers\Controller` and return JSON responses.

### Common Patterns
- Use FormRequest classes for validation
- Implement role-based access control
- Use transactions for data integrity
- Return consistent JSON response format
- Handle errors gracefully

## Authentication & Authorization

### Authentication
- Session-based authentication
- CSRF protection enabled for web routes
- Password reset functionality

### Authorization
- Role-based access control (RBAC)
- Middleware: `CheckRole` middleware
- Roles: admin, accountant, driver, viewer

### Route Protection
- All API routes require `auth` middleware
- Modify operations require `role:admin,accountant` middleware
- View operations accessible to all authenticated users

## Validation

### Form Requests
- `BaseFormRequest` - Base class with input sanitization
- `StoreAccountRequest` - Account creation validation
- `UpdateAccountRequest` - Account update validation
- `StoreTransactionRequest` - Transaction creation validation
- `UpdateTransactionRequest` - Transaction update validation
- `StoreJournalEntryRequest` - Journal entry creation validation
- `UpdateJournalEntryRequest` - Journal entry update validation

### Input Sanitization
- Automatic trimming of whitespace
- Null byte removal
- HTML tag stripping
- Special character filtering based on field type

## Caching

### Report Caching
- Trial Balance: 5 minutes
- Balance Sheet: 5 minutes
- Income Statement: 5 minutes

### Static Data Caching
- Account Types: 1 hour

Cache keys include parameters for proper invalidation.

## Export Features

### Excel Export
- Uses `maatwebsite/excel` package
- Custom export classes for each report type
- Formatted with headers, styling, and totals

### PDF Export
- Uses `barryvdh/laravel-dompdf` package
- Blade templates for PDF generation
- Print-friendly formatting

## Deployment

See `DEPLOYMENT.md` for detailed deployment instructions.

## Environment Variables

Required environment variables:
- `APP_NAME`
- `APP_ENV`
- `APP_KEY`
- `APP_DEBUG`
- `APP_URL`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `CACHE_STORE`
- `SESSION_DRIVER`
- `MAIL_*` (for password reset)

See `.env.example` for complete list.
