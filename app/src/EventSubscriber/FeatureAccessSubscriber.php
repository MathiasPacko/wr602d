<?php

namespace App\EventSubscriber;

use App\Service\FeatureAccessChecker;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class FeatureAccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FeatureAccessChecker $featureAccessChecker,
        private Security $security
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');

        // Vérifier uniquement les routes /convert/*
        if (!str_starts_with($routeName ?? '', 'app_convert_')) {
            return;
        }

        $user = $this->security->getUser();

        // Vérifier si l'utilisateur a accès à cette feature
        if (!$this->featureAccessChecker->canAccessRoute($user, $routeName)) {
            $feature = $this->featureAccessChecker->getFeatureForRoute($routeName);
            $minimumPlan = $this->featureAccessChecker->getMinimumPlanForFeature($feature);

            throw new AccessDeniedHttpException(
                sprintf(
                    'Cette fonctionnalite necessite un abonnement %s ou superieur.',
                    $minimumPlan
                )
            );
        }
    }
}
