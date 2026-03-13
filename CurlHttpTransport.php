<?php

declare(strict_types=1);

namespace BufferApi\Http;

use BufferApi\Exception\HttpTransportException;

final class CurlHttpTransport implements HttpTransportInterface
{
    public function __construct(private readonly int $timeoutSeconds = 30)
    {
        if ($this->timeoutSeconds < 1) {
            throw new HttpTransportException('Timeout must be >= 1 second.');
        }
    }

    public function post(string $url, array $headers, string $body): HttpResponse
    {
        $curl = curl_init($url);

        if ($curl === false) {
            throw new HttpTransportException('Could not initialize cURL.');
        }

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        $responseHeaders = [];

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HEADERFUNCTION => static function ($curlHandle, string $headerLine) use (&$responseHeaders): int {
                $length = strlen($headerLine);
                $headerLine = trim($headerLine);

                if ($headerLine === '' || !str_contains($headerLine, ':')) {
                    return $length;
                }

                [$name, $value] = explode(':', $headerLine, 2);
                $responseHeaders[strtolower(trim($name))] = trim($value);

                return $length;
            },
        ]);

        $responseBody = curl_exec($curl);

        if ($responseBody === false) {
            $message = curl_error($curl);
            curl_close($curl);
            throw new HttpTransportException('cURL request failed: ' . $message);
        }

        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return new HttpResponse($statusCode, $responseBody, $responseHeaders);
    }
}
