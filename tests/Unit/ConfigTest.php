<?php

declare(strict_types=1);

namespace QrCommunication\VivaLocalTerminal\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QrCommunication\VivaLocalTerminal\Config;

final class ConfigTest extends TestCase
{
    #[Test]
    public function it_strips_a_trailing_slash_from_the_base_url(): void
    {
        $config = new Config(terminalBaseUrl: 'https://192.168.1.50:8080/');

        $this->assertSame('https://192.168.1.50:8080', $config->baseUrl);
    }

    #[Test]
    public function it_builds_merchant_urls_without_trailing_slash(): void
    {
        $config = new Config(terminalBaseUrl: 'https://192.168.1.50:8080');

        $this->assertSame('https://192.168.1.50:8080/pos/v1/sale', $config->url('/pos/v1/sale'));
    }

    #[Test]
    public function it_builds_isv_urls_with_trailing_slash(): void
    {
        $config = new Config(terminalBaseUrl: 'https://192.168.1.50:8080', useIsvEndpoints: true);

        $this->assertSame('https://192.168.1.50:8080/pos/v1/sale/', $config->url('/pos/v1/sale'));
    }

    #[Test]
    public function it_rejects_an_empty_base_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Config(terminalBaseUrl: '   ');
    }

    #[Test]
    public function it_rejects_a_base_url_without_scheme(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Config(terminalBaseUrl: '192.168.1.50:8080');
    }

    #[Test]
    public function it_defaults_to_verifying_tls(): void
    {
        $config = new Config(terminalBaseUrl: 'https://192.168.1.50:8080');

        $this->assertTrue($config->verifyTls);
        $this->assertFalse($config->useIsvEndpoints);
    }
}
