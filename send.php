<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

if (!empty($_POST['company'] ?? '') || !empty($_POST['website'] ?? '')) {
    header('Location: /thank-you.html');
    exit;
}

if (empty($_POST['privacy_consent'] ?? '')) {
    http_response_code(400);
    exit('Необходимо согласие на обработку персональных данных.');
}

$name = trim((string)($_POST['Имя'] ?? ''));
$phone = trim((string)($_POST['Телефон'] ?? ''));
$service = trim((string)($_POST['Направление'] ?? ''));
$comment = trim((string)($_POST['Комментарий'] ?? ''));

if ($name === '' || $phone === '' || $service === '') {
    http_response_code(400);
    exit('Заполните имя, телефон и направление.');
}

$clean = static function (string $value): string {
    $value = strip_tags($value);
    $value = preg_replace('/[\r\n]+/', ' ', $value) ?? $value;
    return trim($value);
};

$name = $clean($name);
$phone = $clean($phone);
$service = $clean($service);
$comment = $clean($comment);

$phoneDigits = preg_replace('/\D+/', '', $phone) ?? '';
if (!preg_match('/^\+7[\s\-]?\(?\d{3}\)?[\s\-]?\d{3}[\s\-]?\d{2}[\s\-]?\d{2}$/', $phone) || !preg_match('/^7\d{10}$/', $phoneDigits)) {
    http_response_code(400);
    exit('Введите российский номер телефона в формате +7.');
}

$to = 'alexandrdiamondzeya@yandex.com';
$subject = 'Новая заявка с сайта Центра здоровой семьи';
$site = 'zdorovyezeya.ru';
$date = date('d.m.Y H:i');
$from = $to;

$message = "Новая заявка с сайта {$site}\n\n"
    . "Дата: {$date}\n"
    . "Имя: {$name}\n"
    . "Телефон / MAX: {$phone}\n"
    . "Направление: {$service}\n"
    . "Комментарий: " . ($comment !== '' ? $comment : 'Не указан') . "\n";

$headers = [
    "From: Центр здоровой семьи <{$from}>",
    "Reply-To: {$to}",
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: PHP/' . phpversion(),
];

$sent = mail($to, $subject, $message, implode("\r\n", $headers), "-f {$from}");

if (!$sent) {
    http_response_code(500);
    exit('Не удалось отправить заявку. Пожалуйста, позвоните по телефону +7 914 380-74-12.');
}

header('Location: /thank-you.html');
exit;
