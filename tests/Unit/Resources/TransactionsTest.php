<?php

declare(strict_types=1);

namespace QrCommunication\VivaLocalTerminal\Tests\Unit\Resources;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QrCommunication\VivaLocalTerminal\Config;
use QrCommunication\VivaLocalTerminal\Exceptions\ApiException;
use QrCommunication\VivaLocalTerminal\Resources\Transactions;
use QrCommunication\VivaLocalTerminal\Tests\Fakes\GuzzleMockFactory;

final class TransactionsTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        $this->config = new Config(terminalBaseUrl: 'https://192.168.1.50:8080');
    }

    #[Test]
    public function it_initiates_a_sale(): void
    {
        [$http, $mockHandler] = GuzzleMockFactory::create($this->config);
        $resource = new Transactions($http);

        $mockHandler->append(GuzzleMockFactory::jsonResponse(200, [
            'state' => 'PROCESSING',
            'sessionType' => 'SALE',
            'sessionId' => 'aee2afbd-23aa-4e93-8bdc-df9666abefb8',
        ]));

        $result = $resource->sale(
            sessionId: '4bdebe62-c211-4ca0-a994-b2fbea2061c5',
            amount: 1170,
            currencyCode: 978,
            merchantReference: 'order-123',
            preauth: false,
        );

        $this->assertSame('PROCESSING', $result['state']);
        $this->assertSame('SALE', $result['sessionType']);

        $request = $mockHandler->getLastRequest();
        $this->assertInstanceOf(Request::class, $request);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://192.168.1.50:8080/pos/v1/sale', (string) $request->getUri());

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('4bdebe62-c211-4ca0-a994-b2fbea2061c5', $body['sessionId']);
        $this->assertSame(1170, $body['amount']);
        $this->assertSame(978, $body['currencyCode']);
        $this->assertSame('order-123', $body['merchantReference']);
        $this->assertFalse($body['preauth']);
        // nulls are stripped
        $this->assertArrayNotHasKey('tipAmount', $body);
    }

    #[Test]
    public function it_includes_isv_details_on_a_sale(): void
    {
        [$http, $mockHandler] = GuzzleMockFactory::create($this->config);
        $resource = new Transactions($http);

        $mockHandler->append(GuzzleMockFactory::jsonResponse(200, ['state' => 'PROCESSING']));

        $resource->sale(
            sessionId: 'sess-1',
            amount: 1170,
            isvDetails: [
                'clientId' => 'xxxxx.apps.vivapayments.com',
                'clientSecret' => 'secret',
                'amount' => 122,
            ],
        );

        $body = json_decode((string) $mockHandler->getLastRequest()->getBody(), true);
        $this->assertSame(122, $body['isvDetails']['amount']);
        $this->assertSame('xxxxx.apps.vivapayments.com', $body['isvDetails']['clientId']);
    }

    #[Test]
    public function it_captures_a_preauth(): void
    {
        [$http, $mockHandler] = GuzzleMockFactory::create($this->config);
        $resource = new Transactions($http);

        $mockHandler->append(GuzzleMockFactory::jsonResponse(200, [
            'state' => 'PROCESSING',
            'sessionType' => 'SALE',
            'sessionId' => 'sess-cap',
        ]));

        $result = $resource->capturePreauth(
            sessionId: 'sess-cap',
            amount: 1170,
            transactionId: '03cfb0b9-2e4b-4610-b4d7-29c28a50a749',
            currencyCode: 978,
        );

        $this->assertSame('PROCESSING', $result['state']);

        $request = $mockHandler->getLastRequest();
        $this->assertSame('https://192.168.1.50:8080/pos/v1/preauth-completion', (string) $request->getUri());

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('03cfb0b9-2e4b-4610-b4d7-29c28a50a749', $body['transactionId']);
        $this->assertSame(1170, $body['amount']);
    }

    #[Test]
    public function it_refunds_a_transaction(): void
    {
        [$http, $mockHandler] = GuzzleMockFactory::create($this->config);
        $resource = new Transactions($http);

        $mockHandler->append(GuzzleMockFactory::jsonResponse(200, [
            'state' => 'PROCESSING',
            'sessionType' => 'CANCEL',
            'sessionId' => 'sess-ref',
        ]));

        $result = $resource->refund(
            sessionId: 'sess-ref',
            amount: 1170,
            transactionId: '03cfb0b9-2e4b-4610-b4d7-29c28a50a749',
            currencyCode: 826,
        );

        $this->assertSame('CANCEL', $result['sessionType']);

        $request = $mockHandler->getLastRequest();
        $this->assertSame('https://192.168.1.50:8080/pos/v1/refund', (string) $request->getUri());

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('03cfb0b9-2e4b-4610-b4d7-29c28a50a749', $body['transactionId']);
        $this->assertSame(826, $body['currencyCode']);
    }

    #[Test]
    public function it_issues_an_unreferenced_refund(): void
    {
        [$http, $mockHandler] = GuzzleMockFactory::create($this->config);
        $resource = new Transactions($http);

        $mockHandler->append(GuzzleMockFactory::jsonResponse(200, [
            'state' => 'PROCESSING',
            'sessionType' => 'UNREFERENCED_REFUND',
            'sessionId' => 'sess-uref',
        ]));

        $result = $resource->unreferencedRefund(
            sessionId: 'sess-uref',
            amount: 1170,
            merchantReference: 'ref',
        );

        $this->assertSame('UNREFERENCED_REFUND', $result['sessionType']);

        $request = $mockHandler->getLastRequest();
        $this->assertSame('https://192.168.1.50:8080/pos/v1/unreferenced-refund', (string) $request->getUri());

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame(1170, $body['amount']);
        $this->assertArrayNotHasKey('transactionId', $body);
    }

    #[Test]
    public function it_aborts_a_session(): void
    {
        [$http, $mockHandler] = GuzzleMockFactory::create($this->config);
        $resource = new Transactions($http);

        $mockHandler->append(GuzzleMockFactory::jsonResponse(200, [
            'state' => 'PROCESSING',
            'sessionType' => 'ABORT',
            'sessionId' => '52289d9f-3f0f-42c1-9d89-72978b25d08f',
        ]));

        $result = $resource->abort('4bdebe62-c211-4ca0-a994-b2fbea2061c5');

        $this->assertSame('ABORT', $result['sessionType']);

        $request = $mockHandler->getLastRequest();
        $this->assertSame('https://192.168.1.50:8080/pos/v1/abort', (string) $request->getUri());

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('4bdebe62-c211-4ca0-a994-b2fbea2061c5', $body['sessionId']);
    }

    #[Test]
    public function it_creates_an_aade_fim_control_action(): void
    {
        [$http, $mockHandler] = GuzzleMockFactory::create($this->config);
        $resource = new Transactions($http);

        $mockHandler->append(GuzzleMockFactory::jsonResponse(200, [
            'status' => 'success',
            'message' => 'Fim control success',
        ]));

        $result = $resource->aadeFimControl('ECR0210U/RCFB77000041/CMAC_K:1ED9F7AE0B2509281BBC2DE38EF2A12B:CC5FFF');

        $this->assertSame('success', $result['status']);

        $request = $mockHandler->getLastRequest();
        $this->assertSame('https://192.168.1.50:8080/pos/v1/aade-fim-control', (string) $request->getUri());

        $body = json_decode((string) $request->getBody(), true);
        $this->assertStringStartsWith('ECR0210U/', $body['token']);
    }

    #[Test]
    public function it_retrieves_transactions_with_filters(): void
    {
        [$http, $mockHandler] = GuzzleMockFactory::create($this->config);
        $resource = new Transactions($http);

        $mockHandler->append(GuzzleMockFactory::jsonResponse(200, [
            'isSuccess' => true,
            'transactions' => [['transactionId' => 'txn-1', 'transactionTypeId' => 5]],
        ]));

        $result = $resource->retrieve(
            cardNumber: '449332XXXXXX2179',
            orderCode: '5253220188003510',
            transactionTypes: [7, 5],
            transactionStatus: 'E',
            startDate: '2025-08-14T10:00:00.000Z',
            endDate: '2025-09-10T12:00:00.000Z',
            isAadeAutonomouslyOnly: true,
        );

        $this->assertTrue($result['isSuccess']);
        $this->assertSame('txn-1', $result['transactions'][0]['transactionId']);

        $request = $mockHandler->getLastRequest();
        $this->assertSame('GET', $request->getMethod());

        $uri = (string) $request->getUri();
        $this->assertStringContainsString('/pos/v1/transactions', $uri);
        $this->assertStringContainsString('cardNumber=449332XXXXXX2179', $uri);
        $this->assertStringContainsString('orderCode=5253220188003510', $uri);
        $this->assertStringContainsString('transactionStatus=E', $uri);
        $this->assertStringContainsString('isAadeAutonomouslyOnly=true', $uri);
        // array transactionTypes serialized as repeated param
        $this->assertStringContainsString('transactionTypes%5B0%5D=7', $uri);
        $this->assertStringContainsString('transactionTypes%5B1%5D=5', $uri);
    }

    #[Test]
    public function it_throws_api_exception_with_plain_string_body_on_400(): void
    {
        [$http, $mockHandler] = GuzzleMockFactory::create($this->config);
        $resource = new Transactions($http);

        $mockHandler->append(GuzzleMockFactory::textResponse(
            400,
            'ZeroconfException error message Amount cannot be null',
        ));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('ZeroconfException error message Amount cannot be null');

        $resource->sale(sessionId: 'sess-x', amount: 0);
    }
}
