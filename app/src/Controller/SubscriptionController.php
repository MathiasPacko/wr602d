<?php

namespace App\Controller;

use App\Repository\PlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/subscription')]
class SubscriptionController extends AbstractController
{
    #[Route('/', name: 'app_subscription')]
    public function index(PlanRepository $planRepository): Response
    {
        $plans = $planRepository->findActivePlans();
        $currentPlan = $this->getUser()->getPlan();

        return $this->render('subscription/index.html.twig', [
            'plans' => $plans,
            'current_plan' => $currentPlan,
        ]);
    }

    #[Route('/change/{id}', name: 'app_subscription_change', methods: ['POST'])]
    public function change(
        int $id,
        PlanRepository $planRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $plan = $planRepository->find($id);

        if (!$plan || !$plan->isActive()) {
            $this->addFlash('error', 'Ce plan n\'existe pas ou n\'est plus disponible.');
            return $this->redirectToRoute('app_subscription');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('change_plan_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_subscription');
        }

        $user = $this->getUser();
        $user->setPlan($plan);

        // Mettre à jour le rôle de l'utilisateur selon le plan
        $roles = ['ROLE_USER'];
        if ($plan->getRole() && $plan->getRole() !== 'ROLE_USER') {
            $roles[] = $plan->getRole();
        }
        $user->setRoles($roles);

        $entityManager->flush();

        $this->addFlash('success', sprintf('Vous êtes maintenant abonné au plan %s.', $plan->getName()));

        return $this->redirectToRoute('app_subscription');
    }
}
