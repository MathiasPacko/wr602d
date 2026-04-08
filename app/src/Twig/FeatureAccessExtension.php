<?php

namespace App\Twig;

use App\Entity\User;
use App\Service\FeatureAccessChecker;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FeatureAccessExtension extends AbstractExtension
{
    public function __construct(
        private FeatureAccessChecker $featureAccessChecker,
        private Security $security
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('can_access_feature', [$this, 'canAccessFeature']),
            new TwigFunction('can_access_route', [$this, 'canAccessRoute']),
            new TwigFunction('get_all_features', [$this, 'getAllFeatures']),
            new TwigFunction('get_minimum_plan', [$this, 'getMinimumPlan']),
            new TwigFunction('get_user_plan_name', [$this, 'getUserPlanName']),
        ];
    }

    public function canAccessFeature(string $feature): bool
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        return $this->featureAccessChecker->canAccessFeature($user, $feature);
    }

    public function canAccessRoute(string $routeName): bool
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        return $this->featureAccessChecker->canAccessRoute($user, $routeName);
    }

    public function getAllFeatures(): array
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        return $this->featureAccessChecker->getAllFeaturesWithAccess($user);
    }

    public function getMinimumPlan(string $feature): string
    {
        return $this->featureAccessChecker->getMinimumPlanForFeature($feature);
    }

    public function getUserPlanName(): string
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        return $this->featureAccessChecker->getUserPlanName($user);
    }
}
