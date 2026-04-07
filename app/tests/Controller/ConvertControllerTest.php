<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConvertControllerTest extends WebTestCase
{
    public function testConvertUrlRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/convert/url');

        // Should redirect to login
        $this->assertResponseRedirects('/login');
    }

    public function testConvertHtmlRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/convert/html');

        $this->assertResponseRedirects('/login');
    }

    public function testConvertMarkdownRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/convert/markdown');

        $this->assertResponseRedirects('/login');
    }

    public function testConvertOfficeRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/convert/office');

        $this->assertResponseRedirects('/login');
    }

    public function testConvertMergeRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/convert/merge');

        $this->assertResponseRedirects('/login');
    }

    public function testConvertScreenshotRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/convert/screenshot');

        $this->assertResponseRedirects('/login');
    }

    public function testConvertWysiwygRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/convert/wysiwyg');

        $this->assertResponseRedirects('/login');
    }
}
