<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserContact;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private string $mailerFromEmail,
        private string $mailerFromName
    ) {
    }

    /**
     * Envoie un email de réinitialisation de mot de passe
     */
    public function sendResetPasswordEmail(User $user, string $token): void
    {
        $resetUrl = $this->urlGenerator->generate(
            'app_reset_password',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $html = $this->twig->render('emails/reset_password.html.twig', [
            'user' => $user,
            'resetUrl' => $resetUrl,
        ]);

        $email = (new Email())
            ->from(new Address($this->mailerFromEmail, $this->mailerFromName))
            ->to($user->getEmail())
            ->subject('Reinitialisation de votre mot de passe - PDF Converter')
            ->html($html);

        $this->mailer->send($email);
    }

    /**
     * Envoie un PDF généré à l'utilisateur
     */
    public function sendPdfToUser(User $user, string $pdfContent, string $filename, string $conversionType): void
    {
        $html = $this->twig->render('emails/pdf_generated.html.twig', [
            'user' => $user,
            'filename' => $filename,
            'conversionType' => $conversionType,
        ]);

        $email = (new Email())
            ->from(new Address($this->mailerFromEmail, $this->mailerFromName))
            ->to($user->getEmail())
            ->subject('Votre document PDF - ' . $filename)
            ->html($html)
            ->attach($pdfContent, $filename, 'application/pdf');

        $this->mailer->send($email);
    }

    /**
     * Envoie un PDF à un contact
     */
    public function sendPdfToContact(
        User $sender,
        UserContact $contact,
        string $pdfContent,
        string $filename,
        ?string $message = null
    ): void {
        $html = $this->twig->render('emails/pdf_shared.html.twig', [
            'sender' => $sender,
            'contact' => $contact,
            'filename' => $filename,
            'message' => $message,
        ]);

        $email = (new Email())
            ->from(new Address($this->mailerFromEmail, $this->mailerFromName))
            ->replyTo($sender->getEmail())
            ->to($contact->getEmail())
            ->subject($sender->getFirstname() . ' vous a envoye un document PDF')
            ->html($html)
            ->attach($pdfContent, $filename, 'application/pdf');

        $this->mailer->send($email);
    }

    /**
     * Envoie une capture d'écran à l'utilisateur
     */
    public function sendScreenshotToUser(User $user, string $imageContent, string $filename): void
    {
        $html = $this->twig->render('emails/screenshot_generated.html.twig', [
            'user' => $user,
            'filename' => $filename,
        ]);

        $email = (new Email())
            ->from(new Address($this->mailerFromEmail, $this->mailerFromName))
            ->to($user->getEmail())
            ->subject('Votre capture d\'ecran - ' . $filename)
            ->html($html)
            ->attach($imageContent, $filename, 'image/png');

        $this->mailer->send($email);
    }
}
