<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Balikobot::class)]
final class BalikobotRequestTest extends TestCase {

    private FakeHttpClient $http;
    private Balikobot $balikobot;

    protected function setUp(): void {
        $this->http = new FakeHttpClient();
        $this->balikobot = new Balikobot('user', 'secret-key', 'https://api.test', $this->http);
    }

    public function testDefaultApiUrlIsProduction(): void {
        $balikobot = new Balikobot('user', 'secret-key', null, $this->http);
        $balikobot->overview('cp');

        self::assertSame('https://api.balikobot.cz/cp/overview', $this->http->lastUrl());
    }

    public function testAuthorizationHeaderCarriesEncodedCredentials(): void {
        $this->balikobot->overview('cp');

        self::assertContains('Authorization: Basic ' . base64_encode('user:secret-key'), $this->http->lastHeaders());
        self::assertContains('Content-Type: application/json', $this->http->lastHeaders());
    }

    public function testAddSendsDataToCarrierEndpoint(): void {
        $this->balikobot->add('cp', [['eid' => 'A1', 'rec_name' => 'Jan']]);

        self::assertSame('https://api.test/cp/add', $this->http->lastUrl());
        self::assertSame([['eid' => 'A1', 'rec_name' => 'Jan']], $this->http->lastDecodedBody());
    }

    public function testEmptyDataSendsNoBody(): void {
        $this->balikobot->add('cp', []);

        self::assertSame('https://api.test/cp/add', $this->http->lastUrl());
        self::assertNull($this->http->lastBody());
    }

    public function testDropOfSinglePackageSendsSingleObject(): void {
        $this->balikobot->drop('cp', 123);

        self::assertSame('https://api.test/cp/drop', $this->http->lastUrl());
        self::assertSame(['id' => 123], $this->http->lastDecodedBody());
    }

    public function testDropOfSeveralPackagesSendsArrayOfObjects(): void {
        $this->balikobot->drop('cp', [123, 456]);

        self::assertSame([['id' => 123], ['id' => 456]], $this->http->lastDecodedBody());
    }

    public function testTrackOfSinglePackageSendsSingleObject(): void {
        $this->balikobot->track('cp', 'DR123');

        self::assertSame('https://api.test/cp/track', $this->http->lastUrl());
        self::assertSame(['id' => 'DR123'], $this->http->lastDecodedBody());
    }

    public function testTrackOfSeveralPackagesSendsArrayOfObjects(): void {
        $this->balikobot->track('cp', ['DR123', 'DR456']);

        self::assertSame([['id' => 'DR123'], ['id' => 'DR456']], $this->http->lastDecodedBody());
    }

    public function testTrackV2UsesApiVersionPrefix(): void {
        $this->balikobot->trackV2('cp', 'DR123');

        self::assertSame('https://api.test/v2/cp/track', $this->http->lastUrl());
        self::assertSame(['id' => 'DR123'], $this->http->lastDecodedBody());
    }

    public function testTrackstatusOfSinglePackageSendsSingleObject(): void {
        $this->balikobot->trackstatus('cp', 'DR123');

        self::assertSame('https://api.test/cp/trackstatus', $this->http->lastUrl());
        self::assertSame(['id' => 'DR123'], $this->http->lastDecodedBody());
    }

    public function testTrackstatusOfSeveralPackagesSendsArrayOfObjects(): void {
        $this->balikobot->trackstatus('cp', ['DR123', 'DR456']);

        self::assertSame([['id' => 'DR123'], ['id' => 'DR456']], $this->http->lastDecodedBody());
    }

    #[DataProvider('methodsWithoutBody')]
    public function testMethodWithoutParametersSendsEmptyRequest(string $method, string $path): void {
        $this->balikobot->{$method}('cp');

        self::assertSame('https://api.test/cp/' . $path, $this->http->lastUrl());
        self::assertNull($this->http->lastBody());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function methodsWithoutBody(): array {
        return [
            'overview' => ['overview', 'overview'],
            'services' => ['services', 'services'],
            'manipulationunits' => ['manipulationunits', 'manipulationunits'],
            'countries4service' => ['countries4service', 'countries4service'],
            'branches' => ['branches', 'branches'],
            'fullbranches' => ['fullbranches', 'fullbranches'],
            'zipcodes' => ['zipcodes', 'zipcodes'],
            'orderview' => ['orderview', 'orderview'],
        ];
    }

    public function testLabelsSendsPackageIdsUnderPackageIdsKey(): void {
        $this->balikobot->labels('cp', [1, 'A2']);

        self::assertSame('https://api.test/cp/labels', $this->http->lastUrl());
        self::assertSame(['package_ids' => [1, 'A2']], $this->http->lastDecodedBody());
    }

    public function testPackageHasPackageIdInPathAndNoBody(): void {
        $this->balikobot->package('cp', 987);

        self::assertSame('https://api.test/cp/package/987', $this->http->lastUrl());
        self::assertNull($this->http->lastBody());
    }

    public function testOrderWithoutNoteAndDateSendsOnlyPackageIds(): void {
        $this->balikobot->order('cp', [1, 2]);

        self::assertSame('https://api.test/cp/order', $this->http->lastUrl());
        self::assertSame(['package_ids' => [1, 2]], $this->http->lastDecodedBody());
    }

    public function testOrderAddsNoteAndDateWhenFilledIn(): void {
        $this->balikobot->order('ppl', [1], 'volat predem', '2026-11-20');

        self::assertSame(
            ['package_ids' => [1], 'note' => 'volat predem', 'date' => '2026-11-20'],
            $this->http->lastDecodedBody()
        );
    }

    public function testOrderIgnoresEmptyNoteAndDate(): void {
        $this->balikobot->order('ppl', [1], '', '');

        self::assertSame(['package_ids' => [1]], $this->http->lastDecodedBody());
    }

    public function testOrderviewOfSpecificOrderHasIdInPath(): void {
        $this->balikobot->orderview('cp', 55);

        self::assertSame('https://api.test/cp/orderview/55', $this->http->lastUrl());
    }

    public function testBranchesWithServiceHasServiceIdInPath(): void {
        $this->balikobot->branches('zasilkovna', 1);

        self::assertSame('https://api.test/zasilkovna/branches/1', $this->http->lastUrl());
    }

    public function testFullbranchesWithServiceHasServiceIdInPath(): void {
        $this->balikobot->fullbranches('zasilkovna', 'CP');

        self::assertSame('https://api.test/zasilkovna/fullbranches/CP', $this->http->lastUrl());
    }

    public function testZeroIsValidServiceIdAndStaysInPath(): void {
        $this->balikobot->branches('zasilkovna', 0);

        self::assertSame('https://api.test/zasilkovna/branches/0', $this->http->lastUrl());
    }

    public function testEmptyStringIsNotAddedToPath(): void {
        $this->balikobot->branches('zasilkovna', '');

        self::assertSame('https://api.test/zasilkovna/branches', $this->http->lastUrl());
    }

    public function testZipcodesComposesServiceAndCountryInFixedOrder(): void {
        $this->balikobot->zipcodes('ppl', 1, 'CZ');

        self::assertSame('https://api.test/ppl/zipcodes/1/CZ', $this->http->lastUrl());
    }

    public function testZipcodesWithServiceOnly(): void {
        $this->balikobot->zipcodes('ppl', 1);

        self::assertSame('https://api.test/ppl/zipcodes/1', $this->http->lastUrl());
    }

}
