<?php

namespace App\DataFixtures;

use App\Entity\Plan;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PlanFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $plans = [
            [
                'name' => 'FREE',
                'description' => 'Plan gratuit avec accès limité. Idéal pour découvrir le service.',
                'limitGeneration' => 2,
                'role' => 'ROLE_USER',
                'price' => '0.00',
                'active' => true,
            ],
            [
                'name' => 'BASIC',
                'description' => 'Plan basique pour une utilisation régulière. Génération de PDF quotidienne augmentée.',
                'limitGeneration' => 10,
                'role' => 'ROLE_BASIC',
                'price' => '9.99',
                'active' => true,
            ],
            [
                'name' => 'PREMIUM',
                'description' => 'Plan premium pour une utilisation intensive. Génération illimitée et fonctionnalités avancées.',
                'limitGeneration' => -1,
                'role' => 'ROLE_PREMIUM',
                'price' => '29.99',
                'specialPrice' => '19.99',
                'specialPriceFrom' => new \DateTime('2026-01-01'),
                'specialPriceTo' => new \DateTime('2026-12-31'),
                'active' => true,
            ],
        ];

        foreach ($plans as $planData) {
            $plan = new Plan();
            $plan->setName($planData['name']);
            $plan->setDescription($planData['description']);
            $plan->setLimitGeneration($planData['limitGeneration']);
            $plan->setRole($planData['role']);
            $plan->setPrice($planData['price']);
            $plan->setActive($planData['active']);

            if (isset($planData['specialPrice'])) {
                $plan->setSpecialPrice($planData['specialPrice']);
            }
            if (isset($planData['specialPriceFrom'])) {
                $plan->setSpecialPriceFrom($planData['specialPriceFrom']);
            }
            if (isset($planData['specialPriceTo'])) {
                $plan->setSpecialPriceTo($planData['specialPriceTo']);
            }

            $manager->persist($plan);
        }

        $manager->flush();
    }
}
