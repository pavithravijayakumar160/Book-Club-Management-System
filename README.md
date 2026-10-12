# Book Club Management System

A beginner-friendly DBMS mini project built with **PHP, MySQL, HTML, and CSS**.

## Features
- Add and view club members
- Add books and update their status (Suggested, Selected, Completed)
- Schedule meetings with date, time, location, and notes
- Post book discussion messages as a member
- Store and retrieve all information from MySQL using relational tables and foreign keys

## Requirements
- XAMPP (Apache + MySQL/MariaDB + PHP) or another PHP/MySQL setup
- A web browser

## Setup using XAMPP
1. Install and open XAMPP.
2. Start **Apache** and **MySQL**.
3. Copy this project folder into `C:\\xampp\\htdocs\\book_club_management_system`.
4. Open `http://localhost/phpmyadmin`.
5. Choose **Import**, select `database.sql`, and click **Import/Go**. This creates `book_club_db` and the tables.
6. Check `config.php`. The default XAMPP settings are host `localhost`, user `root`, and an empty password. Change them if your MySQL setup is different.
7. Open `http://localhost/book_club_management_system/`.

## How to test
1. Add a member.
2. Add a book and mark it as Selected.
3. Schedule a meeting for that book.
4. Post a discussion message using a member and book.
5. View the tables and records on the page or in phpMyAdmin.

## Database tables
- `members`: member names and unique email addresses
- `books`: book title, author, genre, and selection status
- `meetings`: date/time and location, linked to a book
- `discussions`: message, member, and book links

## Important notes
- This is a local learning/demo project, not a deployed production service.
- The discussion form lets you choose a member for demonstration; it does not implement real user login.
- The project stores meeting details and displays them. It does not send email or push notifications.
- Do not put real passwords or sensitive personal information in this demo.
- If you change the database name or credentials, update `config.php` too.
