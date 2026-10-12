CREATE DATABASE IF NOT EXISTS book_club_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE book_club_db;

CREATE TABLE IF NOT EXISTS members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS books (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(150) NOT NULL,
    genre VARCHAR(80) DEFAULT NULL,
    status ENUM('Suggested','Selected','Completed') NOT NULL DEFAULT 'Suggested',
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS meetings (
    meeting_id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    meeting_date DATETIME NOT NULL,
    location VARCHAR(200) NOT NULL,
    notes VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_meeting_book FOREIGN KEY (book_id)
        REFERENCES books(book_id) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS discussions (
    discussion_id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    member_id INT NOT NULL,
    message TEXT NOT NULL,
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_discussion_book FOREIGN KEY (book_id)
        REFERENCES books(book_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_discussion_member FOREIGN KEY (member_id)
        REFERENCES members(member_id) ON UPDATE CASCADE ON DELETE CASCADE
);

-- Optional sample data. Remove these INSERT statements if you want an empty database.
INSERT IGNORE INTO members (name, email) VALUES
('Alice', 'alice@example.com'),
('Bob', 'bob@example.com');

INSERT INTO books (title, author, genre, status)
SELECT 'The Great Gatsby', 'F. Scott Fitzgerald', 'Classic Fiction', 'Selected'
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title='The Great Gatsby' AND author='F. Scott Fitzgerald');

INSERT INTO meetings (book_id, meeting_date, location, notes)
SELECT book_id, '2027-03-15 18:00:00', 'Community Library', 'Discuss the selected book'
FROM books
WHERE title='The Great Gatsby'
AND NOT EXISTS (
    SELECT 1 FROM meetings
    WHERE meetings.book_id=books.book_id
      AND meetings.meeting_date='2027-03-15 18:00:00'
);

INSERT INTO discussions (book_id, member_id, message)
SELECT books.book_id, members.member_id, 'I enjoyed this book!'
FROM books CROSS JOIN members
WHERE books.title='The Great Gatsby'
  AND members.email='alice@example.com'
  AND NOT EXISTS (
      SELECT 1 FROM discussions
      WHERE discussions.book_id=books.book_id
        AND discussions.member_id=members.member_id
        AND discussions.message='I enjoyed this book!'
  );
