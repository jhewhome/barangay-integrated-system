# Project Gawad - User Manual

## Table of Contents
1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [System Overview](#system-overview)
4. [User Roles and Permissions](#user-roles-and-permissions)
5. [Module Guides](#module-guides)
   - [Home/Dashboard](#homedashboard)
   - [Residents](#residents)
   - [Medicine Inventory](#medicine-inventory)
   - [Transactions](#transactions)
   - [Complaints](#complaints)
   - [Visitors Log](#visitors-log)
   - [Access Management](#access-management)
   - [Settings](#settings)
6. [Common Operations](#common-operations)
7. [Reports and Exports](#reports-and-exports)
8. [Troubleshooting](#troubleshooting)

---

## Introduction

**Project Gawad** is a comprehensive Barangay Information System designed to streamline operations for Barangay Health Offices (BHO) and administrative personnel. The system manages residents, medicine inventory, transactions, complaints, and visitor logs.

### Key Features
- **Resident Management**: Complete resident database with profiles and records
- **Medicine Inventory**: Track medicines, stock levels, expiry dates, and dispense records
- **Transaction Management**: Handle clearances and business permits
- **Complaint Management**: Record and track resident complaints
- **Visitor Logging**: Track visitors to the barangay office
- **Financial Reports**: Generate spending logs, usage reports, and balance summaries
- **Role-Based Access**: Different access levels for administrators, secretaries, kagawad, and health workers

---

## Getting Started

### First-Time Login

When you first access the system, default accounts are automatically created:

| Username | Password | Role | Access Level |
|----------|----------|------|--------------|
| admin | P@ssw0rd123! | Administrator | Full system access |
| secretary | P@ssw0rd123! | Barangay Secretary | Most modules except system settings |
| kagawad | P@ssw0rd123! | Kagawad | Most modules except system settings |
| staff | P@ssw0rd123! | Health Worker / Staff | Limited - can dispense medicines and view transactions only |

**⚠️ IMPORTANT**: Change these default passwords immediately after first login for security.

### Logging In

1. Open your web browser
2. Navigate to: `http://localhost:5003` (or the configured port)
3. Enter your username and password
4. Click "Login" or press Enter
5. You will be redirected to the Dashboard

### Navigation

The system uses a sidebar navigation menu on the left side:
- **Home**: Dashboard with overview statistics
- **Residents**: Manage resident records
- **Medicine Inventory**: Medicine management and reports
- **Transactions**: Clearances and business permits
- **Complaints**: Complaint management
- **Visitors Log**: Track office visitors
- **System**: Access Management and Settings (Admin/Secretary/Kagawad only)

---

## System Overview

### Dashboard

The Dashboard provides an overview of:
- Total residents
- Medicine inventory statistics
- Recent transactions
- System alerts (low stock, expiring medicines)

### Main Modules

1. **Residents**: Complete resident database management
2. **Medicine Inventory**: Comprehensive medicine tracking and reporting
3. **Transactions**: Clearances and business permits
4. **Complaints**: Complaint tracking and management
5. **Visitors Log**: Visitor registration and tracking
6. **Access Management**: User account management (Admin/Secretary/Kagawad only)
7. **Settings**: System configuration (Admin/Secretary/Kagawad only)

---

## User Roles and Permissions

### Administrator
- **Full Access**: All modules and features
- Can manage users, change passwords, and configure system settings
- Can add, edit, and delete medicines and stock
- Can delete transactions and stock records
- Access to all financial reports

### Barangay Secretary
- **Most Modules**: Residents, Medicines, Transactions, Complaints, Visitors Log
- Can add, edit medicines and stock
- Cannot delete transactions or stock records
- Cannot access Access Management or Settings
- Access to financial reports

### Kagawad
- **Most Modules**: Same as Secretary
- Can add, edit medicines and stock
- Cannot delete transactions or stock records
- Cannot access Access Management or Settings
- Access to financial reports

### Health Worker / Staff
- **Limited Access**: 
  - Can dispense medicines
  - Can view medicine transactions
  - Can view dispensed logs (own transactions only)
  - Cannot add new medicines
  - Cannot add stock
  - Cannot edit or delete medicines
  - Cannot access financial reports
  - Cannot access Access Management or Settings

---

## Module Guides

### Home/Dashboard

The Dashboard provides a quick overview of system statistics and recent activities.

**Features:**
- Total residents count
- Medicine inventory summary
- Low stock alerts
- Expiring medicines alerts
- Recent transactions

---

### Residents

Manage the complete resident database for the barangay.

#### Viewing Residents
1. Click "Residents" in the sidebar
2. Browse the list of residents
3. Use the search box to find specific residents
4. Click on a resident to view full details

#### Adding a New Resident
1. Click "Residents" → "Add New" (or "+" button)
2. Fill in required information:
   - Full Name
   - Date of Birth
   - Gender
   - Address
   - Contact Information
   - Other relevant details
3. Click "Save" or "Submit"

#### Editing a Resident
1. Find the resident in the list
2. Click on the resident to view details
3. Click "Edit" button
4. Make your changes
5. Click "Save" or "Update"

#### Resident Profile
Each resident has a detailed profile including:
- Personal information
- Address details
- Contact information
- Transaction history
- Related records

---

### Medicine Inventory

The Medicine Inventory module is the core feature for managing medical supplies.

#### Medicine List

**Viewing Medicines:**
1. Click "Medicine Inventory" → "Medicine List"
2. View all medicines with:
   - Name and category
   - Current stock count
   - Unit of measure
   - Status indicators (Out of Stock, Low Stock, Expiring Soon)

**Status Indicators:**
- 🔴 **Red Badge**: Out of Stock
- 🟠 **Orange Badge**: Low Stock (below minimum level)
- 🟡 **Yellow Badge**: Expiring Soon (within 30 days)

**Inventory Summary Cards:**
- **Total Stock Received**: Total pieces received (all time)
- **Total Dispensed**: Total pieces dispensed (all time)
- **Remaining Stock**: Current inventory count

**Filtering:**
- Filter by Status: All, Active, Inactive, Out of Stock, Low Stock, Expiring Soon
- Search by medicine name

#### Adding a New Medicine
*(Available to Admin, Secretary, Kagawad only)*

1. Click "Medicine Inventory" → "Add Medicine"
2. Fill in required information:
   - **Medicine Name**: Full name of the medicine
   - **Category**: Select from dropdown (e.g., Antibiotic, Pain Reliever, etc.)
   - **Unit of Measure**: Box, Bottle, Tablet, Capsule, etc.
   - **Unit Price**: Price per box/bottle (not per piece)
   - **Minimum Stock Level**: Alert threshold
   - **Description/Notes**: Additional information
3. Click "Save"

#### Adding Stock
*(Available to Admin, Secretary, Kagawad, Health Worker/Staff)*

1. Go to Medicine List
2. Click "Add Stock" button on the medicine row
3. Fill in stock details:
   - **Input Method**: Select "Boxes" or "Bottles"
   - **Number of Boxes/Bottles**: Enter quantity
   - **Pieces per Box/Bottle**: Enter pieces per unit
   - **Total Pieces**: Automatically calculated
   - **Cost per Unit**: Price per box/bottle
   - **Supplier**: Select from dropdown (Barangay, LGU, DOH, Donors/Private Organizations, Other)
   - **Batch Number**: Optional
   - **Lot Number**: Optional
   - **Expiry Date**: When the medicine expires
   - **Received Date**: Date stock was received
   - **Notes**: Additional information
4. Review the **Total Price Summary**:
   - Shows calculation: Unit Price × Number of Boxes/Bottles
   - Displays total amount
5. Click "Add Stock"

**Important Notes:**
- Unit Price is per box/bottle, NOT per piece
- Total Amount = Unit Price × Number of Boxes/Bottles
- The system automatically creates a StockIn transaction

#### Dispensing Medicine
*(Available to all users)*

1. Go to Medicine List
2. Click "Dispense" button on the medicine row
3. Select stock batch (if multiple batches available)
4. Enter dispense details:
   - **Quantity**: Number of pieces to dispense
   - **Input Method**: Boxes, Bottles, or Pieces
   - **Recipient Name**: Who received the medicine
   - **Reason**: Purpose of dispense
   - **Notes**: Additional information
5. Click "Dispense"

**Note**: Health Worker/Staff can only dispense medicines. They cannot add new medicines or stock.

#### Viewing Medicine Details

1. Click on a medicine name in the Medicine List
2. View detailed information:
   - Medicine information
   - Stock batches with expiry dates
   - Transaction history
   - Statistics (total dispensed, stock in, etc.)

#### Low Stock

**Viewing Low Stock Items:**
1. Click "Medicine Inventory" → "Low Stock"
2. View all medicines with stock below minimum level
3. Medicines are automatically flagged when:
   - Current Stock ≤ Minimum Stock Level

**Actions:**
- Click "Add Stock" to replenish inventory
- Click "View Stocks" to see batch details

#### Expiring Soon

**Viewing Expiring Medicines:**
1. Click "Medicine Inventory" → "Expiring Soon"
2. View medicines expiring within 30 days
3. Each batch shows:
   - Medicine name
   - Batch/Lot number
   - Expiry date
   - Days until expiry
   - Quantity remaining

**Actions:**
- Record action taken (discard, use, etc.)
- Add notes about the action

#### Transactions

**Viewing All Transactions:**
1. Click "Medicine Inventory" → "Transactions"
2. View all medicine transactions:
   - Stock In
   - Dispensed
   - Stock Out
   - Adjustments
3. Filter by:
   - Medicine
   - Transaction Type
   - Date Range
   - Recipient Name

**Transaction Details:**
- Transaction type and date
- Medicine and quantity
- Unit price and total amount
- Recipient information
- Created by user

**Editing Transactions:**
*(Admin only, for StockIn transactions only)*
1. Find the transaction
2. Click "Edit"
3. Modify quantity or other details
4. Click "Save"

**Deleting Transactions:**
*(Admin only)*
1. Find the transaction
2. Click "Delete"
3. Confirm deletion
4. **Note**: Deleting a transaction automatically adjusts stock levels

#### Dispensed Log

**Viewing Dispensed Records:**
1. Click "Medicine Inventory" → "Dispensed Log"
2. View all dispensed transactions
3. Filter by:
   - Medicine
   - Date Range
   - Recipient Name
   - Created By (for Admin - shows all users; for others - shows only own transactions)

**Export:**
- Click "Export CSV" to download dispensed log

#### Financial Reports

*(Available to Admin, Secretary, Kagawad only)*

##### Stock Status Report
1. Click "Medicine Inventory" → "Stock Status"
2. View current stock levels vs minimum requirements
3. Shows status for each medicine

##### Usage Value Report
1. Click "Medicine Inventory" → "Usage Value"
2. Set date range
3. Click "Generate"
4. View dispensed medicines with:
   - Quantity dispensed
   - Unit price
   - Total value
5. Export to CSV if needed

##### Spending Log
1. Click "Medicine Inventory" → "Spending Log"
2. Set date range (default: last 30 days)
3. Click "Generate"
4. View all stock purchases with:
   - Medicine name
   - Supplier
   - Batch/Lot number
   - Quantity
   - Unit cost
   - **Total Cost** (matches the amount from Add Stock page)
   - Received date
   - Expiry date
5. **Delete Stock Record** (Admin only):
   - Click the red trash icon to delete a stock record
   - Confirmation required
   - Used to clean up incorrect entries
6. Export to CSV if needed

##### Balance Summary
1. Click "Medicine Inventory" → "Balance Summary"
2. Set date range
3. Click "Generate"
4. View:
   - Total Purchases (from Spending Log)
   - Total Usage Value (from dispensed transactions)
   - Net Balance (Purchases - Usage)
5. Export to CSV if needed

---

### Transactions

Manage barangay transactions including clearances and business permits.

#### Transaction Record
1. Click "Transactions" → "Transaction Record"
2. View all transactions (clearances and business permits)
3. Filter and search transactions

#### Clearance
1. Click "Transactions" → "Clearance"
2. Fill in clearance application form
3. Enter resident information
4. Select clearance type
5. Submit application
6. Generate clearance document

#### Business Permit
1. Click "Transactions" → "Business Permit"
2. Fill in business permit application
3. Enter business information
4. Submit application
5. Generate business permit document

---

### Complaints

Manage and track resident complaints.

#### Viewing Complaints
1. Click "Complaints" in the sidebar
2. View list of all complaints
3. Filter by status, date, or search

#### Adding a Complaint
1. Click "Add New" or "+" button
2. Fill in complaint details:
   - Complainant information
   - Respondent information
   - Complaint description
   - Date and location
3. Click "Save"

#### Managing Complaints
- View complaint details
- Update status
- Add notes and updates
- Track resolution progress

---

### Visitors Log

Track visitors to the barangay office.

#### Logging a Visitor
1. Click "Visitors Log" in the sidebar
2. Click "Add New" or "+" button
3. Fill in visitor information:
   - Name
   - Purpose of visit
   - Time in
   - Contact information
4. Click "Save"

#### Viewing Visitor Logs
- View all visitor entries
- Filter by date
- Search visitors
- Mark time out for visitors

---

### Access Management

*(Available to Admin, Secretary, Kagawad only)*

Manage user accounts and access.

#### Viewing Users
1. Click "Access Management" in the sidebar
2. View list of all users
3. See user roles and status

#### Adding a User
1. Click "Add New" or "+" button
2. Fill in user information:
   - Username
   - Password
   - Role (Administrator, Barangay Secretary, Kagawad, Health Worker / Staff)
   - Full Name
   - Email (optional)
3. Click "Save"

#### Editing a User
1. Find the user in the list
2. Click on the user
3. Click "Edit"
4. Update information
5. Click "Save"

#### Changing Password
*(Administrator only)*
1. Click "Change Password" in the sidebar
2. Enter current password
3. Enter new password
4. Confirm new password
5. Click "Change Password"

---

### Settings

*(Available to Admin, Secretary, Kagawad only)*

Configure system settings.

#### Official Signatory
1. Click "Settings" → "Official Signatory"
2. Configure signatory information:
   - Name
   - Position
   - Signature image
3. Used for official documents (clearances, permits)

#### QR Code Verification URL
1. Click "Settings" → "QR Code Verification URL"
2. Set the URL for QR code verification
3. Used for document verification

---

## Common Operations

### Searching
- Most modules have a search box
- Type keywords to filter results
- Search works on names, IDs, and other text fields

### Filtering
- Use dropdown filters to narrow results
- Common filters: Status, Date Range, Category
- Combine multiple filters for precise results

### Exporting Data
- Many reports have "Export CSV" buttons
- Click to download data as CSV file
- Open in Excel or other spreadsheet software

### Printing
- Use browser's Print function (Ctrl+P)
- Reports are formatted for printing
- Adjust print settings as needed

---

## Reports and Exports

### Medicine Inventory Reports

1. **Stock Status Report**
   - Current stock levels
   - Minimum stock requirements
   - Status indicators

2. **Usage Value Report**
   - Dispensed medicines by date range
   - Quantity and value
   - CSV export available

3. **Spending Log**
   - Stock purchases by date range
   - Supplier information
   - Total costs
   - CSV export available
   - Admin can delete incorrect entries

4. **Balance Summary**
   - Total purchases vs usage
   - Net balance
   - CSV export available

### Exporting Reports

1. Generate the report with desired filters
2. Click "Export CSV" button
3. File downloads automatically
4. Open in Excel or spreadsheet software

---

## Troubleshooting

### Login Issues

**Problem**: Cannot log in
- **Solution**: Verify username and password are correct
- Check if account is active
- Contact administrator if locked out

**Problem**: Forgot password
- **Solution**: Contact administrator to reset password
- Administrator can change password in Access Management

### Medicine Inventory Issues

**Problem**: Cannot add stock
- **Solution**: 
  - Verify you have permission (Admin, Secretary, Kagawad, or Health Worker/Staff)
  - Check if medicine exists
  - Ensure all required fields are filled

**Problem**: Stock count incorrect
- **Solution**:
  - Check transaction history
  - Verify all dispenses are recorded
  - Review stock adjustments

**Problem**: Cannot see financial reports
- **Solution**:
  - Verify user role (Admin, Secretary, or Kagawad only)
  - Health Worker/Staff cannot access financial reports

### General Issues

**Problem**: Page not loading
- **Solution**:
  - Refresh the page (F5)
  - Check internet connection
  - Clear browser cache
  - Try different browser

**Problem**: Data not saving
- **Solution**:
  - Check all required fields are filled
  - Verify you have permission
  - Check for error messages
  - Try again

**Problem**: Cannot delete records
- **Solution**:
  - Verify you have permission (Admin only for most deletions)
  - Some records cannot be deleted if they have related data
  - Check for error messages

---

## Best Practices

### Medicine Inventory

1. **Regular Stock Checks**
   - Review Low Stock page regularly
   - Check Expiring Soon weekly
   - Update stock levels promptly

2. **Accurate Data Entry**
   - Enter correct quantities
   - Verify expiry dates
   - Record supplier information

3. **Transaction Recording**
   - Record all dispenses immediately
   - Include recipient information
   - Add notes when necessary

4. **Report Generation**
   - Generate financial reports monthly
   - Review spending vs usage
   - Keep records for auditing

### User Management

1. **Password Security**
   - Change default passwords immediately
   - Use strong passwords
   - Don't share passwords

2. **Role Assignment**
   - Assign appropriate roles
   - Limit access as needed
   - Review user access regularly

### Data Management

1. **Regular Backups**
   - Ensure database backups are performed
   - Keep backup records
   - Test restore procedures

2. **Data Accuracy**
   - Verify information before saving
   - Update records promptly
   - Review data regularly

---

## Support and Contact

For technical support or questions:
- Contact your system administrator
- Refer to this user manual
- Check system documentation

---

## Appendix

### Keyboard Shortcuts

- **Ctrl + F**: Search on page
- **F5**: Refresh page
- **Ctrl + P**: Print
- **Enter**: Submit form (when focused on input)

### Browser Compatibility

Recommended browsers:
- Google Chrome (latest version)
- Microsoft Edge (latest version)
- Mozilla Firefox (latest version)

### System Requirements

- Windows 10 or later
- MongoDB installed and running
- Modern web browser
- Internet connection (for initial setup)

---

**Last Updated**: [Current Date]
**Version**: 1.0





