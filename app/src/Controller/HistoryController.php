<?php

namespace App\Controller;

use App\Repository\GenerationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/history')]
class HistoryController extends AbstractController
{
    #[Route('/', name: 'app_history')]
    public function index(GenerationRepository $generationRepository): Response
    {
        $user = $this->getUser();
        $generations = $generationRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('history/index.html.twig', [
            'generations' => $generations,
        ]);
    }
}
