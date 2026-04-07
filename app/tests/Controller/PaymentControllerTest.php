<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentControllerTest extends WebTestCase
{
    public function testCheckoutRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/payment/checkout/1');

        $this->assertResponseRedirects('/login');
    }

    public function testSuccessPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/payment/success');

        $this->assertResponseRedirects('/login');
    }

    public function testCancelPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/payment/cancel');

        $this->assertResponseIsSuccessful();
    }

    public function testWebhookAcceptsPost(): void
    {
        $client = static::createClient();
        $client->request('POST', '/payment/webhook', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['type' => 'test']));

        // Webhook returns OK even with invalid event type
        $this->assertResponseIsSuccessful();
    }
}
