<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Balikobot::class)]
final class BalikobotResponseTest extends TestCase {

    public function testApiResponseIsReturnedAsObject(): void {
        $http = new FakeHttpClient('{"status":200,"packages":[{"package_id":42,"carrier_id":"DR1"}]}');
        $balikobot = new Balikobot('user', 'secret-key', 'https://api.test', $http);

        $response = $balikobot->add('cp', [['eid' => 'A1']]);

        self::assertInstanceOf(stdClass::class, $response);
        self::assertSame(200, $response->status);
        self::assertSame(42, $response->packages[0]->package_id);
        self::assertSame('', $balikobot->getLastCurlError());
    }

    public function testErrorPageInsteadOfJsonReturnsHttpStatus(): void {
        $http = new FakeHttpClient('<html><body>502 Bad Gateway</body></html>', 502);
        $balikobot = new Balikobot('user', 'secret-key', 'https://api.test', $http);

        $response = $balikobot->overview('cp');

        self::assertInstanceOf(stdClass::class, $response);
        self::assertSame(502, $response->status);
    }

    public function testEmptyResponseReturnsHttpStatus(): void {
        $http = new FakeHttpClient('', 500);
        $balikobot = new Balikobot('user', 'secret-key', 'https://api.test', $http);

        self::assertSame(500, $balikobot->overview('cp')->status);
    }

    public function testNullBodyReturnsHttpStatus(): void {
        $http = new FakeHttpClient('null', 200);
        $balikobot = new Balikobot('user', 'secret-key', 'https://api.test', $http);

        self::assertSame(200, $balikobot->overview('cp')->status);
    }

    public function testNetworkErrorReturnsZeroStatusAndExposesItsText(): void {
        $http = new FakeHttpClient('', 0, 'Operation timed out after 60001 milliseconds');
        $balikobot = new Balikobot('user', 'secret-key', 'https://api.test', $http);

        $response = $balikobot->add('cp', [['eid' => 'A1']]);

        self::assertSame(0, $response->status);
        self::assertSame('Operation timed out after 60001 milliseconds', $balikobot->getLastCurlError());
        self::assertStringContainsString('spojení', Balikobot::describeStatus($response->status));
    }

    public function testThereIsNoErrorBeforeFirstRequest(): void {
        $balikobot = new Balikobot('user', 'secret-key', 'https://api.test', new FakeHttpClient());

        self::assertSame('', $balikobot->getLastCurlError());
    }

    public function testSuccessfulRequestClearsErrorFromPreviousOne(): void {
        $http = new FakeHttpClient();
        $http->queueResponse('', 0, 'Could not resolve host: api.test')
            ->queueResponse('{"status":200}');
        $balikobot = new Balikobot('user', 'secret-key', 'https://api.test', $http);

        $balikobot->overview('cp');
        self::assertSame('Could not resolve host: api.test', $balikobot->getLastCurlError());

        $balikobot->overview('cp');
        self::assertSame('', $balikobot->getLastCurlError());
    }

}
