# Quick Setup Guide for PHP/MySQL/phpMyAdmin

## Step 1: Start Services in XAMPP

1. Open XAMPP Control Panel
2. Start the **Apache** service
3. Start the **MySQL** service
4. Make sure both are running (green status)

## Step 2: Create Database

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click "New" to create a database
3. Name it: `warehouse_db`
4. Select the database
5. Go to "SQL" tab
6. Copy and paste the SQL from `db/init.sql`
7. Click "Go" to execute

Alternatively, you can import the `db/init.sql` file directly.

## Step 3: Configure Database Connection

Edit `db.php` and update the credentials if needed:

```php
$host = "localhost";
$user = "root";        // Default XAMPP MySQL username
$pass = "";            // Default XAMPP MySQL password (usually empty)
$dbname = "warehouse_db";
```

**For XAMPP default installation:**
- Host: `localhost`
- User: `root`
- Password: (leave empty)

## Step 4: Place Files in Web Server

Copy all project files to your web server directory:
- **XAMPP**: `C:\xampp\htdocs\warehousing\`
- **Other servers**: Your web root directory (e.g., `/var/www/html/warehousing/`)

## Step 5: Access the Application

Open your browser and go to:
```
http://localhost/warehousing/login.php
```

## Step 6: Login or Register

### Option 1: Register New Account
1. Click "Create one" link on login page
2. Fill in username, password, and select role (admin or user)
3. Click "Register"
4. Login with your new credentials

### Option 2: Use Default Accounts (if created)

**Admin Account:**
- Username: `admin`
- Password: `admin123`

**User Account (View Only):**
- Username: `user`
- Password: `user123`

**Note**: If these accounts don't exist, you can create them via registration or manually in phpMyAdmin.

## Using phpMyAdmin

You can manage the database using phpMyAdmin:

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select the `warehouse_db` database
3. You'll see these tables:
   - `users` - User accounts
   - `items` - Warehouse items
   - `stock_movements` - Stock movement records

## Troubleshooting

### Error: "Access denied for user 'root'@'localhost'"
- Check your MySQL password in `db.php`
- For XAMPP, the password is usually empty (blank string)

### Error: "Can't connect to MySQL server"
- Make sure MySQL service is running in XAMPP
- Check if MySQL is running on port 3306 (default)

### Error: "Database doesn't exist"
- Create the database manually in phpMyAdmin
- Run the SQL from `db/init.sql` in phpMyAdmin

### Error: "Table doesn't exist"
- Run the SQL from `db/init.sql` in phpMyAdmin
- Make sure you selected the correct database

### PHP Errors
- Make sure Apache service is running
- Check PHP error logs in XAMPP
- Verify PHP is enabled in your web server

### Page Not Found (404)
- Check the file path in your browser
- Make sure files are in the correct web server directory
- Verify Apache is running

## Manual User Creation (Optional)

If you want to create users manually in phpMyAdmin:

1. Open phpMyAdmin
2. Select `warehouse_db` database
3. Go to `users` table
4. Click "Insert" tab
5. Fill in:
   - `username`: Your desired username
   - `password_hash`: Use PHP's `password_hash()` function or run this SQL:
     ```sql
     INSERT INTO users (username, password_hash, role) 
     VALUES ('admin', '$2y$10$YourHashedPasswordHere', 'admin');
     ```
   - `role`: 'admin' or 'user'

**Note**: It's easier to use the registration page at `register.php` to create users.

## Cleanup (Optional)

If you want to remove Node.js related files (not needed for PHP version):

- You can delete the `node_modules/` directory (if it exists)
- No need for `package.json`, `server.js`, or any `.js` files in the project root

The system now runs entirely on PHP - no Node.js or JavaScript required!
