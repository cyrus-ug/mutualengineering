<?php
$recipient = 'mutualengandlog@gmail.com';
$siteName = 'Mutual Engineering & Logistics';
$backLink = 'contact.html';

function load_wordpress_mailer() {
    $directory = __DIR__;

    for ($i = 0; $i < 6; $i++) {
        $wpLoad = $directory . DIRECTORY_SEPARATOR . 'wp-load.php';

        if (file_exists($wpLoad)) {
            require_once $wpLoad;
            return function_exists('wp_mail');
        }

        $parent = dirname($directory);

        if ($parent === $directory) {
            break;
        }

        $directory = $parent;
    }

    return false;
}

function clean_input($value) {
    return trim(str_replace(["\r", "\n"], ' ', filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS)));
}

function render_response($title, $message, $type, $backLink) {
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $safeType = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
    $safeBackLink = htmlspecialchars($backLink, ENT_QUOTES, 'UTF-8');

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$safeTitle}</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="message-result-page">
    <main class="message-result {$safeType}">
        <h1>{$safeTitle}</h1>
        <p>{$safeMessage}</p>
        <a href="{$safeBackLink}" class="btn">Back to Contact Page</a>
    </main>
</body>
</html>
HTML;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    render_response('Form unavailable', 'Please submit your message from the contact page.', 'error', $backLink);
    exit;
}

if (!empty($_POST['website'] ?? '')) {
    render_response('Message blocked', 'Your message could not be processed.', 'error', $backLink);
    exit;
}

$name = clean_input($_POST['name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone = clean_input($_POST['phone'] ?? '');
$service = clean_input($_POST['service'] ?? 'Not specified');
$message = trim($_POST['message'] ?? '');

if ($name === '' || !$email || $message === '') {
    render_response('Missing details', 'Please provide your name, a valid email address, and a message.', 'error', $backLink);
    exit;
}

$plainMessage = trim(strip_tags($message));
$subject = 'New website inquiry from ' . $name;

$body = "New contact form message\n\n";
$body .= "Name: {$name}\n";
$body .= "Email: {$email}\n";
$body .= "Phone: " . ($phone !== '' ? $phone : 'Not provided') . "\n";
$body .= "Service Needed: {$service}\n\n";
$body .= "Message:\n{$plainMessage}\n";

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: Mutual Engineering Website <' . $recipient . '>',
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . phpversion()
];

$usesWordPress = load_wordpress_mailer();

if ($usesWordPress) {
    $sent = wp_mail($recipient, $subject, $body, $headers);
} else {
    $sent = mail($recipient, $subject, wordwrap($body, 70), implode("\r\n", $headers));
}

if ($sent) {
    render_response('Message sent', 'Thank you. Your message has been sent to Mutual Engineering & Logistics.', 'success', $backLink);
    exit;
}

render_response(
    'Message not sent',
    'The form is working, but the server could not send the email. Please check the hosting mail or SMTP settings.',
    'error',
    $backLink
);
