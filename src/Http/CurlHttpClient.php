<?php

declare(strict_types=1);

namespace SenzaIdee\Http;

use Monolog\Handler\Curl\Util;

final class CurlHttpClient implements HttpClientInterface
{
    #[\Override]
    public function send(string $url, array $headers, string $data, array $options = []): string
    {
        $handle = curl_init();

        if (false === $handle) {
            throw new \RuntimeException('Unable to initialize curl handle');
        }

        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

        /**
         * @psalm-suppress MixedAssignment, MixedArgumentTypeCoercion
         */
        foreach ($options as $option => $value) {
            curl_setopt($handle, $option, $value);
        }

        /**
         * @psalm-suppress InternalClass, InternalMethod
         */
        return (string) Util::execute($handle);
    }
}
