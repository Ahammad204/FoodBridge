<?php
// FoodBridge — contact page
// Note: the form validates and confirms on screen. XAMPP has no mail server
// by default, so messages are not emailed or stored yet — see README notes.
$pageTitle = 'Contact';
require_once __DIR__ . '/includes/auth_check.php'; // session + CSRF + flash helpers

$errors = [];
$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old['name']    = trim($_POST['name'] ?? '');
    $old['email']   = trim($_POST['email'] ?? '');
    $old['subject'] = trim($_POST['subject'] ?? '');
    $old['message'] = trim($_POST['message'] ?? '');

    if ($old['name'] === '' || mb_strlen($old['name']) < 3) {
        $errors[] = 'Please enter your name.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($old['subject'] === '') {
        $errors[] = 'Please enter a subject.';
    }
    if (mb_strlen($old['message']) < 10) {
        $errors[] = 'Please write a message (at least 10 characters).';
    }

    if (!$errors) {
        $sent = true;
        $old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
    }
}

include __DIR__ . '/includes/header.php';
$baseUrl = '/FoodBridge';
?>

<h1 class="fb-section-title mb-4">Contact us</h1>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card card-donation h-100">
            <div class="card-body">
                <h2 class="h6 fw-bold">Reach us directly</h2>
                <p class="text-muted small mb-2">
                    Dhaka, Bangladesh<br>
                    Email: hello@foodbridge.local
                </p>
                <p class="text-muted small mb-0">
                    For partnership, donation drives or support questions,
                    send a message with the form and we will get back to you.
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <?php if ($sent): ?>
                    <div class="alert alert-success" role="alert">
                        Thank you — your message has been received. We will reply to your email soon.
                    </div>
                <?php endif; ?>

                <?php if ($errors): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="" novalidate>
                    <?= csrf_field() ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="name">Your name</label>
                            <input type="text" class="form-control" id="name" name="name" required
                                   value="<?= htmlspecialchars($old['name'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="email">Your email</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?= htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="subject">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required
                               value="<?= htmlspecialchars($old['subject'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="message">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required><?= htmlspecialchars($old['message'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-fb-orange">Send message</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
