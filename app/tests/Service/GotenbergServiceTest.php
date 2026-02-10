<?php

namespace App\Tests\Service;

use App\Service\GotenbergService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GotenbergServiceTest extends TestCase
{
    private const GOTENBERG_URL = 'http://gotenberg:3000';

    public function testConvertHtmlToPdfSuccess(): void
    {
        // Simuler une réponse PDF
        $fakePdfContent = '%PDF-1.4 fake pdf content';

        $mockResponse = new MockResponse($fakePdfContent, [
            'http_code' => 200,
            'response_headers' => ['Content-Type' => 'application/pdf'],
        ]);

        $httpClient = new MockHttpClient($mockResponse);
        $service = new GotenbergService($httpClient, self::GOTENBERG_URL);

        $htmlContent = '<html><body><h1>Test</h1></body></html>';
        $result = $service->convertHtmlToPdf($htmlContent);

        $this->assertEquals($fakePdfContent, $result);
    }

    public function testConvertHtmlToPdfWithOptions(): void
    {
        $fakePdfContent = '%PDF-1.4 fake pdf content';

        $mockResponse = new MockResponse($fakePdfContent, [
            'http_code' => 200,
        ]);

        $httpClient = new MockHttpClient($mockResponse);
        $service = new GotenbergService($httpClient, self::GOTENBERG_URL);

        $htmlContent = '<html><body><h1>Test</h1></body></html>';
        $options = [
            'marginTop' => '2',
            'marginBottom' => '2',
        ];

        $result = $service->convertHtmlToPdf($htmlContent, $options);

        $this->assertEquals($fakePdfContent, $result);
    }

    public function testConvertHtmlToPdfError(): void
    {
        $mockResponse = new MockResponse('Internal Server Error', [
            'http_code' => 500,
        ]);

        $httpClient = new MockHttpClient($mockResponse);
        $service = new GotenbergService($httpClient, self::GOTENBERG_URL);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Erreur Gotenberg \(500\)/');

        $service->convertHtmlToPdf('<html></html>');
    }

    public function testConvertUrlToPdfSuccess(): void
    {
        $fakePdfContent = '%PDF-1.4 fake pdf content';

        $mockResponse = new MockResponse($fakePdfContent, [
            'http_code' => 200,
        ]);

        $httpClient = new MockHttpClient($mockResponse);
        $service = new GotenbergService($httpClient, self::GOTENBERG_URL);

        $result = $service->convertUrlToPdf('https://example.com');

        $this->assertEquals($fakePdfContent, $result);
    }

    public function testHealthCheckSuccess(): void
    {
        $mockResponse = new MockResponse('{"status":"up"}', [
            'http_code' => 200,
        ]);

        $httpClient = new MockHttpClient($mockResponse);
        $service = new GotenbergService($httpClient, self::GOTENBERG_URL);

        $result = $service->healthCheck();

        $this->assertTrue($result);
    }

    public function testHealthCheckFailure(): void
    {
        $mockResponse = new MockResponse('', [
            'http_code' => 503,
        ]);

        $httpClient = new MockHttpClient($mockResponse);
        $service = new GotenbergService($httpClient, self::GOTENBERG_URL);

        $result = $service->healthCheck();

        $this->assertFalse($result);
    }

    public function testMergePdfsSuccess(): void
    {
        $fakeMergedPdf = '%PDF-1.4 merged pdf content';

        $mockResponse = new MockResponse($fakeMergedPdf, [
            'http_code' => 200,
        ]);

        $httpClient = new MockHttpClient($mockResponse);
        $service = new GotenbergService($httpClient, self::GOTENBERG_URL);

        $pdfContents = [
            '%PDF-1.4 first pdf',
            '%PDF-1.4 second pdf',
        ];

        $result = $service->mergePdfs($pdfContents);

        $this->assertEquals($fakeMergedPdf, $result);
    }

    public function testRequestIsSentToCorrectEndpoint(): void
    {
        $requestInfo = [];

        $mockResponse = new MockResponse('%PDF-1.4', [
            'http_code' => 200,
        ]);

        $httpClient = new MockHttpClient(function ($method, $url) use (&$requestInfo, $mockResponse) {
            $requestInfo['method'] = $method;
            $requestInfo['url'] = $url;
            return $mockResponse;
        });

        $service = new GotenbergService($httpClient, self::GOTENBERG_URL);
        $service->convertHtmlToPdf('<html></html>');

        $this->assertEquals('POST', $requestInfo['method']);
        $this->assertEquals(self::GOTENBERG_URL . '/forms/chromium/convert/html', $requestInfo['url']);
    }
}
