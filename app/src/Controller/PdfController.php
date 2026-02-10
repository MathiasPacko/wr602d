<?php

namespace App\Controller;

use App\Entity\Generation;
use App\Repository\GenerationRepository;
use App\Service\GotenbergService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/pdf')]
class PdfController extends AbstractController
{
    public function __construct(
        private GotenbergService $gotenbergService,
        private GenerationRepository $generationRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'app_pdf_index')]
    public function index(): Response
    {
        // Vérifier si Gotenberg est accessible
        $gotenbergStatus = $this->gotenbergService->healthCheck();

        return $this->render('pdf/index.html.twig', [
            'gotenberg_status' => $gotenbergStatus,
        ]);
    }

    #[Route('/convert/html', name: 'app_pdf_convert_html', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function convertHtml(Request $request): Response
    {
        // Vérifier la limite de génération
        if (!$this->canUserGenerate()) {
            $this->addFlash('error', 'Vous avez atteint votre limite de génération quotidienne.');
            return $this->redirectToRoute('app_pdf_index');
        }

        if ($request->isMethod('POST')) {
            $htmlContent = $request->request->get('html_content');

            if (empty($htmlContent)) {
                $this->addFlash('error', 'Le contenu HTML est requis.');
                return $this->redirectToRoute('app_pdf_convert_html');
            }

            try {
                // Options de page optionnelles
                $options = [
                    'marginTop' => $request->request->get('margin_top', '1'),
                    'marginBottom' => $request->request->get('margin_bottom', '1'),
                    'marginLeft' => $request->request->get('margin_left', '1'),
                    'marginRight' => $request->request->get('margin_right', '1'),
                ];

                $pdfContent = $this->gotenbergService->convertHtmlToPdf($htmlContent, $options);

                // Enregistrer la génération
                $this->saveGeneration('html_conversion_' . date('Y-m-d_H-i-s') . '.pdf');

                return new Response($pdfContent, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="document.pdf"',
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la conversion: ' . $e->getMessage());
                return $this->redirectToRoute('app_pdf_convert_html');
            }
        }

        return $this->render('pdf/convert_html.html.twig');
    }

    #[Route('/convert/url', name: 'app_pdf_convert_url', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function convertUrl(Request $request): Response
    {
        // Vérifier la limite de génération
        if (!$this->canUserGenerate()) {
            $this->addFlash('error', 'Vous avez atteint votre limite de génération quotidienne.');
            return $this->redirectToRoute('app_pdf_index');
        }

        if ($request->isMethod('POST')) {
            $url = $request->request->get('url');

            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                $this->addFlash('error', 'Une URL valide est requise.');
                return $this->redirectToRoute('app_pdf_convert_url');
            }

            try {
                $pdfContent = $this->gotenbergService->convertUrlToPdf($url);

                // Enregistrer la génération
                $this->saveGeneration('url_conversion_' . date('Y-m-d_H-i-s') . '.pdf');

                return new Response($pdfContent, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="webpage.pdf"',
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la conversion: ' . $e->getMessage());
                return $this->redirectToRoute('app_pdf_convert_url');
            }
        }

        return $this->render('pdf/convert_url.html.twig');
    }

    #[Route('/convert/office', name: 'app_pdf_convert_office', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function convertOffice(Request $request): Response
    {
        // Vérifier la limite de génération
        if (!$this->canUserGenerate()) {
            $this->addFlash('error', 'Vous avez atteint votre limite de génération quotidienne.');
            return $this->redirectToRoute('app_pdf_index');
        }

        if ($request->isMethod('POST')) {
            $file = $request->files->get('file');

            if (!$file) {
                $this->addFlash('error', 'Un fichier est requis.');
                return $this->redirectToRoute('app_pdf_convert_office');
            }

            try {
                $pdfContent = $this->gotenbergService->convertOfficeToPdf($file);

                // Enregistrer la génération
                $this->saveGeneration('office_conversion_' . date('Y-m-d_H-i-s') . '.pdf');

                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                return new Response($pdfContent, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => sprintf('attachment; filename="%s.pdf"', $originalName),
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la conversion: ' . $e->getMessage());
                return $this->redirectToRoute('app_pdf_convert_office');
            }
        }

        return $this->render('pdf/convert_office.html.twig');
    }

    #[Route('/health', name: 'app_pdf_health')]
    public function health(): Response
    {
        $isHealthy = $this->gotenbergService->healthCheck();

        return $this->json([
            'status' => $isHealthy ? 'ok' : 'error',
            'gotenberg' => $isHealthy,
        ]);
    }

    /**
     * Vérifie si l'utilisateur peut encore générer des PDF aujourd'hui
     */
    private function canUserGenerate(): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        $plan = $user->getPlan();
        if (!$plan) {
            // Pas de plan = limite par défaut (2)
            $limit = 2;
        } else {
            $limit = $plan->getLimitGeneration();
        }

        // -1 signifie illimité
        if ($limit === -1) {
            return true;
        }

        $todayCount = $this->generationRepository->countTodayGenerationsByUser($user);

        return $todayCount < $limit;
    }

    /**
     * Enregistre une génération en base de données
     */
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
}
