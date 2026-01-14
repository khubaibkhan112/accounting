# Accounting Software - User Manual

## Table of Contents
1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [User Roles](#user-roles)
4. [Chart of Accounts](#chart-of-accounts)
5. [Transactions](#transactions)
6. [Journal Entries](#journal-entries)
7. [Ledger View](#ledger-view)
8. [Reports](#reports)
9. [Export Features](#export-features)
10. [Customer Management](#customer-management)
11. [Employee Management](#employee-management)
12. [Vehicle Management](#vehicle-management)

## Introduction

This accounting software is designed to help you manage your financial records efficiently. It supports double-entry bookkeeping, provides comprehensive reporting, and offers role-based access control.

## Getting Started

### Login
1. Navigate to the login page
2. Enter your email and password
3. Click "Login"

**Default Users:**
- Admin: `admin@example.com` / `password`
- Accountant: `accountant@example.com` / `password`
- Driver: `driver@example.com` / `password`

### Dashboard
After logging in, you'll see the dashboard with:
- Summary statistics
- Recent transactions
- Quick access to main features

## User Roles

### Administrator
- Full access to all features
- Can create and manage accounts
- Can create and modify transactions
- Can manage users and employees
- Can view all reports

### Accountant
- Can create and manage accounts
- Can create and modify transactions
- Can view all reports
- Cannot manage users

### Driver
- Can view transactions assigned to them
- Can view accounts (read-only)
- Limited access to reports

## Chart of Accounts

### Viewing Accounts
1. Navigate to **Accounts** from the sidebar
2. Use the toggle to switch between **Table** and **Tree** view
3. Use filters to search by:
   - Account code or name
   - Account type (Asset, Liability, Equity, Revenue, Expense)
   - Status (Active/Inactive)

### Creating an Account
1. Click **Add Account** button
2. Fill in the required fields:
   - **Account Code**: Unique identifier (e.g., 1000, 2000)
   - **Account Name**: Descriptive name
   - **Account Type**: Select from Asset, Liability, Equity, Revenue, or Expense
   - **Parent Account**: (Optional) Select parent for hierarchical structure
   - **Opening Balance**: Initial balance
   - **Description**: Additional notes
3. Click **Create**

### Editing an Account
1. Click **Edit** next to the account
2. Modify the fields as needed
3. Click **Update**

**Note:** You cannot change the account type if the account has existing transactions.

### Deleting an Account
1. Click **Delete** next to the account
2. Confirm the deletion

**Note:** Accounts with transactions or child accounts cannot be deleted. Deactivate them instead.

## Transactions

### Creating a Transaction
1. Navigate to **Transactions**
2. Click **Add Transaction**
3. Fill in the form:
   - **Date**: Transaction date
   - **Account**: Select the account
   - **Description**: Transaction description
   - **Reference Number**: Optional reference
   - **Debit Amount** or **Credit Amount**: Enter one (not both)
   - **Customer**: (Optional) Select customer
   - **Employee**: (Optional) Select employee
   - **Vehicle**: (Optional) Select vehicle (if customer is selected)
4. Click **Create**

### Viewing Transactions
- Use filters to search by:
  - Account
  - Customer
  - Employee
  - Date range
  - Transaction type
- Click on a transaction to view details

### Editing/Deleting Transactions
- Only Admin and Accountant roles can modify transactions
- Click **Edit** or **Delete** on a transaction
- Confirm deletion when prompted

## Journal Entries

### Creating a Journal Entry
1. Navigate to **Journal Entries**
2. Click **Add Journal Entry**
3. Fill in:
   - **Entry Date**: Date of the journal entry
   - **Description**: Description of the entry
   - **Reference Number**: Optional reference
4. Add line items:
   - Click **Add Line Item**
   - Select **Account**
   - Enter **Debit Amount** or **Credit Amount**
   - Add description (optional)
5. Ensure **Total Debits = Total Credits**
6. Click **Create**

**Note:** The system will automatically balance the last line item if you use the "Auto Balance" feature.

### Viewing Journal Entries
- View all journal entries with their line items
- Filter by date range or account
- Click on an entry to view full details

## Ledger View

### Viewing Account Ledger
1. Navigate to **Ledger**
2. Select an **Account**
3. Optionally set a **Date Range**
4. Click **View Ledger**

The ledger shows:
- Opening balance
- All transactions in chronological order
- Running balance after each transaction
- Closing balance

### Exporting Ledger
- Click **Export to Excel** or **Export to PDF**
- The file will download with all ledger details

## Reports

### Trial Balance
1. Navigate to **Reports**
2. Select **Trial Balance**
3. Set the date (defaults to today)
4. Click **Generate Report**

Shows all accounts with their debit and credit balances.

### Balance Sheet
1. Select **Balance Sheet**
2. Set the date
3. Click **Generate Report**

Shows:
- Assets
- Liabilities
- Equity
- Total Assets = Total Liabilities + Equity

### Income Statement (Profit & Loss)
1. Select **Income Statement**
2. Set date range (defaults to current year)
3. Click **Generate Report**

Shows:
- Revenue accounts and total
- Expense accounts and total
- Net Income (Revenue - Expenses)

### Exporting Reports
All reports can be exported to:
- **Excel** format (.xlsx)
- **PDF** format (.pdf)

Click the respective export buttons on the report page.

### Printing Reports
1. Generate the report
2. Click **Print** button
3. The print-friendly view will open
4. Use browser's print function (Ctrl+P / Cmd+P)

## Export Features

### Excel Export
- Formatted spreadsheets with headers
- Includes all data and totals
- Ready for further analysis

### PDF Export
- Professional formatted documents
- Suitable for sharing and archiving
- Includes company information and dates

## Customer Management

### Creating a Customer
1. Navigate to **Customers**
2. Click **Add Customer**
3. Fill in customer details:
   - Customer Code (auto-generated or manual)
   - Company Name (for business) or First/Last Name (for individual)
   - Contact information
   - Payment terms
   - Assigned employee
4. Click **Create**

### Viewing Customer Transactions
1. Open a customer
2. Click **View Transactions**
3. See all transactions related to this customer

## Employee Management

### Creating an Employee
1. Navigate to **Employees**
2. Click **Add Employee**
3. Fill in employee details
4. Optionally create a user account for the employee
5. Click **Create**

### Employee Transactions
- View all transactions created by an employee
- Filter transactions by employee

## Vehicle Management

### Adding Vehicles to Customers
1. Open a customer
2. Click **Add Vehicle**
3. Enter:
   - Vehicle Number
   - Chassis Number
4. Click **Save**

### Using Vehicles in Transactions
- When creating a transaction for a customer
- Select the customer first
- Then select the vehicle (if applicable)
- This links the transaction to the specific vehicle

## Tips and Best Practices

1. **Account Codes**: Use a consistent numbering system (e.g., 1000s for Assets, 2000s for Liabilities)

2. **Double-Entry**: Always ensure debits equal credits in journal entries

3. **Reference Numbers**: Use reference numbers to track invoices, receipts, or other documents

4. **Date Ranges**: Use date ranges when viewing ledgers or reports for specific periods

5. **Regular Backups**: Ensure your database is backed up regularly

6. **Review Reports**: Regularly review Trial Balance to ensure accounts are balanced

## Troubleshooting

### Cannot Delete Account
- Account has transactions: Deactivate instead
- Account has child accounts: Delete or reassign children first

### Transaction Not Showing
- Check date range filters
- Verify account selection
- Check user permissions

### Report Not Balancing
- Verify all transactions are entered correctly
- Check for missing journal entries
- Review account types and balances

## Support

For technical support or questions, contact your system administrator.
