<?php

declare(strict_types=1);

namespace App;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

final class MailerService
{
    /** @param array<string, string> $config */
    public function __construct(private readonly array $config)
    {
    }

    public function isConfigured(): bool
    {
        foreach (['SMTP_HOST', 'SMTP_USERNAME', 'SMTP_PASSWORD', 'SMTP_FROM'] as $key) {
            if (empty($this->config[$key]) || str_contains($this->config[$key], 'exemplo')) {
                return false;
            }
        }
        return true;
    }

    /** @throws Exception */
    public function send(string $recipientName, string $recipientEmail, string $pdfPath, string $code): void
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $this->config['SMTP_HOST'];
        $mail->Port = (int) ($this->config['SMTP_PORT'] ?? 587);
        $mail->SMTPAuth = true;
        $mail->Username = $this->config['SMTP_USERNAME'];
        $mail->Password = $this->config['SMTP_PASSWORD'];
        $mail->SMTPSecure = $this->config['SMTP_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($this->config['SMTP_FROM'], $this->config['SMTP_FROM_NAME'] ?? 'Sistema de Documentos');
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->isHTML(true);
        $mail->Subject = 'Seu comprovante ' . $code;
        $mail->Body = '<p>Olá, ' . htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') . '.</p><p>Seu comprovante está anexado a este e-mail.</p>';
        $mail->addAttachment($pdfPath, basename($pdfPath));
        $mail->send();
    }
}
