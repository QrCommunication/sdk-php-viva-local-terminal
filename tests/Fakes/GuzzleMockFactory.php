<?php

declare(strict_types=1);

namespace QrCommunication\VivaLocalTerminal\Tests\Fakes;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use QrCommunication\VivaLocalTerminal\Config;
use QrCommunication\VivaLocalTerminal\HttpClient;

/**
 * Factory that builds a real HttpClient with a Guzzle MockHandler injected via
 * the optional $guzzle parameter of HttpClient::__construct().
 *
 * The Local Terminal API requires NO authentication, so — unlike cloud SDKs —
 * there is no token response to pre-queue: just append the endpoint response.
 *
 * Usage:
 *
 *     [$http, $mockHandler] = GuzzleMockFactory::create($config);
 *     $mockHandler->append(GuzzleMockFactory::jsonResponse(200, ['state' => 'PROCESSING']));
 *     $result = $http->post('/pos/v1/sale', [...]);
 */
final class GuzzleMockFactory
{
    /**
     * @return array{0: HttpClient, 1: MockHandler}
     */
    public static function create(Config $config): array
    {
        $mockHandler = new MockHandler;
        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client([
            'handler' => $handlerStack,
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => false,
            'headers' => ['Accept' => 'application/json'],
        ]);

        $http = new HttpClient($config, $guzzleClient);

        return [$http, $mockHandler];
    }

    /**
     * Build a JSON response for the mock handler.
     *
     * @param  array<string, mixed>  $body
     */
    public static function jsonResponse(int $status, array $body): Response
    {
        return new Response(
            $status,
            ['Content-Type' => 'application/json'],
            json_encode($body, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * Build a plain-text response — the Local Terminal API returns a bare string
     * body on 400 (e.g. "ZeroconfException error message Amount cannot be null").
     */
    public static function textResponse(int $status, string $body): Response
    {
        return new Response($status, ['Content-Type' => 'text/plain'], $body);
    }
}
