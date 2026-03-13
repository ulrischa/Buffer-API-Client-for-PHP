<?php

declare(strict_types=1);

namespace BufferApi\Http;

use BufferApi\Exception\HttpTransportException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Psr18HttpTransport implements HttpTransportInterface
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory
    ) {
    }

    public function post(string $url, array $headers, string $body): HttpResponse
    {
        $request = $this->requestFactory->createRequest('POST', $url);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $request = $request->withBody($this->streamFactory->createStream($body));

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            throw new HttpTransportException('PSR-18 request failed: ' . $exception->getMessage(), 0, $exception);
        }

        $normalizedHeaders = [];
        foreach ($response->getHeaders() as $name => $values) {
            $normalizedHeaders[strtolower($name)] = implode(', ', $values);
        }

        return new HttpResponse(
            $response->getStatusCode(),
            (string) $response->getBody(),
            $normalizedHeaders
        );
    }
}
