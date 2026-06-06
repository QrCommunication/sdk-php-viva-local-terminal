<?php

declare(strict_types=1);

namespace QrCommunication\VivaLocalTerminal\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QrCommunication\VivaLocalTerminal\Resources\Device;
use QrCommunication\VivaLocalTerminal\Resources\Sessions;
use QrCommunication\VivaLocalTerminal\Resources\Transactions;
use QrCommunication\VivaLocalTerminal\VivaLocalTerminalClient;

final class VivaLocalTerminalClientTest extends TestCase
{
    #[Test]
    public function it_exposes_the_three_resources(): void
    {
        $client = new VivaLocalTerminalClient(terminalBaseUrl: 'https://192.168.1.50:8080');

        $this->assertInstanceOf(Transactions::class, $client->transactions);
        $this->assertInstanceOf(Sessions::class, $client->sessions);
        $this->assertInstanceOf(Device::class, $client->device);
    }

    #[Test]
    public function it_exposes_the_config(): void
    {
        $client = new VivaLocalTerminalClient(
            terminalBaseUrl: 'https://192.168.1.50:8080',
            useIsvEndpoints: true,
        );

        $this->assertSame('https://192.168.1.50:8080', $client->getConfig()->baseUrl);
        $this->assertTrue($client->getConfig()->useIsvEndpoints);
    }
}
