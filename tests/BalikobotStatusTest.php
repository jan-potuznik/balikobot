<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Balikobot::class)]
final class BalikobotStatusTest extends TestCase {

    public function testKnownStatusHasDescriptionFromList(): void {
        self::assertSame(Balikobot::$response_status_codes[200], Balikobot::describeStatus(200));
        self::assertSame(Balikobot::$response_status_codes[503], Balikobot::describeStatus(503));
    }

    public function testStatusGivenAsTextIsUnderstoodTheSame(): void {
        self::assertSame(Balikobot::describeStatus(404), Balikobot::describeStatus('404'));
    }

    public function testZeroStatusMeansConnectionWasNotEstablished(): void {
        self::assertStringContainsString('spojení', Balikobot::describeStatus(0));
    }

    public function testUnknownStatusDoesNotFailAndReportsItsValue(): void {
        self::assertSame('neznámý stav API (299)', Balikobot::describeStatus(299));
    }

    /**
     * @param string|int $key
     */
    #[DataProvider('carrierErrors')]
    public function testCarrierErrorIsRecognised(string|int $key): void {
        self::assertTrue(Balikobot::isCarrierErrorAttribute($key));
    }

    /**
     * @return array<string, array{string|int}>
     */
    public static function carrierErrors(): array {
        return [
            'unified value' => ['carrier_error'],
            'other' => ['other'],
            'known carrier' => ['GLS_API'],
            'unknown carrier with _API suffix' => ['FOFR_API'],
            'unknown carrier with _error suffix' => ['sps_error'],
        ];
    }

    /**
     * @param string|int $key
     */
    #[DataProvider('dataErrors')]
    public function testDataErrorIsNotCarrierError(string|int $key): void {
        self::assertFalse(Balikobot::isCarrierErrorAttribute($key));
    }

    /**
     * @return array<string, array{string|int}>
     */
    public static function dataErrors(): array {
        return [
            'request attribute' => ['rec_zip'],
            'numeric key' => [406],
            'error without underscore' => ['error'],
            'suffix in the middle' => ['GLS_API_v2'],
        ];
    }

}
