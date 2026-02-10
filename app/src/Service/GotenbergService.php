<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

class GotenbergService
{
    public function __construct(
        private HttpClientInterface $client,
        private string $gotenbergUrl
    ) {
    }

    /**
     * Convertit du contenu HTML en PDF
     */
    public function convertHtmlToPdf(string $htmlContent, array $options = []): string
    {
        $formFields = array_merge($options, [
            'files' => new DataPart($htmlContent, 'index.html', 'text/html'),
        ]);

        return $this->sendRequest('/forms/chromium/convert/html', $formFields);
    }

    /**
     * Convertit une URL en PDF
     */
    public function convertUrlToPdf(string $url, array $options = []): string
    {
        $formFields = array_merge($options, [
            'url' => $url,
        ]);

        return $this->sendRequest('/forms/chromium/convert/url', $formFields);
    }

    /**
     * Convertit un fichier Office (docx, xlsx, pptx, odt, etc.) en PDF
     */
    public function convertOfficeToPdf(UploadedFile $file, array $options = []): string
    {
        $formFields = array_merge($options, [
            'files' => DataPart::fromPath($file->getPathname(), $file->getClientOriginalName()),
        ]);

        return $this->sendRequest('/forms/libreoffice/convert', $formFields);
    }

    /**
     * Convertit un fichier uploadé (image, document) en PDF via Chromium
     */
    public function convertFileToPdf(UploadedFile $file, array $options = []): string
    {
        $formFields = array_merge($options, [
            'files' => DataPart::fromPath($file->getPathname(), $file->getClientOriginalName()),
        ]);

        return $this->sendRequest('/forms/chromium/convert/html', $formFields);
    }

    /**
     * Fusionne plusieurs fichiers PDF en un seul
     *
     * @param array $pdfContents Tableau de contenus PDF binaires
     */
    public function mergePdfs(array $pdfContents): string
    {
        $formFields = [];
        foreach ($pdfContents as $index => $content) {
            $formFields['files'][] = new DataPart($content, sprintf('%d.pdf', $index + 1), 'application/pdf');
        }

        return $this->sendRequest('/forms/pdfengines/merge', $formFields);
    }

    /**
     * Vérifie si le service Gotenberg est accessible
     */
    public function healthCheck(): bool
    {
        try {
            $response = $this->client->request('GET', $this->gotenbergUrl . '/health');
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Envoie une requête multipart à Gotenberg
     */
    private function sendRequest(string $endpoint, array $formFields): string
    {
        $formData = new FormDataPart($formFields);

        $response = $this->client->request('POST', $this->gotenbergUrl . $endpoint, [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable(),
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(
                sprintf('Erreur Gotenberg (%d): %s', $response->getStatusCode(), $response->getContent(false))
            );
        }

        return $response->getContent();
    }
}
