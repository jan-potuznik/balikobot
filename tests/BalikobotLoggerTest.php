<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Balikobot::class)]
final class BalikobotLoggerTest extends TestCase {

    /** @var array{string, string, string}[] */
    private array $records = [];

    /**
     * @param array{body: string, httpStatus: int, error: string}|null $response
     */
    private function balikobotWithLogger(?array $response = null): Balikobot {
        $http = new FakeHttpClient(...($response ?? []));
        $balikobot = new Balikobot('user', 'secret-key', 'https://api.test', $http);
        $balikobot->registerLogger(function (string $type, string $action, string $data): void {
            $this->records[] = [$type, $action, $data];
        });

        return $balikobot;
    }

    public function testLoggerReceivesRequestAndResponse(): void {
        $balikobot = $this->balikobotWithLogger(['body' => '{"status":200}', 'httpStatus' => 200, 'error' => '']);

        $balikobot->add('cp', [['eid' => 'A1']]);

        self::assertSame([
            ['request', 'cp/add', '[{"eid":"A1"}]'],
            ['response', 'cp/add', '{"status":200}'],
        ], $this->records);
    }

    public function testLoggerKnowsWholeActionIncludingApiVersion(): void {
        $balikobot = $this->balikobotWithLogger();

        $balikobot->trackV2('cp', 'DR123');

        self::assertSame('v2/cp/track', $this->records[0][1]);
    }

    public function testRequestWithoutDataLogsNull(): void {
        $balikobot = $this->balikobotWithLogger();

        $balikobot->overview('cp');

        self::assertSame(['request', 'cp/overview', 'null'], $this->records[0]);
    }

    public function testNetworkErrorTextIsLoggedInsteadOfResponse(): void {
        $balikobot = $this->balikobotWithLogger(['body' => '', 'httpStatus' => 0, 'error' => 'Operation timed out']);

        $balikobot->overview('cp');

        self::assertSame(['response', 'cp/overview', 'CURL ERROR: Operation timed out'], $this->records[1]);
    }

    public function testRequestRunsWithoutRecordsWhenNoLoggerRegistered(): void {
        $balikobot = new Balikobot('user', 'secret-key', 'https://api.test', new FakeHttpClient());

        self::assertSame(200, $balikobot->overview('cp')->status);
        self::assertSame([], $this->records);
    }

}
