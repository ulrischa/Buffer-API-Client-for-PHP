<?php

declare(strict_types=1);

namespace BufferApi\Http;

interface HttpTransportInterface
{
    /**
     * @param array<string, string> $headers
     */
    public function post(string $url, array $headers, string $body): HttpResponse;
}
