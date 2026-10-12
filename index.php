<?php
session_start();
require __DIR__ . '/config.php';

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function post_text(string $key): string {
    return trim((string)($_POST[$key] ?? ''));
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = post_text('action');

        if ($action === 'add_member') {
            $name = post_text('name');
            $email = post_text('email');
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Enter a name and a valid email address.');
            }
            $stmt = $pdo->prepare('INSERT INTO members (name, email) VALUES (?, ?)');
            $stmt->execute([$name, $email]);
            $message = 'Member added successfully.';
        } elseif ($action === 'add_book') {
            $title = post_text('title');
            $author = post_text('author');
            $genre = post_text('genre');
            $status = post_text('status');
            if ($title === '' || $author === '') {
                throw new RuntimeException('Book title and author are required.');
            }
            if (!in_array($status, ['Suggested', 'Selected', 'Completed'], true)) {
                throw new RuntimeException('Choose a valid book status.');
            }
            $stmt = $pdo->prepare('INSERT INTO books (title, author, genre, status) VALUES (?, ?, ?, ?)');
            $stmt->execute([$title, $author, $genre !== '' ? $genre : null, $status]);
            $message = 'Book added successfully.';
        } elseif ($action === 'update_status') {
            $bookId = filter_var($_POST['book_id'] ?? null, FILTER_VALIDATE_INT);
            $status = post_text('status');
            if (!$bookId || !in_array($status, ['Suggested', 'Selected', 'Completed'], true)) {
                throw new RuntimeException('Choose a valid book and status.');
            }
            $stmt = $pdo->prepare('UPDATE books SET status = ? WHERE book_id = ?');
            $stmt->execute([$status, $bookId]);
            $message = 'Book status updated.';
        } elseif ($action === 'add_meeting') {
            $bookId = filter_var($_POST['book_id'] ?? null, FILTER_VALIDATE_INT);
            $date = post_text('meeting_date');
            $location = post_text('location');
            $notes = post_text('notes');
            if (!$bookId || $date === '' || $location === '') {
                throw new RuntimeException('Choose a book and enter the date, time, and location.');
            }
            $stmt = $pdo->prepare('INSERT INTO meetings (book_id, meeting_date, location, notes) VALUES (?, ?, ?, ?)');
            $stmt->execute([$bookId, str_replace('T', ' ', $date) . (strlen($date) === 16 ? ':00' : ''), $location, $notes !== '' ? $notes : null]);
            $message = 'Meeting scheduled successfully.';
        } elseif ($action === 'add_discussion') {
            $bookId = filter_var($_POST['book_id'] ?? null, FILTER_VALIDATE_INT);
            $memberId = filter_var($_POST['member_id'] ?? null, FILTER_VALIDATE_INT);
            $text = post_text('message');
            if (!$bookId || !$memberId || $text === '') {
                throw new RuntimeException('Choose a book and member, and enter a message.');
            }
            $stmt = $pdo->prepare('INSERT INTO discussions (book_id, member_id, message) VALUES (?, ?, ?)');
            $stmt->execute([$bookId, $memberId, $text]);
            $message = 'Discussion message posted.';
        }
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $error = 'That email address is already registered, or the selected record is invalid.';
        } else {
            $error = 'The operation could not be completed. Check the entered information and database setup.';
        }
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }

    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_error'] = $error;
    header('Location: index.php');
    exit;
}

$message = (string)($_SESSION['flash_message'] ?? '');
$error = (string)($_SESSION['flash_error'] ?? '');
unset($_SESSION['flash_message'], $_SESSION['flash_error']);

$members = $pdo->query('SELECT * FROM members ORDER BY name')->fetchAll();
$books = $pdo->query('SELECT * FROM books ORDER BY added_at DESC')->fetchAll();
$meetings = $pdo->query(
    'SELECT meetings.*, books.title
     FROM meetings JOIN books ON meetings.book_id = books.book_id
     ORDER BY meetings.meeting_date ASC'
)->fetchAll();
$discussions = $pdo->query(
    'SELECT discussions.*, books.title, members.name AS member_name
     FROM discussions
     JOIN books ON discussions.book_id = books.book_id
     JOIN members ON discussions.member_id = members.member_id
     ORDER BY discussions.posted_at DESC
     LIMIT 50'
)->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Book Club Management System</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="hero">
  <div class="wrap">
    <p class="eyebrow">READ · SHARE · CONNECT</p>
    <h1>Book Club Management</h1>
    <p>Manage members, choose books, schedule meetings, and share ideas in one place.</p>
    <nav><a href="#members">Members</a><a href="#books">Books</a><a href="#meetings">Meetings</a><a href="#discussions">Discussions</a></nav>
  </div>
</header>
<main class="wrap">
<?php if ($message !== ''): ?><div class="notice success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="notice error"><?= h($error) ?></div><?php endif; ?>

<section class="stats">
  <div class="stat"><span>Members</span><strong><?= count($members) ?></strong></div>
  <div class="stat"><span>Books</span><strong><?= count($books) ?></strong></div>
  <div class="stat"><span>Meetings</span><strong><?= count($meetings) ?></strong></div>
  <div class="stat"><span>Messages</span><strong><?= count($discussions) ?></strong></div>
</section>

<section class="panel" id="members">
  <div class="section-head"><div><p class="eyebrow">COMMUNITY</p><h2>Member Management</h2></div></div>
  <form method="post" class="form-grid">
    <input type="hidden" name="action" value="add_member">
    <label>Member name<input name="name" maxlength="100" required placeholder="e.g. Alice Johnson"></label>
    <label>Email address<input type="email" name="email" maxlength="150" required placeholder="alice@example.com"></label>
    <button type="submit">Add Member</button>
  </form>
  <div class="table-wrap"><table><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Joined</th></tr></thead><tbody>
  <?php foreach ($members as $m): ?><tr><td><?= h($m['member_id']) ?></td><td><?= h($m['name']) ?></td><td><?= h($m['email']) ?></td><td><?= h($m['joined_at']) ?></td></tr><?php endforeach; ?>
  <?php if (!$members): ?><tr><td colspan="4" class="empty">No members yet.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>

<section class="panel" id="books">
  <div class="section-head"><div><p class="eyebrow">READING LIST</p><h2>Book Management</h2></div></div>
  <form method="post" class="form-grid">
    <input type="hidden" name="action" value="add_book">
    <label>Book title<input name="title" maxlength="200" required placeholder="Book title"></label>
    <label>Author<input name="author" maxlength="150" required placeholder="Author name"></label>
    <label>Genre<input name="genre" maxlength="80" placeholder="Fiction, mystery..."></label>
    <label>Status<select name="status"><option>Suggested</option><option>Selected</option><option>Completed</option></select></label>
    <button type="submit">Add Book</button>
  </form>
  <div class="table-wrap"><table><thead><tr><th>Title</th><th>Author</th><th>Genre</th><th>Status</th><th>Update status</th></tr></thead><tbody>
  <?php foreach ($books as $b): ?><tr>
    <td><?= h($b['title']) ?></td><td><?= h($b['author']) ?></td><td><?= h($b['genre'] ?? '—') ?></td><td><span class="pill"><?= h($b['status']) ?></span></td>
    <td><form method="post" class="inline-form"><input type="hidden" name="action" value="update_status"><input type="hidden" name="book_id" value="<?= h($b['book_id']) ?>"><select name="status"><?php foreach (['Suggested','Selected','Completed'] as $s): ?><option <?= $b['status'] === $s ? 'selected' : '' ?>><?= h($s) ?></option><?php endforeach; ?></select><button type="submit" class="small-btn">Save</button></form></td>
  </tr><?php endforeach; ?>
  <?php if (!$books): ?><tr><td colspan="5" class="empty">No books yet.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>

<section class="panel" id="meetings">
  <div class="section-head"><div><p class="eyebrow">PLAN AHEAD</p><h2>Meeting Schedule</h2></div></div>
  <?php if ($books): ?>
  <form method="post" class="form-grid">
    <input type="hidden" name="action" value="add_meeting">
    <label>Book<select name="book_id" required><?php foreach ($books as $b): ?><option value="<?= h($b['book_id']) ?>"><?= h($b['title']) ?></option><?php endforeach; ?></select></label>
    <label>Date and time<input type="datetime-local" name="meeting_date" required></label>
    <label>Location or meeting link<input name="location" maxlength="200" required placeholder="Library or online meeting link"></label>
    <label>Notes<input name="notes" maxlength="500" placeholder="Discussion topics (optional)"></label>
    <button type="submit">Schedule Meeting</button>
  </form>
  <?php else: ?><p class="hint">Add a book before scheduling a meeting.</p><?php endif; ?>
  <div class="table-wrap"><table><thead><tr><th>Book</th><th>Date and time</th><th>Location</th><th>Notes</th></tr></thead><tbody>
  <?php foreach ($meetings as $mt): ?><tr><td><?= h($mt['title']) ?></td><td><?= h($mt['meeting_date']) ?></td><td><?= h($mt['location']) ?></td><td><?= h($mt['notes'] ?? '—') ?></td></tr><?php endforeach; ?>
  <?php if (!$meetings): ?><tr><td colspan="4" class="empty">No meetings scheduled yet.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>

<section class="panel" id="discussions">
  <div class="section-head"><div><p class="eyebrow">SHARE IDEAS</p><h2>Member Discussions</h2></div></div>
  <?php if ($books && $members): ?>
  <form method="post" class="form-grid">
    <input type="hidden" name="action" value="add_discussion">
    <label>Book<select name="book_id" required><?php foreach ($books as $b): ?><option value="<?= h($b['book_id']) ?>"><?= h($b['title']) ?></option><?php endforeach; ?></select></label>
    <label>Member<select name="member_id" required><?php foreach ($members as $m): ?><option value="<?= h($m['member_id']) ?>"><?= h($m['name']) ?></option><?php endforeach; ?></select></label>
    <label class="wide">Message<textarea name="message" rows="3" required maxlength="3000" placeholder="Share your thoughts about the book..."></textarea></label>
    <button type="submit">Post Message</button>
  </form>
  <?php else: ?><p class="hint">Add at least one member and one book to start a discussion.</p><?php endif; ?>
  <div class="discussion-list">
  <?php foreach ($discussions as $d): ?><article class="discussion"><div class="discussion-top"><strong><?= h($d['member_name']) ?></strong><span><?= h($d['title']) ?> · <?= h($d['posted_at']) ?></span></div><p><?= nl2br(h($d['message'])) ?></p></article><?php endforeach; ?>
  <?php if (!$discussions): ?><p class="empty">No discussion messages yet.</p><?php endif; ?>
  </div>
</section>
<footer>Book Club Management System · Built with PHP and MySQL</footer>
</main>
</body>
</html>
