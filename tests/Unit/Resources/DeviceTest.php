<?php

declare(strict_types=1);

namespace QrCommunication\VivaLocalTerminal\Tests\Unit\Resources;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QrCommunication\VivaLocalTerminal\Config;
use QrCommunication\VivaLocalTerminal\Resources\Device;
use QrCommunication\VivaLocalTerminal\Tests\Fakes\GuzzleMockFactory;

final class DeviceTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        $this->config = new Config(terminalBaseUrl: 'https://192.168.1.50:8080');
    }

    #[Test]
    public function it_controls_the_screen_via_awake_lock(): void
    {
        [$http, $mockHandler] = GuzzleMockFactory::create($this->config);
        $resource = new Device($http);

        $mockHandler->append(GuzzleMockFactory::jsonResponse(200, ['isSuccess' => true]));

        $result = $resource->screenControl(wakeUpLock: true);

        $this->assertTrue($result['isSuccess']);

        $request = $mockHandler->getLastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://192.168.1.50:8080/pos/v1/awake-lock', (string) $request->getUri());

        $body = json_decode((string) $request->getBody(), true);
        $this->assertTrue($body['wakeUpLock']);
    }

    #[Test]
    public function it_sets_the_brightness(): void
    {
        [$http, $mockHandler] = GuzzleMockFactory::create($this->config);
        $resource = new Device($http);

        $mockHandler->append(GuzzleMockFactory::jsonResponse(200, ['isSuccess' => true]));

        $result = $resource->brightness(0.25);

        $this->assertTrue($result['isSuccess']);

        $request = $mockHandler->getLastRequest();
        $this->assertSame('https://192.168.1.50:8080/pos/v1/brightness', (string) $request->getUri());

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame(0.25, $body['brightness']);
    }

    #[Test]
    public function it_rejects_brightness_of_zero(): void
    {
        [$http] = GuzzleMockFactory::create($this->config);
        $resource = new Device($http);

        $this->expectException(\InvalidArgumentException::class);

        $resource->brightness(0.0);
    }

    #[Test]
    public function it_rejects_brightness_above_one(): void
    {
        [$http] = GuzzleMockFactory::create($this->config);
        $resource = new Device($http);

        $this->expectException(\InvalidArgumentException::class);

        $resource->brightness(1.5);
    }
}
