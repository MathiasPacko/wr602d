<?php

namespace App\Tests\Service;

use App\Entity\Plan;
use App\Entity\User;
use App\Service\FeatureAccessChecker;
use PHPUnit\Framework\TestCase;

class FeatureAccessCheckerTest extends TestCase
{
    private FeatureAccessChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new FeatureAccessChecker();
    }

    private function createUserWithPlan(string $planName, int $limit = 10): User
    {
        $plan = new Plan();
        $plan->setName($planName);
        $plan->setLimitGeneration($limit);
        $plan->setPrice('0');
        $plan->setRole('ROLE_USER');

        $user = new User();
        $user->setPlan($plan);

        return $user;
    }

    public function testFreeUserCanAccessUrlFeature(): void
    {
        $user = $this->createUserWithPlan('FREE', 2);
        $this->assertTrue($this->checker->canAccessFeature($user, 'url'));
    }

    public function testFreeUserCanAccessHtmlFeature(): void
    {
        $user = $this->createUserWithPlan('FREE', 2);
        $this->assertTrue($this->checker->canAccessFeature($user, 'html'));
    }

    public function testFreeUserCannotAccessMergeFeature(): void
    {
        $user = $this->createUserWithPlan('FREE', 2);
        $this->assertFalse($this->checker->canAccessFeature($user, 'merge'));
    }

    public function testBasicUserCanAccessMarkdownFeature(): void
    {
        $user = $this->createUserWithPlan('BASIC', 10);
        $this->assertTrue($this->checker->canAccessFeature($user, 'markdown'));
    }

    public function testBasicUserCannotAccessScreenshotFeature(): void
    {
        $user = $this->createUserWithPlan('BASIC', 10);
        $this->assertFalse($this->checker->canAccessFeature($user, 'screenshot'));
    }

    public function testPremiumUserCanAccessAllFeatures(): void
    {
        $user = $this->createUserWithPlan('PREMIUM', -1);

        $this->assertTrue($this->checker->canAccessFeature($user, 'url'));
        $this->assertTrue($this->checker->canAccessFeature($user, 'html'));
        $this->assertTrue($this->checker->canAccessFeature($user, 'markdown'));
        $this->assertTrue($this->checker->canAccessFeature($user, 'office'));
        $this->assertTrue($this->checker->canAccessFeature($user, 'merge'));
        $this->assertTrue($this->checker->canAccessFeature($user, 'screenshot'));
        $this->assertTrue($this->checker->canAccessFeature($user, 'wysiwyg'));
    }

    public function testGetMinimumPlanForFeature(): void
    {
        $this->assertEquals('FREE', $this->checker->getMinimumPlanForFeature('url'));
        $this->assertEquals('FREE', $this->checker->getMinimumPlanForFeature('html'));
        $this->assertEquals('BASIC', $this->checker->getMinimumPlanForFeature('markdown'));
        $this->assertEquals('BASIC', $this->checker->getMinimumPlanForFeature('office'));
        $this->assertEquals('PREMIUM', $this->checker->getMinimumPlanForFeature('merge'));
        $this->assertEquals('PREMIUM', $this->checker->getMinimumPlanForFeature('screenshot'));
        $this->assertEquals('PREMIUM', $this->checker->getMinimumPlanForFeature('wysiwyg'));
    }

    public function testNullUserGetsFreeFeatures(): void
    {
        $this->assertTrue($this->checker->canAccessFeature(null, 'url'));
        $this->assertTrue($this->checker->canAccessFeature(null, 'html'));
        $this->assertFalse($this->checker->canAccessFeature(null, 'merge'));
    }
}
