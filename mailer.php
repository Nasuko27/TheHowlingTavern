<?php

require_once '/home/ubuntu/vendor/phpmailer/phpmailer/src/Exception.php';
require_once '/home/ubuntu/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '/home/ubuntu/vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/secrets.php';

define('MAIL_FROM_NAME', 'The Howling Tavern');

function send_verification_mail(string $email, string $login, string $code) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_FROM;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($email, $login);

        $mail->isHTML(true);
        $mail->Subject = '[The Howling Tavern] Votre code de vérification';
        $mail->Body    = '
            <div style="font-family:sans-serif;max-width:500px;margin:auto;background:#1a1d2e;color:#e0e0e0;padding:2rem;border-radius:10px">
                <h2 style="color:#7c9dff">Bienvenue, ' . htmlspecialchars($login) . ' !</h2>
                <p>Voici ton code de vérification à 6 chiffres pour activer ton compte :</p>
                <div style="text-align:center;margin:1.5rem 0">
                    <span style="display:inline-block;letter-spacing:.5rem;font-size:2.5rem;font-weight:700;background:#2a2d3e;padding:.75rem 1.5rem;border-radius:8px;color:#7c9dff">' . htmlspecialchars($code) . '</span>
                </div>
                <p style="color:#666;font-size:.85rem">Saisis ce code sur la page de vérification pour activer ton compte.</p>
                <p style="color:#666;font-size:.85rem">Si tu n\'as pas créé de compte, ignore ce mail.</p>
            </div>
        ';
        $mail->AltBody = "Ton code de vérification : $code";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo ?: $e->getMessage();
    }
}

function send_newsletter_mail(string $email, string $login, string $titre, string $contenu, int $article_id = 0) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_FROM;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($email, $login);

        $mail->isHTML(true);
        $mail->Subject = '[The Howling Tavern] ' . $titre;
        $mail->Body    = '
            <div style="font-family:sans-serif;max-width:600px;margin:auto;background:#1a0a04;color:#e0e0e0;padding:2rem;border-radius:10px;border:1px solid #620D14">
                <h1 style="font-family:Georgia,serif;color:#c0392b;border-bottom:1px solid #620D14;padding-bottom:.75rem;margin-top:0">' . htmlspecialchars($titre) . '</h1>
                <p style="line-height:1.7;white-space:pre-line">' . nl2br(htmlspecialchars($contenu)) . '</p>
                <hr style="border-color:#620D14;margin:1.5rem 0">
                <p style="color:#888;font-size:.8rem;text-align:center">
                    Tu re&ccedil;ois cet e-mail car tu es membre de <strong>The Howling Tavern</strong>.<br>
                    <a href="' . SITE_URL . '/newsletter_redirect.php?id=' . $article_id . '" style="color:#c0392b">Voir sur le site</a>
                </p>
            </div>
        ';
        $mail->AltBody = $titre . "\n\n" . $contenu . "\n\nThe Howling Tavern";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo ?: $e->getMessage();
    }
}
