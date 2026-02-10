<?php

namespace App\Controller;

use App\Repository\GenerationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(GenerationRepository $generationRepository): Response
    {
        $user = $this->getUser();
        $plan = $user->getPlan();

        // Statistiques utilisateur
        $todayGenerations = $generationRepository->countTodayGenerationsByUser($user);
        $totalGenerations = count($user->getGenerations());

        // Limite quotidienne
        $dailyLimit = $plan ? $plan->getLimitGeneration() : 2;
        $isUnlimited = $dailyLimit === -1;

        return $this->render('home/index.html.twig', [
            'user' => $user,
            'plan' => $plan,
            'today_generations' => $todayGenerations,
            'total_generations' => $totalGenerations,
            'daily_limit' => $dailyLimit,
            'is_unlimited' => $isUnlimited,
        ]);
    }
}
