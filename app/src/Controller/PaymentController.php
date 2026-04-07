<?php

namespace App\Controller;

use App\Entity\Plan;
use App\Entity\User;
use App\Repository\PlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/payment')]
class PaymentController extends AbstractController
{
    private string $stripeSecretKey;

    public function __construct(
        private ParameterBagInterface $params,
        private EntityManagerInterface $entityManager,
        private PlanRepository $planRepository,
    ) {
        $this->stripeSecretKey = $this->params->get('stripe_secret_key');
    }

    #[Route('/checkout/{planId}', name: 'app_payment_checkout', methods: ['GET'])]
    public function checkout(int $planId): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $plan = $this->planRepository->find($planId);

        if (!$plan || !$plan->isActive()) {
            $this->addFlash('error', 'Plan non disponible.');
            return $this->redirectToRoute('app_subscription');
        }

        if ($plan->getRole() === 'ROLE_USER') {
            $this->addFlash('info', 'Le plan gratuit ne necessite pas de paiement.');
            return $this->redirectToRoute('app_subscription');
        }

        /** @var User $user */
        $user = $this->getUser();

        $stripePriceId = $plan->getStripePriceId();
        if (!$stripePriceId) {
            $this->addFlash('error', 'Ce plan n\'est pas configure pour le paiement Stripe.');
            return $this->redirectToRoute('app_subscription');
        }

        try {
            // Create Payment Link
            $data = http_build_query([
                'line_items[0][price]' => $stripePriceId,
                'line_items[0][quantity]' => 1,
                'after_completion[type]' => 'redirect',
                'after_completion[redirect][url]' => 'http://localhost:8080/payment/success?plan_id=' . $plan->getId() . '&user_id=' . $user->getId(),
                'metadata[user_id]' => (string) $user->getId(),
                'metadata[plan_id]' => (string) $plan->getId(),
            ]);

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Authorization: Basic ' . base64_encode($this->stripeSecretKey . ':'),
                        'Content-Type: application/x-www-form-urlencoded',
                    ],
                    'content' => $data,
                ]
            ]);

            $result = file_get_contents('https://api.stripe.com/v1/payment_links', false, $context);

            if ($result === false) {
                throw new \Exception('Erreur de connexion a Stripe');
            }

            $paymentLink = json_decode($result, true);

            if (isset($paymentLink['error'])) {
                throw new \Exception($paymentLink['error']['message']);
            }

            if (isset($paymentLink['url'])) {
                return $this->redirect($paymentLink['url']);
            }

            throw new \Exception('No payment link URL returned');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur Stripe: ' . $e->getMessage());
            return $this->redirectToRoute('app_subscription');
        }
    }

    #[Route('/success', name: 'app_payment_success', methods: ['GET'])]
    public function success(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $planId = $request->query->get('plan_id');
        $userId = $request->query->get('user_id');

        /** @var User $user */
        $user = $this->getUser();

        // Verify user matches
        if ($userId && (int)$userId !== $user->getId()) {
            $this->addFlash('error', 'Erreur de verification.');
            return $this->redirectToRoute('app_subscription');
        }

        $plan = $planId ? $this->planRepository->find($planId) : null;

        if ($plan) {
            $user->setPlan($plan);
            $user->setRoles([$plan->getRole()]);
            $this->entityManager->flush();
        }

        return $this->render('payment/success.html.twig', [
            'plan' => $plan,
        ]);
    }

    #[Route('/cancel', name: 'app_payment_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        return $this->render('payment/cancel.html.twig');
    }

    #[Route('/webhook', name: 'app_payment_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        $payload = $request->getContent();
        $event = json_decode($payload, true);

        if (!$event || !isset($event['type'])) {
            return new Response('Invalid payload', 400);
        }

        if ($event['type'] === 'checkout.session.completed') {
            $session = $event['data']['object'];
            $userId = $session['metadata']['user_id'] ?? null;
            $planId = $session['metadata']['plan_id'] ?? null;

            if ($userId && $planId) {
                $user = $this->entityManager->getRepository(User::class)->find($userId);
                $plan = $this->planRepository->find($planId);

                if ($user && $plan) {
                    $user->setPlan($plan);
                    $user->setRoles([$plan->getRole()]);
                    $this->entityManager->flush();
                }
            }
        }

        return new Response('OK', 200);
    }
}
