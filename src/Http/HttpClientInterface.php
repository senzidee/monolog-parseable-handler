<?php

declare(strict_types=1);

namespace SenzaIdee\Http;

interface HttpClientInterface
{
    /**
     * Send an HTTP request with the given options.
     *
     * @param string $url The URL to send the request to
     * @param array $headers HTTP headers to include
     * @param string $data The request body data
     * @param array $options Additional options for the request
     * @return string The response body
     *
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function send(string $url, array $headers, string $data, array $options = []): string;
}
