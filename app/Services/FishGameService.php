<?php

namespace App\Services;

use App\Core\Logger;

/**
 * The only class that talks to the external Fish Game API (https://marcconrad.com/uob/fish/).
 *
 * Documented API (doc.php):
 *   GET api.php?out=json|csv&base64=yes|no      (defaults: out=json, base64=no)
 *   JSON → { "question": <image URL | base64 PNG>, "fish": <int>, "chest": <int> }
 *   No authentication; responds with Access-Control-Allow-Origin: *.
 *
 * The response contains the solution and the image URL encodes it (…_004x008.png), so the
 * browser must never see the raw response. We request base64=yes and hand the browser a
 * data: URI only; the solution stays on the server.
 */
class FishGameService
{
    private string $endpoint;
    private int $timeout;
    private int $retries;

    public function __construct()
    {
        $config = config('app.fish_api');
        $this->endpoint = $config['url'];
        $this->timeout = (int) $config['timeout'];
        $this->retries = (int) $config['retries'];
    }

    /**
     * @return array{image: string, fish: int, chest: int}
     * @throws FishApiException
     */
    public function fetchPuzzle(): array
    {
        $url = $this->endpoint . '?' . http_build_query(['out' => 'json', 'base64' => 'yes']);
        $lastError = 'unknown error';

        for ($attempt = 0; $attempt <= $this->retries; $attempt++) {
            try {
                return $this->parse($this->request($url));
            } catch (FishApiException $e) {
                $lastError = $e->getMessage();
            }
        }

        Logger::error('Fish API unavailable', ['url' => $this->endpoint, 'error' => $lastError]);

        throw new FishApiException('Unable to connect to the game server.');
    }

    private function request(string $url): string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_USERAGENT      => 'DeepCatch/1.0 (+FishGameService)',
            ]);
            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($body === false || $status !== 200) {
                throw new FishApiException("HTTP $status $error");
            }

            return (string) $body;
        }

        $context = stream_context_create(['http' => ['timeout' => $this->timeout, 'ignore_errors' => false]]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            throw new FishApiException('Request failed (file_get_contents)');
        }

        return $body;
    }

    /**
     * @return array{image: string, fish: int, chest: int}
     */
    private function parse(string $body): array
    {
        // The API answers with Content-Type text/html, so decode regardless of headers.
        $data = json_decode(trim($body), true);
        if (!is_array($data) || !isset($data['question'], $data['fish'])) {
            throw new FishApiException('Unexpected response format');
        }

        $fish = filter_var($data['fish'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 99]]);
        $chest = filter_var($data['chest'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 99]]);
        if ($fish === false || $chest === false) {
            throw new FishApiException('Invalid solution values');
        }

        $base64 = preg_replace('/\s+/', '', (string) $data['question']);
        $binary = base64_decode($base64, true);
        if ($binary === false || !str_starts_with($binary, "\x89PNG")) {
            throw new FishApiException('Question is not a base64 PNG image');
        }

        return [
            'image' => 'data:image/png;base64,' . $base64,
            'fish'  => $fish,
            'chest' => $chest,
        ];
    }
}
