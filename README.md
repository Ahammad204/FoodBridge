# FoodBridge

FoodBridge is a PHP + MySQL web app that connects food donors with receivers,
so surplus food reaches people who need it instead of going to waste.

## Stack

- HTML, CSS, JavaScript, Bootstrap 5
- PHP (PDO)
- MySQL

## Setup (XAMPP)

1. Copy this folder into your XAMPP `htdocs` folder: `htdocs/FoodBridge`
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Open [phpMyAdmin](http://localhost/phpmyadmin), go to **Import**, and import `database.sql`.
   This creates the `foodbridge` database, all tables, the default admin, and sample data.
4. Open [http://localhost/FoodBridge](http://localhost/FoodBridge)

Database connection settings live in `config/db.php`
(XAMPP defaults: host `localhost`, user `root`, empty password, database `foodbridge`).

## Default accounts (from database.sql sample data)

| Role     | Email                    | Password      |
|----------|--------------------------|---------------|
| Admin    | admin@foodbridge.local   | `Admin@123`   |
| Donor    | donor@example.com        | `Donor@123`   |
| Receiver | receiver@example.com     | `Receiver@123`|

Change these passwords before any real use.

## Folder structure

```
/config     Database connection (db.php)
/includes   header, footer, navbar, auth_check
/assets     css, js, img, uploads
/admin      Admin pages
/donor      Donor pages
/receiver   Receiver pages
/auth       Login / register / logout pages
database.sql  Database schema + sample data
```
