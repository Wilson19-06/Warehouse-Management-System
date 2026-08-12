# Warehouse Inventory Management System

A simple web-based warehouse inventory management system built with **PHP, HTML, and CSS** (no JavaScript).

## Features

### Admin Features
- **Dashboard**: View total items, low stock alerts, and today's sales
- **Item Management**: Full CRUD operations (Create, Read, Update, Delete) for warehouse items
- **Stock Movements**: Record IN/OUT movements that automatically update item quantities
- **Reports**: Filter and view stock movements by date range, item, and movement type with revenue calculations
- **Search**: Search items by name, code, or category

### User Features (View Only)
- **View Items**: Browse all warehouse items with search functionality
- **Item Details**: View detailed information about any item
- **Search**: Search items by name

## Tech Stack

- **Frontend**: HTML, CSS (Pure CSS, no JavaScript)
- **Backend**: PHP
- **Database**: MySQL (phpMyAdmin)
- **Authentication**: Session-based with bcrypt password hashing

## Installation

1. **Prerequisites**
   - Install XAMPP (or any PHP + MySQL server) with phpMyAdmin
   - Start Apache and MySQL services in XAMPP Control Panel

2. **Database Setup**
   - Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
   - Create a new database named `warehouse_db`
   - Import the SQL from `db/init.sql` in phpMyAdmin, OR
   - Run the SQL commands from `db/init.sql` manually

3. **Configure Database Connection**
   - Edit `db.php` to match your MySQL credentials:
     ```php
     $host = "localhost";
     $user = "root";        // Your MySQL username
     $pass = "";            // Your MySQL password (usually empty for XAMPP)
     $dbname = "warehouse_db";
     ```

4. **Place Files in Web Server**
   - Copy all files to your web server directory (e.g., `C:\xampp\htdocs\warehousing`)
   - Make sure PHP is enabled in your web server

5. **Access the Application**
   - Open your browser and navigate to: `http://localhost/warehousing/login.php`

## Default Credentials

### Admin Account
- **Username**: `admin`
- **Password**: `admin123`

### User Account (View Only)
- **Username**: `user`
- **Password**: `user123`

**Note**: If these accounts don't exist, you can register new accounts at `register.php`

## Project Structure

```
warehousing/
├── db/
│   └── init.sql         # MySQL database schema
├── dashboard.php        # Dashboard page
├── items.php            # Item management page
├── movement.php         # Stock movement page
├── report.php           # Reports page
├── login.php            # Login page
├── register.php         # Registration page
├── logout.php           # Logout handler
├── delete_confirm.php   # Delete confirmation page
├── db.php               # Database connection
├── header.php           # Shared header/navigation
├── footer.php           # Shared footer
├── style.css            # Main stylesheet
└── README.md           # This file
```

## Database Schema

### Users Table
- `id` - Primary key
- `username` - Unique username
- `password_hash` - Bcrypt hashed password
- `role` - Either 'admin' or 'user'

### Items Table
- `id` - Primary key
- `item_code` - Unique item code (optional)
- `item_name` - Item name
- `category` - Item category
- `location` - Warehouse location
- `quantity` - Current stock quantity
- `unit` - Unit of measurement (default: 'pcs')
- `reorder_level` - Minimum stock level (default: 10)
- `price` - Item price (DECIMAL)
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

### Stock Movements Table
- `id` - Primary key
- `item_id` - Foreign key to items
- `movement_type` - Either 'IN' or 'OUT'
- `quantity` - Movement quantity
- `date` - Movement date
- `reference_note` - Optional reference note
- `created_at` - Creation timestamp

## Usage

1. **Login**: Use the default credentials or register a new account
2. **Admin Panel**: 
   - Add items with details (name, category, location, quantity, price)
   - Record stock movements (IN/OUT)
   - View dashboard for overview
   - Generate reports with filters
3. **User View**: 
   - Browse items
   - View item details
   - Search functionality available

## Notes

- The database (`warehouse_db`) must be created manually in phpMyAdmin
- Default admin and user accounts can be created via registration or manually in the database
- Stock movements automatically update item quantities
- OUT movements cannot result in negative stock
- Item codes are optional (auto-generated if not provided)
- Low stock threshold is set to 10 units by default
- Make sure MySQL service is running before accessing the application
- You can manage the database using phpMyAdmin at `http://localhost/phpmyadmin`

## Security Notes

- Passwords are hashed using PHP's `password_hash()` (bcrypt)
- Session-based authentication
- Role-based access control
- SQL injection protection via prepared statements
- No JavaScript - all functionality is server-side

## Future Enhancements

Potential improvements:
- User management (add/edit/delete users)
- Export reports to CSV/PDF
- Email notifications for low stock
- Barcode scanning support
- Multi-warehouse support
- Audit logs
