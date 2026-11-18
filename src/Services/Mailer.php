<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\App;

/**
 * Envoi des e-mails automatiques.
 *
 *  - Si PHPMailer est installé (composer) et qu'un serveur SMTP est configuré : envoi SMTP authentifié (TLS).
 *  - Sinon, en développement : le mail est écrit dans storage/logs/mails.log (pratique pour tester).
 *  - En dernier recours en production : fonction mail() de PHP (sendmail de l'hébergeur).
 *
 * Les contenus sont générés côté serveur ; toute donnée utilisateur insérée dans le HTML est échappée.
 */
final class Mailer
{
    public function send(string $to, string $subject, string $htmlBody, ?string $replyTo = null): bool
    {
        $cfg = App::config('mail');
        $html = $this->wrap($subject, $htmlBody);

        try {
            if (!empty($cfg['host']) && class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $cfg['host'];
                $mail->Port = $cfg['port'];
                $mail->SMTPAuth = true;
                $mail->Username = (string) $cfg['user'];
                $mail->Password = (string) $cfg['pass'];
                $mail->SMTPSecure = $cfg['port'] === 465 ? 'ssl' : 'tls';
                $mail->CharSet = 'UTF-8';
                $mail->setFrom($cfg['from'], $cfg['from_name']);
                $mail->addAddress($to);
                if ($replyTo) {
                    $mail->addReplyTo($replyTo);
                }
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $html;
                $mail->AltBody = trim(html_entity_decode(strip_tags($htmlBody)));
                return $mail->send();
            }

            if (!App::isProd()) {
                $log = sprintf("=== %s ===\nÀ : %s\nSujet : %s\n%s\n\n", date('c'), $to, $subject, strip_tags($htmlBody));
                file_put_contents(dirname(__DIR__, 2) . '/storage/logs/mails.log', $log, FILE_APPEND);
                return true;
            }

            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $cfg['from_name'] . ' <' . $cfg['from'] . '>',
            ];
            if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $headers[] = 'Reply-To: ' . $replyTo;
            }
            return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, implode("\r\n", $headers));
        } catch (\Throwable $e) {
            error_log('[Mailer] ' . $e->getMessage());
            return false;
        }
    }

    private function wrap(string $title, string $body): string
    {
        $t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        return '<!doctype html><html lang="fr"><body style="font-family:Arial,sans-serif;color:#2b2b2b;background:#fbf6ee;padding:24px">'
            . '<div style="max-width:560px;margin:auto;background:#fff;border-radius:8px;padding:24px">'
            . '<h1 style="color:#7a1f3d;font-size:20px">' . $t . '</h1>' . $body
            . '<p style="margin-top:24px;font-size:13px;color:#6b6b6b">Vite &amp; Gourmand - Traiteur à Bordeaux</p></div></body></html>';
    }

    // ---- Modèles de mails métier ---------------------------------------------------------

    public function welcome(array $user): bool
    {
        return $this->send($user['email'], 'Bienvenue chez Vite & Gourmand',
            '<p>Bonjour ' . e($user['prenom']) . ',</p><p>Votre compte a bien été créé. Vous pouvez dès à présent commander nos menus en ligne.</p>');
    }

    public function passwordReset(string $email, string $link): bool
    {
        return $this->send($email, 'Réinitialisation de votre mot de passe',
            '<p>Vous avez demandé la réinitialisation de votre mot de passe.</p><p><a href="' . e($link) . '">Choisir un nouveau mot de passe</a></p>'
            . '<p>Ce lien est valable 1 heure et ne peut être utilisé qu\'une seule fois. Si vous n\'êtes pas à l\'origine de cette demande, ignorez ce message.</p>');
    }

    public function orderConfirmation(array $order): bool
    {
        return $this->send($order['email'], 'Confirmation de votre commande ' . $order['numero_commande'],
            '<p>Bonjour ' . e($order['prenom']) . ',</p><p>Nous avons bien reçu votre commande <strong>' . e($order['numero_commande']) . '</strong> : '
            . e($order['menu_titre']) . ' pour ' . (int) $order['nombre_personne'] . ' personnes, le ' . e(date_fr($order['date_prestation'])) . ' à '
            . e(substr($order['heure_livraison'], 0, 5)) . '.</p><p>Montant total : <strong>' . e(money($order['prix_total'])) . '</strong>.</p>'
            . '<p>Notre équipe va la valider très prochainement.</p>');
    }

    public function orderFinished(array $order, string $accountUrl): bool
    {
        return $this->send($order['email'], 'Votre avis nous intéresse',
            '<p>Bonjour ' . e($order['prenom']) . ',</p><p>Votre commande ' . e($order['numero_commande']) . ' est terminée. Merci pour votre confiance !</p>'
            . '<p>Connectez-vous à votre espace pour nous laisser un avis : <a href="' . e($accountUrl) . '">' . e($accountUrl) . '</a></p>');
    }

    public function materialReturn(array $order): bool
    {
        return $this->send($order['email'], 'Restitution du matériel prêté',
            '<p>Bonjour ' . e($order['prenom']) . ',</p><p>Du matériel vous a été prêté pour la commande ' . e($order['numero_commande']) . '.</p>'
            . '<p><strong>Si le matériel n\'est pas restitué sous 10 jours ouvrés, des frais de 600 € vous seront facturés</strong>, conformément à nos conditions générales de vente.</p>'
            . '<p>Pour organiser la restitution, merci de prendre contact avec nous par téléphone ou via la page Contact du site.</p>');
    }

    public function orderCancelled(array $order, string $reason): bool
    {
        return $this->send($order['email'], 'Annulation de votre commande ' . $order['numero_commande'],
            '<p>Bonjour ' . e($order['prenom']) . ',</p><p>Suite à notre échange, votre commande ' . e($order['numero_commande']) . ' a été annulée.</p>'
            . '<p>Motif : ' . e($reason) . '</p>');
    }

    public function employeeCreated(string $email): bool
    {
        return $this->send($email, 'Votre compte employé Vite & Gourmand',
            '<p>Bonjour,</p><p>Un compte employé a été créé pour vous sur l\'application Vite & Gourmand (identifiant : ' . e($email) . ').</p>'
            . '<p>Pour des raisons de sécurité, votre mot de passe n\'est pas communiqué par e-mail : rapprochez-vous de l\'administrateur pour l\'obtenir.</p>');
    }

    public function contact(string $title, string $description, string $fromEmail): bool
    {
        return $this->send((string) App::config('mail')['company'], '[Contact site] ' . $title,
            '<p><strong>De :</strong> ' . e($fromEmail) . '</p><p>' . nl2br(e($description)) . '</p>', $fromEmail);
    }
}
