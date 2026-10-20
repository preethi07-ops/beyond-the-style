# Beyond The Style

Fashion e-commerce site (PHP + MySQL) for men and women. Runs on XAMPP.

## Setup (XAMPP)
1. Copy the `Beyond-The-Style` folder into `C:\xampp\htdocs\` (macOS/Linux: `/opt/lampp/htdocs/`).
2. Open the XAMPP Control Panel and **Start Apache** and **Start MySQL**.
3. Open http://localhost/phpmyadmin → **Import** tab → choose `database.sql` → **Import**.
   (This creates the database `beyond_the_style` with all tables and sample data.)
4. Database credentials are in `includes/config.php` (defaults: host `localhost`, user `root`, empty password).
5. Open the store: http://localhost/Beyond-The-Style/
6. Admin panel: http://localhost/Beyond-The-Style/admin/

## Demo logins
| Role     | Login                      | Password    |
|----------|----------------------------|-------------|
| Admin    | `admin`                    | `Admin@123` |
| Customer | `demo@beyondthestyle.com`  | `Demo@1234` |

Change the admin password after first use (generate a hash with `php -r "echo password_hash('NewPass', PASSWORD_DEFAULT);"` and update the `admins` table in phpMyAdmin).

## Database `beyond_the_style`
Tables: users, admins, categories, products, product_images, cart, orders, order_items, reviews, wishlist, contact_messages.

## Requirements
PHP 8.1+ (XAMPP 8.1 or newer) with `pdo_mysql` and `fileinfo` enabled (both are on by default).
Product uploads go to `assets/uploads/products/` (must be writable).

## Notes
- Payment is a safe demo: "Cash on Delivery" or "Demo online payment" (no card data is collected).
- Currency is set in `includes/config.php` (`CURRENCY`).
