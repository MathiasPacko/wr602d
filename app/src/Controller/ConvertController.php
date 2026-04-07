<?php

namespace App\Controller;

use App\Entity\Generation;
use App\Entity\User;
use App\Form\UrlToPdfType;
use App\Repository\GenerationRepository;
use App\Repository\UserContactRepository;
use App\Service\GotenbergService;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use League\CommonMark\CommonMarkConverter;

#[Route('/convert')]
#[IsGranted('ROLE_USER')]
class ConvertController extends AbstractController
{
    public function __construct(
        private GotenbergService $gotenbergService,
        private GenerationRepository $generationRepository,
        private EntityManagerInterface $entityManager,
        private MailerService $mailerService,
        private UserContactRepository $contactRepository
    ) {
    }

    #[Route('/html', name: 'app_convert_html', methods: ['GET', 'POST'])]
    public function convertHtml(Request $request): Response
    {
        if (!$this->canUserGenerate()) {
            $this->addFlash('error', 'Vous avez atteint votre limite de generation quotidienne.');
            return $this->redirectToRoute('app_pdf_index');
        }

        if ($request->isMethod('POST')) {
            $htmlContent = $request->request->get('html_content');

            if (empty($htmlContent)) {
                $this->addFlash('error', 'Le contenu HTML est requis.');
                return $this->redirectToRoute('app_convert_html');
            }

            try {
                $options = [
                    'marginTop' => $request->request->get('margin_top', '1'),
                    'marginBottom' => $request->request->get('margin_bottom', '1'),
                    'marginLeft' => $request->request->get('margin_left', '1'),
                    'marginRight' => $request->request->get('margin_right', '1'),
                ];

                $pdfContent = $this->gotenbergService->convertHtmlToPdf($htmlContent, $options);
                $filename = 'html_conversion_' . date('Y-m-d_H-i-s') . '.pdf';

                $this->saveGeneration($filename);
                $this->handleEmailOptions($request, $pdfContent, $filename, 'HTML');

                return new Response($pdfContent, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="document.pdf"',
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la conversion: ' . $e->getMessage());
                return $this->redirectToRoute('app_convert_html');
            }
        }

        return $this->render('pdf/convert_html.html.twig', [
            'contacts' => $this->getUserContacts(),
        ]);
    }

    #[Route('/url', name: 'app_convert_url', methods: ['GET', 'POST'])]
    public function convertUrl(Request $request): Response
    {
        if (!$this->canUserGenerate()) {
            $this->addFlash('error', 'Vous avez atteint votre limite de generation quotidienne.');
            return $this->redirectToRoute('app_pdf_index');
        }

        if ($request->isMethod('POST')) {
            $url = $request->request->get('url');

            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                $this->addFlash('error', 'Une URL valide est requise.');
                return $this->redirectToRoute('app_convert_url');
            }

            try {
                $pdfContent = $this->gotenbergService->convertUrlToPdf($url);
                $filename = 'url_conversion_' . date('Y-m-d_H-i-s') . '.pdf';

                $this->saveGeneration($filename);
                $this->handleEmailOptions($request, $pdfContent, $filename, 'URL');

                return new Response($pdfContent, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="webpage.pdf"',
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la conversion: ' . $e->getMessage());
                return $this->redirectToRoute('app_convert_url');
            }
        }

        return $this->render('pdf/convert_url.html.twig', [
            'contacts' => $this->getUserContacts(),
        ]);
    }

    #[Route('/office', name: 'app_convert_office', methods: ['GET', 'POST'])]
    public function convertOffice(Request $request): Response
    {
        if (!$this->canUserGenerate()) {
            $this->addFlash('error', 'Vous avez atteint votre limite de generation quotidienne.');
            return $this->redirectToRoute('app_pdf_index');
        }

        if ($request->isMethod('POST')) {
            $file = $request->files->get('file');

            if (!$file) {
                $this->addFlash('error', 'Un fichier est requis.');
                return $this->redirectToRoute('app_convert_office');
            }

            try {
                $pdfContent = $this->gotenbergService->convertOfficeToPdf($file);
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $filename = $originalName . '_' . date('Y-m-d_H-i-s') . '.pdf';

                $this->saveGeneration($filename);
                $this->handleEmailOptions($request, $pdfContent, $filename, 'Office');

                return new Response($pdfContent, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => sprintf('attachment; filename="%s.pdf"', $originalName),
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la conversion: ' . $e->getMessage());
                return $this->redirectToRoute('app_convert_office');
            }
        }

        return $this->render('pdf/convert_office.html.twig', [
            'contacts' => $this->getUserContacts(),
        ]);
    }

    #[Route('/markdown', name: 'app_convert_markdown', methods: ['GET', 'POST'])]
    public function convertMarkdown(Request $request): Response
    {
        if (!$this->canUserGenerate()) {
            $this->addFlash('error', 'Vous avez atteint votre limite de generation quotidienne.');
            return $this->redirectToRoute('app_pdf_index');
        }

        if ($request->isMethod('POST')) {
            $markdownContent = $request->request->get('markdown_content');

            if (empty($markdownContent)) {
                $this->addFlash('error', 'Le contenu Markdown est requis.');
                return $this->redirectToRoute('app_convert_markdown');
            }

            try {
                $converter = new CommonMarkConverter([
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ]);
                $htmlBody = $converter->convert($markdownContent);

                $htmlContent = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            color: #1a1a1a;
        }
        h1, h2, h3, h4, h5, h6 { margin-top: 1.5em; margin-bottom: 0.5em; }
        h1 { font-size: 2em; border-bottom: 2px solid #e1e1e1; padding-bottom: 0.3em; }
        h2 { font-size: 1.5em; border-bottom: 1px solid #e1e1e1; padding-bottom: 0.3em; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-size: 0.9em; }
        pre { background: #f4f4f4; padding: 16px; border-radius: 6px; overflow-x: auto; }
        pre code { background: none; padding: 0; }
        blockquote { border-left: 4px solid #3b82f6; margin: 1em 0; padding-left: 1em; color: #555; }
        table { border-collapse: collapse; width: 100%; margin: 1em 0; }
        th, td { border: 1px solid #ddd; padding: 8px 12px; text-align: left; }
        th { background: #f4f4f4; }
        img { max-width: 100%; height: auto; }
        a { color: #3b82f6; }
    </style>
</head>
<body>
{$htmlBody}
</body>
</html>
HTML;

                $options = [
                    'marginTop' => $request->request->get('margin_top', '1'),
                    'marginBottom' => $request->request->get('margin_bottom', '1'),
                    'marginLeft' => $request->request->get('margin_left', '1'),
                    'marginRight' => $request->request->get('margin_right', '1'),
                ];

                $pdfContent = $this->gotenbergService->convertHtmlToPdf($htmlContent, $options);
                $filename = 'markdown_conversion_' . date('Y-m-d_H-i-s') . '.pdf';

                $this->saveGeneration($filename);
                $this->handleEmailOptions($request, $pdfContent, $filename, 'Markdown');

                return new Response($pdfContent, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="document.pdf"',
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la conversion: ' . $e->getMessage());
                return $this->redirectToRoute('app_convert_markdown');
            }
        }

        return $this->render('pdf/convert_markdown.html.twig', [
            'contacts' => $this->getUserContacts(),
        ]);
    }

    #[Route('/merge', name: 'app_convert_merge', methods: ['GET', 'POST'])]
    public function convertMerge(Request $request): Response
    {
        if (!$this->canUserGenerate()) {
            $this->addFlash('error', 'Vous avez atteint votre limite de generation quotidienne.');
            return $this->redirectToRoute('app_pdf_index');
        }

        if ($request->isMethod('POST')) {
            $files = $request->files->get('pdf_files');

            if (!$files || count($files) < 2) {
                $this->addFlash('error', 'Veuillez selectionner au moins 2 fichiers PDF a fusionner.');
                return $this->redirectToRoute('app_convert_merge');
            }

            foreach ($files as $file) {
                if ($file->getMimeType() !== 'application/pdf') {
                    $this->addFlash('error', 'Tous les fichiers doivent etre des PDF.');
                    return $this->redirectToRoute('app_convert_merge');
                }
            }

            try {
                $pdfContent = $this->gotenbergService->mergePdfFiles($files);
                $filename = 'merge_' . date('Y-m-d_H-i-s') . '.pdf';

                $this->saveGeneration($filename);
                $this->handleEmailOptions($request, $pdfContent, $filename, 'Fusion');

                return new Response($pdfContent, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="merged.pdf"',
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la fusion: ' . $e->getMessage());
                return $this->redirectToRoute('app_convert_merge');
            }
        }

        return $this->render('pdf/convert_merge.html.twig', [
            'contacts' => $this->getUserContacts(),
        ]);
    }

    #[Route('/screenshot', name: 'app_convert_screenshot', methods: ['GET', 'POST'])]
    public function screenshot(Request $request): Response
    {
        if (!$this->canUserGenerate()) {
            $this->addFlash('error', 'Vous avez atteint votre limite de generation quotidienne.');
            return $this->redirectToRoute('app_pdf_index');
        }

        if ($request->isMethod('POST')) {
            $url = $request->request->get('url');

            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                $this->addFlash('error', 'Une URL valide est requise.');
                return $this->redirectToRoute('app_convert_screenshot');
            }

            try {
                $options = [
                    'width' => $request->request->get('width', '1920'),
                    'height' => $request->request->get('height', '1080'),
                ];

                $imageContent = $this->gotenbergService->screenshotUrl($url, $options);
                $filename = 'screenshot_' . date('Y-m-d_H-i-s') . '.png';

                $this->saveGeneration($filename);

                // Envoi par email si demandé
                /** @var User $user */
                $user = $this->getUser();
                $sendToMe = $request->request->getBoolean('send_to_me');

                if ($sendToMe) {
                    try {
                        $this->mailerService->sendScreenshotToUser($user, $imageContent, $filename);
                        $this->addFlash('success', 'La capture a ete envoyee a votre adresse email.');
                    } catch (\Exception $e) {
                        $this->addFlash('warning', 'La capture n\'a pas pu etre envoyee par email.');
                    }
                }

                return new Response($imageContent, 200, [
                    'Content-Type' => 'image/png',
                    'Content-Disposition' => 'attachment; filename="screenshot.png"',
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la capture: ' . $e->getMessage());
                return $this->redirectToRoute('app_convert_screenshot');
            }
        }

        return $this->render('pdf/convert_screenshot.html.twig');
    }

    #[Route('/wysiwyg', name: 'app_convert_wysiwyg', methods: ['GET', 'POST'])]
    public function convertWysiwyg(Request $request): Response
    {
        if (!$this->canUserGenerate()) {
            $this->addFlash('error', 'Vous avez atteint votre limite de generation quotidienne.');
            return $this->redirectToRoute('app_pdf_index');
        }

        if ($request->isMethod('POST')) {
            $htmlContent = $request->request->get('wysiwyg_content');

            if (empty($htmlContent)) {
                $this->addFlash('error', 'Le contenu est requis.');
                return $this->redirectToRoute('app_convert_wysiwyg');
            }

            try {
                $fullHtml = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            color: #1a1a1a;
        }
        img { max-width: 100%; height: auto; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; }
    </style>
</head>
<body>
{$htmlContent}
</body>
</html>
HTML;

                $options = [
                    'marginTop' => $request->request->get('margin_top', '1'),
                    'marginBottom' => $request->request->get('margin_bottom', '1'),
                    'marginLeft' => $request->request->get('margin_left', '1'),
                    'marginRight' => $request->request->get('margin_right', '1'),
                ];

                $pdfContent = $this->gotenbergService->convertHtmlToPdf($fullHtml, $options);
                $filename = 'wysiwyg_' . date('Y-m-d_H-i-s') . '.pdf';

                $this->saveGeneration($filename);
                $this->handleEmailOptions($request, $pdfContent, $filename, 'WYSIWYG');

                return new Response($pdfContent, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="document.pdf"',
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la conversion: ' . $e->getMessage());
                return $this->redirectToRoute('app_convert_wysiwyg');
            }
        }

        return $this->render('pdf/convert_wysiwyg.html.twig', [
            'contacts' => $this->getUserContacts(),
        ]);
    }

    /**
     * Gère les options d'envoi par email (à soi-même et aux contacts)
     */
    private function handleEmailOptions(Request $request, string $pdfContent, string $filename, string $conversionType): void
    {
        /** @var User $user */
        $user = $this->getUser();

        // Envoi à l'utilisateur lui-même
        $sendToMe = $request->request->getBoolean('send_to_me');
        if ($sendToMe) {
            try {
                $this->mailerService->sendPdfToUser($user, $pdfContent, $filename, $conversionType);
                $this->addFlash('success', 'Le PDF a ete envoye a votre adresse email.');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Le PDF n\'a pas pu etre envoye par email.');
            }
        }

        // Envoi aux contacts sélectionnés
        $contactIds = $request->request->all('send_to_contacts');
        $message = $request->request->get('contact_message');

        if (!empty($contactIds)) {
            $contacts = $this->contactRepository->findBy([
                'id' => $contactIds,
                'user' => $user,
            ]);

            $sentCount = 0;
            foreach ($contacts as $contact) {
                try {
                    $this->mailerService->sendPdfToContact($user, $contact, $pdfContent, $filename, $message);
                    $sentCount++;
                } catch (\Exception $e) {
                    // Continue avec les autres contacts
                }
            }

            if ($sentCount > 0) {
                $this->addFlash('success', sprintf('Le PDF a ete envoye a %d contact(s).', $sentCount));
            }
        }
    }

    private function canUserGenerate(): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        $plan = $user->getPlan();
        if (!$plan) {
            $limit = 2;
        } else {
            $limit = $plan->getLimitGeneration();
        }

        if ($limit === -1) {
            return true;
        }

        $todayCount = $this->generationRepository->countTodayGenerationsByUser($user);

        return $todayCount < $limit;
    }

    private function saveGeneration(string $filename): void
    {
        $user = $this->getUser();
        if (!$user) {
            return;
        }

        $generation = new Generation();
        $generation->setUser($user);
        $generation->setFile($filename);

        $this->entityManager->persist($generation);
        $this->entityManager->flush();
    }

    private function getUserContacts(): array
    {
        $user = $this->getUser();
        if (!$user) {
            return [];
        }

        return $this->contactRepository->findBy(['user' => $user], ['lastname' => 'ASC']);
    }
}
