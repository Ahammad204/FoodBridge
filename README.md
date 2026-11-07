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

## Running at a domain root (hosting)

The app auto-detects where it runs (`config/app.php`): on localhost it uses
the `/FoodBridge` base path (XAMPP subfolder); on any real domain `BASE_URL`
is empty, so the same code works from `htdocs` at the domain root.

On a real host, `config/db.php` loads database credentials from
`config/config.production.php` instead of the XAMPP defaults. That file is
**git-ignored** (see `.gitignore`): copy it from the deploy zip, set the real
MySQL password in place of `YOUR_PASSWORD_HERE`, and upload it with the site.

## Updating an existing install

If you imported an older `database.sql` before notifications existed, run
`sql_update_notifications.sql` once in phpMyAdmin to add the `notifications` table.

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
