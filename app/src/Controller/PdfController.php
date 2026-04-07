<?php

namespace App\Controller;

use App\Repository\GenerationRepository;
use App\Service\GotenbergService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pdf')]
class PdfController extends AbstractController
{
    public function __construct(
        private GotenbergService $gotenbergService,
        private GenerationRepository $generationRepository
    ) {
    }

    #[Route('/', name: 'app_pdf_index')]
    public function index(): Response
    {
        $gotenbergStatus = $this->gotenbergService->healthCheck();

        $user = $this->getUser();
        $quotaInfo = null;

        if ($user) {
            $plan = $user->getPlan();
            $limit = $plan ? $plan->getLimitGeneration() : 2;
            $todayCount = $this->generationRepository->countTodayGenerationsByUser($user);

            $quotaInfo = [
                'used' => $todayCount,
                'limit' => $limit,
                'unlimited' => $limit === -1,
            ];
        }

        return $this->render('pdf/index.html.twig', [
            'gotenberg_status' => $gotenbergStatus,
            'quota_info' => $quotaInfo,
        ]);
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
}
