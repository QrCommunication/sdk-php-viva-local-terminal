<?php

declare(strict_types=1);

namespace QrCommunication\VivaLocalTerminal\Tests\Unit\Resources;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QrCommunication\VivaLocalTerminal\Config;
use QrCommunication\VivaLocalTerminal\Resources\Sessions;
use QrCommunication\VivaLocalTerminal\Tests\Fakes\GuzzleMockFactory;

final class SessionsTest extends TestCase
{
    #[Test]
    public function it_retrieves_a_session_by_id(): void
    {
        $config = new Config(terminalBaseUrl: 'https://192.168.1.50:8080');
        [$http, $mockHandler] = GuzzleMockFactory::create($config);
        $resource = new Sessions($http);

        $mockHandler->append(GuzzleMockFactory::jsonResponse(200, [
            'state' => 'SUCCESS',
            'sessionType' => 'SALE',
            'sessionId' => '4bdebe62-c211-4ca0-a994-b2fbea2061c5',
            'payloadData' => ['saleResponse' => ['transactionId' => 'txn-1', 'isSuccess' => true]],
        ]));

        $result = $resource->get('4bdebe62-c211-4ca0-a994-b2fbea2061c5');

        $this->assertSame('SUCCESS', $result['state']);
        $this->assertSame('txn-1', $result['payloadData']['saleResponse']['transactionId']);

        $request = $mockHandler->getLastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame(
            'https://192.168.1.50:8080/pos/v1/sessions/4bdebe62-c211-4ca0-a994-b2fbea2061c5',
            (string) $request->getUri(),
        );
    }

    #[Test]
    public function it_uses_the_isv_trailing_slash_path_when_enabled(): void
    {
        $config = new Config(terminalBaseUrl: 'https://192.168.1.50:8080', useIsvEndpoints: true);
        [$http, $mockHandler] = GuzzleMockFactory::create($config);
        $resource = new Sessions($http);

        $mockHandler->append(GuzzleMockFactory::jsonResponse(200, ['state' => 'SUCCESS']));

        $resource->get('sess-isv');

        $this->assertSame(
            'https://192.168.1.50:8080/pos/v1/sessions/sess-isv/',
            (string) $mockHandler->getLastRequest()->getUri(),
        );
    }
}
