<?php

namespace App\Service;

use App\Entity\User;

class FeatureAccessChecker
{
    // Features disponibles par plan
    private const PLAN_FEATURES = [
        'FREE' => ['url', 'html'],
        'BASIC' => ['url', 'html', 'office', 'markdown'],
        'PREMIUM' => ['url', 'html', 'office', 'markdown', 'merge', 'screenshot', 'wysiwyg'],
    ];

    // Mapping route -> feature
    private const ROUTE_FEATURE_MAP = [
        'app_convert_url' => 'url',
        'app_convert_html' => 'html',
        'app_convert_office' => 'office',
        'app_convert_markdown' => 'markdown',
        'app_convert_merge' => 'merge',
        'app_convert_screenshot' => 'screenshot',
        'app_convert_wysiwyg' => 'wysiwyg',
    ];

    // Informations sur les features pour l'affichage
    private const FEATURE_INFO = [
        'url' => [
            'name' => 'URL vers PDF',
            'description' => 'Convertir une page web en PDF',
            'icon' => 'link',
        ],
        'html' => [
            'name' => 'HTML vers PDF',
            'description' => 'Convertir du code HTML en PDF',
            'icon' => 'code',
        ],
        'office' => [
            'name' => 'Office vers PDF',
            'description' => 'Convertir Word, Excel, PowerPoint',
            'icon' => 'file-text',
        ],
        'markdown' => [
            'name' => 'Markdown vers PDF',
            'description' => 'Convertir du Markdown en PDF',
            'icon' => 'file',
        ],
        'merge' => [
            'name' => 'Fusionner des PDF',
            'description' => 'Combiner plusieurs PDF',
            'icon' => 'layers',
        ],
        'screenshot' => [
            'name' => 'Capture d\'ecran',
            'description' => 'Capturer une page web en PNG',
            'icon' => 'camera',
        ],
        'wysiwyg' => [
            'name' => 'Editeur WYSIWYG',
            'description' => 'Creer un document avec editeur visuel',
            'icon' => 'edit',
        ],
    ];

    /**
     * Retourne le nom du plan de l'utilisateur
     */
    public function getUserPlanName(?User $user): string
    {
        if (!$user) {
            return 'FREE';
        }

        $plan = $user->getPlan();
        if (!$plan) {
            return 'FREE';
        }

        return strtoupper($plan->getName());
    }

    /**
     * Retourne les features disponibles pour un plan donné
     */
    public function getFeaturesForPlan(string $planName): array
    {
        $planName = strtoupper($planName);
        return self::PLAN_FEATURES[$planName] ?? self::PLAN_FEATURES['FREE'];
    }

    /**
     * Vérifie si un utilisateur a accès à une feature
     */
    public function canAccessFeature(?User $user, string $feature): bool
    {
        $planName = $this->getUserPlanName($user);
        $allowedFeatures = $this->getFeaturesForPlan($planName);

        return in_array($feature, $allowedFeatures, true);
    }

    /**
     * Vérifie si un utilisateur a accès à une route
     */
    public function canAccessRoute(?User $user, string $routeName): bool
    {
        $feature = self::ROUTE_FEATURE_MAP[$routeName] ?? null;

        if ($feature === null) {
            // Route non mappée = accès libre
            return true;
        }

        return $this->canAccessFeature($user, $feature);
    }

    /**
     * Retourne la feature correspondant à une route
     */
    public function getFeatureForRoute(string $routeName): ?string
    {
        return self::ROUTE_FEATURE_MAP[$routeName] ?? null;
    }

    /**
     * Retourne toutes les features avec leur statut d'accès pour un utilisateur
     */
    public function getAllFeaturesWithAccess(?User $user): array
    {
        $planName = $this->getUserPlanName($user);
        $allowedFeatures = $this->getFeaturesForPlan($planName);
        $result = [];

        foreach (self::FEATURE_INFO as $feature => $info) {
            $result[$feature] = array_merge($info, [
                'accessible' => in_array($feature, $allowedFeatures, true),
                'route' => array_search($feature, self::ROUTE_FEATURE_MAP) ?: null,
            ]);
        }

        return $result;
    }

    /**
     * Retourne le plan minimum requis pour une feature
     */
    public function getMinimumPlanForFeature(string $feature): string
    {
        foreach (self::PLAN_FEATURES as $planName => $features) {
            if (in_array($feature, $features, true)) {
                return $planName;
            }
        }

        return 'PREMIUM';
    }

    /**
     * Retourne la liste de tous les plans disponibles
     */
    public function getAvailablePlans(): array
    {
        return array_keys(self::PLAN_FEATURES);
    }
}
