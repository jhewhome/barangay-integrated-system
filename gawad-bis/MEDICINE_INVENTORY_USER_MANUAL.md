# Medicine Inventory Module - User Manual

## Table of Contents
1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [User Roles and Permissions](#user-roles-and-permissions)
4. [Medicine Management](#medicine-management)
5. [Stock Management](#stock-management)
6. [Dispensing Medicines](#dispensing-medicines)
7. [Monitoring and Alerts](#monitoring-and-alerts)
8. [Transactions](#transactions)
9. [Financial Reports](#financial-reports)
10. [Common Operations](#common-operations)
11. [Troubleshooting](#troubleshooting)

---

## Introduction

The **Medicine Inventory Module** is a comprehensive system designed to manage medical supplies for Barangay Health Offices (BHO). It tracks medicines from receipt to dispense, monitors stock levels, alerts for expiring items, and generates financial reports.

### Key Features
- **Medicine Catalog**: Complete database of medicines with categories, units, and pricing
- **Stock Management**: Track stock batches with expiry dates, lot numbers, and suppliers
- **Dispense Tracking**: Record all medicine dispenses with recipient information
- **Stock Alerts**: Automatic notifications for low stock and expiring medicines
- **Financial Reports**: Spending logs, usage reports, and balance summaries
- **Transaction History**: Complete audit trail of all stock movements

---

## Getting Started

### Accessing Medicine Inventory

1. Log in to the system
2. Click **"Medicine Inventory"** in the left sidebar
3. The menu will expand showing all available options

### Navigation Menu

The Medicine Inventory menu includes:
- **Medicine List**: View and manage all medicines
- **Add Medicine**: Create new medicine entries (Admin/Secretary/Kagawad only)
- **Low Stock**: View medicines below minimum stock level
- **Expiring Soon**: View medicines expiring within 30 days
- **Transactions**: View all stock movements
- **Dispensed Log**: View all dispensed records
- **Financial Reports** (Admin/Secretary/Kagawad only):
  - Stock Status
  - Usage Value
  - Spending Log
  - Balance Summary

---

## User Roles and Permissions

### Administrator
- ✅ Add, edit, and delete medicines
- ✅ Add stock to medicines
- ✅ Dispense medicines
- ✅ Edit and delete transactions
- ✅ Delete stock records (in Spending Log)
- ✅ Access all financial reports
- ✅ View all dispensed logs (all users)

### Barangay Secretary / Kagawad
- ✅ Add and edit medicines
- ✅ Add stock to medicines
- ✅ Dispense medicines
- ❌ Cannot delete medicines
- ❌ Cannot delete transactions
- ❌ Cannot delete stock records
- ✅ Access all financial reports
- ✅ View all dispensed logs (all users)

### Health Worker / Staff
- ❌ Cannot add new medicines
- ❌ Cannot add stock
- ✅ Can dispense medicines
- ✅ Can view medicine list
- ✅ Can view transactions
- ✅ Can view own dispensed logs only
- ❌ Cannot access financial reports
- ❌ Cannot edit or delete anything

---

## Medicine Management

### Viewing the Medicine List

1. Click **"Medicine Inventory"** → **"Medicine List"**
2. You'll see a table with all medicines showing:
   - Medicine name and category
   - Current stock count (in pieces)
   - Unit of measure (Box, Bottle, Tablet, etc.)
   - Status indicators

### Status Indicators

The system uses visual badges to indicate medicine status:

- 🔴 **Red Badge**: **Out of Stock** - No stock available
- 🟠 **Orange Badge**: **Low Stock** - Stock is below minimum level
- 🟡 **Yellow Badge**: **Expiring Soon** - Has batches expiring within 30 days
- ✅ **No Badge**: Normal stock level

### Inventory Summary Cards

At the top of the Medicine List page, you'll see three summary cards:

1. **Total Stock Received** (Blue)
   - Shows total pieces received (all time)
   - Counts only active medicines (excludes deleted)

2. **Total Dispensed** (Red)
   - Shows total pieces dispensed (all time)
   - Counts all dispensed transactions

3. **Remaining Stock** (Green)
   - Shows current inventory count
   - Calculated as: Received - Dispensed

### Filtering Medicines

**By Status:**
- Click the **Status** dropdown
- Select: All, Active, Inactive, Out of Stock, Low Stock, or Expiring Soon
- The list updates automatically

**By Search:**
- Type in the search box
- Search by medicine name
- Results filter as you type

### Adding a New Medicine

*(Available to Admin, Secretary, Kagawad only)*

1. Click **"Medicine Inventory"** → **"Add Medicine"**
2. Fill in the form:

   **Required Fields:**
   - **Medicine Name**: Full name of the medicine (e.g., "Paracetamol 500mg")
   - **Category**: Select from dropdown
     - Antibiotic
     - Pain Reliever
     - Antihistamine
     - Vitamins
     - First Aid
     - Other
   - **Unit of Measure**: How the medicine is packaged
     - Box
     - Bottle
     - Tablet
     - Capsule
     - Piece
     - Other

   **Optional but Recommended:**
   - **Unit Price**: Price per box/bottle (NOT per piece)
     - Example: If a box costs ₱100, enter 100
     - This is used for financial calculations
   - **Minimum Stock Level**: Alert threshold
     - When stock falls below this, it shows as "Low Stock"
     - Enter in pieces (e.g., 50 pieces)
   - **Description**: Additional notes about the medicine

3. Click **"Save"**
4. The medicine is added and you'll be redirected to the Medicine List

**Important Notes:**
- Unit Price is per box/bottle, NOT per individual piece
- Minimum Stock Level should be set based on typical usage
- You can edit these later if needed

### Viewing Medicine Details

1. Click on a medicine name in the Medicine List
2. View detailed information:

   **Medicine Information:**
   - Name, category, unit of measure
   - Unit price and minimum stock level
   - Description

   **Stock Batches:**
   - All stock batches for this medicine
   - Shows: Batch number, Lot number, Quantity, Expiry date, Supplier
   - Sorted by expiry date (soonest first)

   **Transaction History:**
   - Recent transactions (last 10)
   - All transactions link to view complete history

   **Statistics:**
   - Total Dispensed (pieces)
   - Total Stock In (pieces)
   - Total Stock Out (pieces)
   - Total Dispensed Value (amount)

### Editing a Medicine

*(Available to Admin, Secretary, Kagawad only)*

1. Go to Medicine List
2. Click on the medicine name
3. Click **"Edit"** button
4. Modify the information
5. Click **"Save"**

**Note**: You can update:
- Medicine name
- Category
- Unit of measure
- Unit price
- Minimum stock level
- Description

### Deleting a Medicine

*(Administrator only)*

1. Go to Medicine List
2. Find the medicine
3. Click **"Delete"** button
4. Confirm deletion
5. The medicine is soft-deleted (hidden but not permanently removed)

**Warning**: Deleting a medicine will hide it from the list. Related transactions remain in history.

---

## Stock Management

### Adding Stock to a Medicine

*(Available to Admin, Secretary, Kagawad, Health Worker/Staff)*

#### Step-by-Step Process

1. **Navigate to Medicine List**
   - Click "Medicine Inventory" → "Medicine List"

2. **Select Medicine**
   - Find the medicine you want to add stock to
   - Click **"Add Stock"** button on that row

3. **Choose Input Method**
   - Select how you want to enter the quantity:
     - **Boxes**: If medicine comes in boxes
     - **Bottles**: If medicine comes in bottles
   - The system will calculate total pieces automatically

4. **Enter Quantity Information**

   **If using Boxes:**
   - **Number of Boxes**: Enter how many boxes (e.g., 2)
   - **Pieces per Box**: Enter pieces in each box (e.g., 100)
   - **Total Pieces**: Automatically calculated (2 × 100 = 200 pieces)

   **If using Bottles:**
   - **Number of Bottles**: Enter how many bottles (e.g., 5)
   - **Pieces per Bottle**: Enter pieces in each bottle (e.g., 50)
   - **Total Pieces**: Automatically calculated (5 × 50 = 250 pieces)

5. **Enter Cost Information**
   - **Cost per Unit**: Enter price per box/bottle
     - Example: If each box costs ₱500, enter 500
     - This is the price per box/bottle, NOT per piece
   - The system will calculate total amount automatically

6. **Enter Stock Details**
   - **Supplier**: Select from dropdown
     - Barangay
     - LGU
     - DOH
     - Donors/Private Organizations
     - Other
   - **Batch Number**: Optional identifier
   - **Lot Number**: Optional lot identifier
   - **Expiry Date**: When the medicine expires (important!)
   - **Received Date**: Date stock was received (defaults to today)
   - **Notes**: Any additional information

7. **Review Total Price Summary**
   - The system shows:
     - **Calculation**: Unit Price × Number of Boxes/Bottles
     - **Total Amount**: Final cost
   - Example: ₱500 per box × 2 boxes = ₱1,000.00

8. **Save Stock**
   - Click **"Add Stock"** button
   - Stock is added and a StockIn transaction is created automatically

#### Important Notes About Adding Stock

**Unit Price vs Total Amount:**
- **Unit Price** = Price per box/bottle
- **Total Amount** = Unit Price × Number of Boxes/Bottles
- **NOT** Unit Price × Total Pieces

**Example:**
- Medicine: Paracetamol
- 2 boxes received
- 100 pieces per box = 200 total pieces
- Price: ₱500 per box
- **Total Amount** = ₱500 × 2 boxes = **₱1,000**
- **NOT** ₱500 × 200 pieces = ₱100,000 ❌

**Why This Matters:**
- The Spending Log uses this Total Amount
- Financial reports are based on this calculation
- Ensures accurate cost tracking

### Viewing Stock Batches

1. Click on a medicine name in Medicine List
2. Scroll to **"Stock Batches"** section
3. View all batches showing:
   - Batch Number
   - Lot Number
   - Quantity (pieces)
   - Expiry Date
   - Supplier
   - Received Date

**Stock Batch Information:**
- Batches are sorted by expiry date (soonest first)
- Shows remaining quantity after dispenses
- Displays days until expiry

### Deleting Stock Records

*(Administrator only - in Spending Log)*

If a stock entry was added incorrectly:

1. Go to **"Medicine Inventory"** → **"Spending Log"**
2. Generate the report for the date range
3. Find the incorrect stock record
4. Click the red **trash icon** (🗑️) in the Actions column
5. Confirm deletion
6. The stock record is removed

**Warning**: This action cannot be undone. Use only to clean up incorrect entries.

---

## Dispensing Medicines

### Dispensing Process

*(Available to all users)*

1. **Navigate to Medicine List**
   - Click "Medicine Inventory" → "Medicine List"

2. **Select Medicine**
   - Find the medicine to dispense
   - Click **"Dispense"** button

3. **Select Stock Batch** (if multiple batches)
   - If the medicine has multiple batches with different expiry dates
   - Select which batch to use (usually FIFO - First In, First Out)
   - System shows expiry dates to help you choose

4. **Enter Dispense Details**

   **Quantity:**
   - **Input Method**: Select Boxes, Bottles, or Pieces
   - **Number of Units**: Enter quantity
   - **Pieces per Unit**: If using boxes/bottles
   - **Total Pieces**: Automatically calculated

   **Recipient Information:**
   - **Recipient Name**: Who received the medicine
   - **Reason**: Purpose (e.g., "Fever", "Headache", "Prescription")
   - **Notes**: Additional information

5. **Review and Dispense**
   - Check all information
   - Click **"Dispense"** button
   - Stock is deducted automatically
   - Transaction is recorded

### Dispense Notes

- **All dispenses are recorded**: Every dispense creates a transaction record
- **Stock is automatically deducted**: No manual stock adjustment needed
- **Transaction history**: All dispenses appear in Transactions and Dispensed Log
- **Health Worker/Staff**: Can only see their own dispensed records in the log

---

## Monitoring and Alerts

### Low Stock Monitoring

**What is Low Stock?**
- Stock count falls below the minimum stock level set for the medicine
- Example: If minimum is 50 pieces and current stock is 30, it's low stock

**Viewing Low Stock Items:**

1. Click **"Medicine Inventory"** → **"Low Stock"**
2. View all medicines with stock below minimum level
3. Information shown:
   - Medicine name
   - Current stock (pieces)
   - Minimum stock level
   - Difference (how many pieces needed)

**Actions Available:**
- **Add Stock**: Click to replenish inventory
- **View Stocks**: See batch details
- **View Details**: Go to medicine detail page

**Best Practice:**
- Check Low Stock page daily
- Replenish stock before it runs out
- Update minimum stock levels based on usage patterns

### Expiring Soon Monitoring

**What is Expiring Soon?**
- Medicines with batches expiring within 30 days
- Helps prevent waste and ensures proper rotation

**Viewing Expiring Medicines:**

1. Click **"Medicine Inventory"** → **"Expiring Soon"**
2. View all batches expiring within 30 days
3. Information shown:
   - Medicine name
   - Batch/Lot number
   - Quantity remaining
   - Expiry date
   - Days until expiry

**Actions Available:**
- **Record Action**: Mark what was done with expiring stock
  - Discard
  - Use immediately
  - Return to supplier
  - Other actions
- **Add Notes**: Document the action taken
- **View Details**: Go to medicine detail page

**Best Practice:**
- Check Expiring Soon weekly
- Use expiring stock first (FIFO principle)
- Record actions taken for audit purposes

### Out of Stock Alerts

**Visual Indicator:**
- Red badge appears on medicine in Medicine List
- Medicine shows "Out of Stock" status

**What to Do:**
1. Check Medicine List for red badges
2. Click on the medicine
3. Add stock immediately
4. Or mark medicine as inactive if discontinued

---

## Transactions

### Viewing All Transactions

1. Click **"Medicine Inventory"** → **"Transactions"**
2. View all stock movements:
   - **Stock In**: When stock was added
   - **Dispensed**: When medicine was given out
   - **Stock Out**: Other stock removals
   - **Adjustment**: Stock corrections

### Transaction Information

Each transaction shows:
- **Transaction Type**: Stock In, Dispensed, etc.
- **Date**: When it occurred
- **Medicine**: Which medicine
- **Quantity**: How many pieces
- **Unit Price**: Price per unit
- **Total Amount**: Total value
- **Recipient**: Who received (for dispenses)
- **Created By**: Which user recorded it

### Filtering Transactions

**Available Filters:**
- **Medicine**: Filter by specific medicine
- **Transaction Type**: Stock In, Dispensed, etc.
- **Date Range**: Start and end dates
- **Recipient Name**: For dispensed transactions
- **Search**: Text search

### Editing Transactions

*(Administrator only - StockIn transactions only)*

1. Find the StockIn transaction
2. Click **"Edit"** button
3. Modify quantity or other details
4. Click **"Save"**
5. Stock levels are automatically adjusted

**Note**: Only StockIn transactions can be edited. Dispensed transactions cannot be edited (must be deleted and recreated if incorrect).

### Deleting Transactions

*(Administrator only)*

1. Find the transaction
2. Click **"Delete"** button
3. Confirm deletion
4. **Important**: Stock levels are automatically reversed
   - Deleting a StockIn transaction: Removes stock
   - Deleting a Dispensed transaction: Adds stock back

**Warning**: Use with caution. Deleting transactions affects stock counts and financial reports.

### Dispensed Log

**Viewing Dispensed Records:**

1. Click **"Medicine Inventory"** → **"Dispensed Log"**
2. View all dispensed transactions
3. Shows:
   - Medicine name
   - Quantity dispensed
   - Recipient name
   - Date dispensed
   - Created by (user who dispensed)

**Filtering:**
- **Medicine**: Filter by specific medicine
- **Date Range**: Start and end dates
- **Recipient Name**: Search by recipient
- **Created By**: 
  - **Admin**: Can see all users' dispenses
  - **Others**: Can only see their own dispenses

**Export:**
- Click **"Export CSV"** to download
- Opens in Excel or spreadsheet software
- Useful for record keeping and reporting

---

## Financial Reports

*(Available to Admin, Secretary, Kagawad only)*

### Stock Status Report

**Purpose**: View current stock levels vs minimum requirements

1. Click **"Medicine Inventory"** → **"Stock Status"**
2. View report showing:
   - Medicine name
   - Current stock (pieces)
   - Minimum stock level
   - Status (Adequate, Low, Out of Stock)
3. Helps identify which medicines need replenishment

### Usage Value Report

**Purpose**: Track value of dispensed medicines

1. Click **"Medicine Inventory"** → **"Usage Value"**
2. Set **Date Range** (default: last 30 days)
3. Click **"Generate"**
4. View report showing:
   - Medicine name
   - Quantity dispensed (pieces)
   - Unit price
   - Total value (quantity × unit price)
5. Click **"Export CSV"** to download

**Use Cases:**
- Monthly usage reports
- Budget planning
- Cost analysis

### Spending Log

**Purpose**: Track all stock purchases and costs

1. Click **"Medicine Inventory"** → **"Spending Log"**
2. Set **Date Range** (default: last 30 days)
3. Click **"Generate"**
4. View report showing:
   - Medicine name
   - Supplier (Barangay, LGU, DOH, Donors, Other)
   - Batch number
   - Lot number
   - Quantity (pieces)
   - Unit cost (per box/bottle)
   - **Total Cost** (matches amount from Add Stock page)
   - Received date
   - Expiry date
5. **Delete Stock Record** (Admin only):
   - Click red trash icon to remove incorrect entries
   - Use to clean up the spending log
6. Click **"Export CSV"** to download

**Important Notes:**
- Total Cost = Unit Cost × Number of Boxes/Bottles
- This matches the "Total Amount" shown when adding stock
- Used for budget tracking and financial reporting

### Balance Summary

**Purpose**: Compare purchases vs usage to see net balance

1. Click **"Medicine Inventory"** → **"Balance Summary"**
2. Set **Date Range**
3. Click **"Generate"**
4. View summary showing:
   - **Total Purchases**: Sum from Spending Log
   - **Total Usage Value**: Sum from dispensed transactions
   - **Net Balance**: Purchases - Usage
5. Click **"Export CSV"** to download

**Use Cases:**
- Monthly financial summaries
- Budget reconciliation
- Cost analysis

---

## Common Operations

### Searching for Medicines

1. Go to Medicine List
2. Type in the search box
3. Results filter automatically
4. Search works on medicine names

### Filtering by Status

1. Go to Medicine List
2. Click **Status** dropdown
3. Select: All, Active, Inactive, Out of Stock, Low Stock, Expiring Soon
4. List updates automatically

### Exporting Reports

1. Generate any report (Usage Value, Spending Log, Balance Summary)
2. Click **"Export CSV"** button
3. File downloads automatically
4. Open in Excel or spreadsheet software
5. Use for:
   - Record keeping
   - External reporting
   - Data analysis

### Printing Reports

1. Generate the report
2. Press **Ctrl + P** (or browser Print button)
3. Adjust print settings
4. Print or save as PDF

---

## Troubleshooting

### Cannot Add Medicine

**Problem**: "Add Medicine" button is missing or disabled

**Solutions:**
- Verify your user role (Admin, Secretary, or Kagawad only)
- Health Worker/Staff cannot add medicines
- Contact administrator if you need access

### Cannot Add Stock

**Problem**: "Add Stock" button is missing or not working

**Solutions:**
- Verify the medicine exists and is active
- Check you have permission (all roles can add stock)
- Ensure all required fields are filled
- Check for error messages

### Stock Count Incorrect

**Problem**: Stock count doesn't match expected amount

**Solutions:**
1. Check transaction history
   - Go to Medicine Details
   - Review all transactions
   - Verify all dispenses are recorded
2. Check for deleted transactions
   - Deleted transactions affect stock counts
3. Verify stock additions
   - Ensure all stock was properly added
4. Contact administrator if issue persists

### Cannot See Financial Reports

**Problem**: Financial reports menu items are missing

**Solutions:**
- Verify your user role
- Health Worker/Staff cannot access financial reports
- Only Admin, Secretary, and Kagawad can view reports
- Contact administrator if you need access

### Total Cost Calculation Wrong

**Problem**: Spending Log shows incorrect total cost

**Solutions:**
- Verify how stock was added
- Total Cost = Unit Cost × Number of Boxes/Bottles
- NOT Unit Cost × Total Pieces
- Check the Add Stock page calculation
- If incorrect, Admin can delete the stock record and re-add

### Medicine Not Showing in List

**Problem**: Added medicine doesn't appear

**Solutions:**
- Check if medicine was deleted (soft-deleted)
- Verify filter settings (may be filtered out)
- Check status (inactive medicines may be hidden)
- Refresh the page (F5)

### Cannot Delete Stock Record

**Problem**: Delete button not available in Spending Log

**Solutions:**
- Only Administrator can delete stock records
- Verify you're logged in as Admin
- Check if the record exists in the date range
- Generate the report first, then delete

### Expiry Date Issues

**Problem**: Expiring Soon shows wrong dates

**Solutions:**
- Verify expiry dates when adding stock
- Check date format (MM/DD/YYYY)
- System shows medicines expiring within 30 days
- Update expiry dates if incorrect

---

## Best Practices

### Daily Operations

1. **Check Low Stock Daily**
   - Review Low Stock page each morning
   - Replenish before running out
   - Update minimum stock levels based on usage

2. **Check Expiring Soon Weekly**
   - Review Expiring Soon page weekly
   - Use expiring stock first (FIFO)
   - Record actions taken

3. **Record Dispenses Immediately**
   - Don't wait to record dispenses
   - Enter recipient information accurately
   - Add notes when necessary

### Stock Management

1. **Accurate Data Entry**
   - Enter correct quantities
   - Verify expiry dates
   - Record supplier information
   - Double-check calculations

2. **Proper Stock Rotation**
   - Use oldest stock first (FIFO)
   - Check expiry dates before dispensing
   - Remove expired stock promptly

3. **Regular Audits**
   - Compare system stock vs physical stock
   - Investigate discrepancies
   - Update records as needed

### Financial Management

1. **Regular Report Generation**
   - Generate Spending Log monthly
   - Review Usage Value reports
   - Check Balance Summary regularly
   - Keep exported reports for records

2. **Cost Tracking**
   - Enter accurate unit costs
   - Verify total amounts
   - Review spending patterns
   - Use reports for budgeting

### User Management

1. **Role Assignment**
   - Assign appropriate roles
   - Health Worker/Staff: Dispense only
   - Admin/Secretary/Kagawad: Full access
   - Review access regularly

2. **Training**
   - Train users on proper procedures
   - Emphasize accurate data entry
   - Review best practices regularly

---

## Quick Reference

### Navigation Shortcuts

- **Medicine List**: Medicine Inventory → Medicine List
- **Add Medicine**: Medicine Inventory → Add Medicine
- **Add Stock**: Medicine List → Click "Add Stock" on medicine row
- **Dispense**: Medicine List → Click "Dispense" on medicine row
- **Low Stock**: Medicine Inventory → Low Stock
- **Expiring Soon**: Medicine Inventory → Expiring Soon
- **Transactions**: Medicine Inventory → Transactions
- **Dispensed Log**: Medicine Inventory → Dispensed Log
- **Reports**: Medicine Inventory → [Report Name]

### Important Formulas

- **Total Amount (Add Stock)**: Unit Price × Number of Boxes/Bottles
- **Remaining Stock**: Total Received - Total Dispensed
- **Net Balance**: Total Purchases - Total Usage Value

### Status Indicators

- 🔴 Red = Out of Stock
- 🟠 Orange = Low Stock
- 🟡 Yellow = Expiring Soon
- ✅ No badge = Normal

### User Roles Summary

| Feature | Admin | Secretary/Kagawad | Health Worker/Staff |
|---------|-------|-------------------|---------------------|
| Add Medicine | ✅ | ✅ | ❌ |
| Add Stock | ✅ | ✅ | ❌ |
| Dispense | ✅ | ✅ | ✅ |
| Delete Medicine | ✅ | ❌ | ❌ |
| Delete Transaction | ✅ | ❌ | ❌ |
| Delete Stock Record | ✅ | ❌ | ❌ |
| Financial Reports | ✅ | ✅ | ❌ |
| View All Dispensed Logs | ✅ | ✅ | ❌ (Own only) |

---

## Support

For questions or issues:
- Contact your system administrator
- Refer to this manual
- Check system help documentation

---

**Last Updated**: [Current Date]
**Version**: 1.0





