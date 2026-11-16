<?php

/**
 * http://www.balikobot.cz/dokumentace/Balikobot-dokumentace-API.pdf // v1.79 2017-01-30
 *
 * @author http://www.janpotuznik.cz/
 * @link https://github.com/jan-potuznik/balikobot/
 */
class Balikobot {

    /** @var string[] */
    public static array $response_status_codes = [
        200 => 'OK, operace proběhla v pořádku',
        208 => 'položka s doloženým ID již existuje. Data, která jsou navrácena, patří k původnímu záznamu',
        400 => 'operace neproběhla v pořádku, zkontrolujte konkrétní data',
        401 => 'Unauthorized - nejspíš chyba na straně Balikobotu',
        402 => 'data k vícekusové zásilce nejsou odeslána kompletní',
        403 => 'přepravce není pro použité klíče aktivovaný',
        404 => 'zásilka neexistuje, nebo již byla zpracována',
        405 => 'balík již byl odeslán ke svozu, nelze s ním dále pracovat',
        406 => 'nedorazila žádná data ke zpracování nebo nemůžou být akceptována',
        409 => 'konfigurační soubor daného dopravce nebo profil není vyplněn/konflikt mezi přijatými daty u zásilky (například u DPD pokud je u zásilky, která má být zaslána službou DPD Classic, zaslána dobírka cod_price a zároveň příznak, že se jedná o výměnnou zásilku swap)',
        413 => 'špatný formát dat',
        416 => 'datum má špatný formát nebo není povoleno',
        423 => 'tato funkce je dostupná jen pro „živé klíče“',
        500 => 'chyba na straně dopravce - výpadek, vypršení časového limitu nebo neplatná odpověď',
        501 => 'technologie toho dopravce ještě není implementována, pro bližší informace sledujte web www.balikobot.cz',
        502 => 'chybná odpověď ze systému dopravce',
        503 => 'technologie dopravce není dostupná, požadavek bude vyřízen později',
    ];

    /** @var string[][] */
    public static array $key_status_codes = [
        406 => [
            'eid' => 'Nedorazilo eshop ID.',
            'service_type' => 'Nedorazilo ID vybrané služby přepravce.',
            'cod_currency' => 'Nedorazil ISO kód měny.',
            'branch_id' => 'Nedorazilo ID pobočky.',
            'rec_name' => 'Nedorazilo jméno příjemce.',
            'rec_street' => 'Nedorazila ulice s číslem popisným příjemce.',
            'rec_city' => 'Nedorazilo město příjemce.',
            'rec_zip' => 'Nedorazilo PSČ příjemce.',
            'rec_country' => 'Nedorazil ISO kód země příjemce.',
            'rec_phone' => 'Nedorazilo telefonní číslo příjemce.',
            'rec_email' => 'Nedorazil email příjemce.',
            'price' => 'Nedorazila udaná cena zásilky.',
            'vs' => 'Nedorazil variabilní symbol pro dobírkovou zásilku.',
            'service_range' => 'Balíček nelze přidat, protože není vyplněna číselná řada v klientské zóně.',
            'config_data' => 'Balíček nelze přidat, protože chybí potřebná data v klientské zóně.',
            'weight' => 'Nedorazil údaj o váze zásilky.',
        ],
        409 => [
            'cod_price' => 'Nepovolená kombinace služeb dobírky a výměnné zásilky.',
            'swap' => 'Nepovolená kombinace služeb dobírky a výměnné zásilky.',
        ],
        413 => [
            'eid' => 'Eshop ID je delší než je maximální povolená délka.',
            'service_type' => 'Neznámé ID služby přepravce.',
            'cod_price' => 'Nepovolená dobírka.',
            'cod_currency' => 'Nepovolený ISO kód měny.',
            'price' => 'Nepovolená částka udané ceny.',
            'branch_id' => 'Neznámé ID pobočky.',
            'rec_email' => 'Špatný formát emailu příjemce.',
            'order_number' => 'Sdružená zásilka není povolena.',
            'rec_country' => 'Nepovolený ISO kód země příjemce.',
            'rec_zip' => 'Nepovolené PSČ příjemce.',
            'weight' => 'Neplatný formát váhy/váha překračuje maximální povolenou hodnotu.',
            'swap' => 'Výměnná zásilka není pro vybranou službu povolena.',
            'rec_phone' => 'Špatný formát telefonního čísla.',
            'credit_card' => 'Platba kartou není pro tuto službu/pobočku povolena.',
            'service_range' => 'Balíček nelze přidat, protože číselná řada v klientské zóně je již přečerpaná.',
        ],
        416 => [
            'delivery_date' => 'Datum má špatný formát nebo není povoleno.',
        ],
    ];

    /** @var string[] */
    public static array $key_status_codes_simple = [
        406 => 'Nedorazila žádná data ke zpracování.',
        409 => 'Nepovolená kombinace služeb dobírky a výměnné zásilky.',
        413 => 'Špatný formát dat.',
        416 => 'Datum má špatný formát nebo není povoleno.',
    ];

    /**
     * Atributy, kterými API označuje chybu na straně dopravce (výpadek, vypršení časového limitu,
     * neplatná odpověď nebo chyba serveru dopravce) - hodnotou je text chyby od dopravce, ne chybový kód.
     *
     * Od 7. 12. 2026 Balíkobot tyto chyby sjednocuje na jedinou hodnotu `carrier_error`,
     * ostatní položky jsou ponechány kvůli starším odpovědím a přechodnému období.
     *
     * @var string[]
     */
    public static array $carrier_error_attributes = [
        'carrier_error',
        'other',
        'GLS_API',
        'DPD_API',
        'zasilkovna_error',
        'tnt_error',
    ];
    private string $user;
    private string $apiKey;
    private string $apiUrl = 'https://api.balikobot.cz';
    private BalikobotHttpClient $httpClient;

    /** @var callable|null */
    private $loggerCallback = null;

    /** @var string text poslední síťové chyby, prázdný řetězec když požadavek proběhl */
    private string $lastCurlError = '';

    /**
     * @param BalikobotHttpClient|null $httpClient vlastní přenosová vrstva (třeba Guzzle),
     *     bez ní se použije curl
     */
    public function __construct(
        string $user,
        string $apiKey,
        ?string $apiUrl = null,
        ?BalikobotHttpClient $httpClient = null
    ) {
        $this->user = $user;
        $this->apiKey = $apiKey;
        if ($apiUrl !== null) {
            $this->apiUrl = $apiUrl;
        }
        $this->httpClient = $httpClient ?? new BalikobotCurlClient();
    }

    /**
     * Jde o chybu na straně dopravce, ne o chybu v datech požadavku?
     * Kromě vyjmenovaných atributů se akceptují i neznámí dopravci podle přípony (`*_API`, `*_error`).
     *
     * @param string|int $key název atributu z chybové odpovědi
     */
    public static function isCarrierErrorAttribute(string|int $key): bool {
        return in_array((string)$key, self::$carrier_error_attributes, true)
            || preg_match('/(_API|_error)$/', (string)$key) === 1;
    }

    /**
     * Popis stavu odpovědi, který nepadá na neznámé hodnotě.
     * Status přichází z JSON odpovědi API, takže může být číslo i řetězec.
     */
    public static function describeStatus(mixed $status): string {
        $status = (int)$status;
        if ($status === 0) {
            return 'spojení s API se nepodařilo navázat (vypršení časového limitu nebo síťová chyba)';
        }

        return self::$response_status_codes[$status] ?? 'neznámý stav API (' . $status . ')';
    }

    /**
     * Nepovinná část cesty požadavku - prázdný řetězec, když hodnota nebyla předána.
     */
    private static function pathSegment(int|string|null $value): string {
        if ($value === null || $value === '') {
            return '';
        }

        return '/' . $value;
    }

    /**
     * @param mixed[]|null $postData
     */
    private function curlRequest(string $carrier, string $pozadavek, ?array $postData = null): mixed {
        $action = $carrier . '/' . $pozadavek;
        $encodedData = json_encode($postData, JSON_THROW_ON_ERROR);
        $headers = [
            'Authorization: Basic ' . base64_encode($this->user . ':' . $this->apiKey),
            'Content-Type: application/json',
        ];
        if ($this->loggerCallback !== null) {
            call_user_func($this->loggerCallback, 'request', $action, $encodedData);
        }
        $result = $this->httpClient->post(
            $this->apiUrl . '/' . $action,
            ($postData !== null && $postData !== [] ? $encodedData : null),
            $headers
        );
        $this->lastCurlError = $result['error'];
        $response = self::decodeResponse($result['body'], $result['httpStatus']);
        if ($this->loggerCallback !== null) {
            $loggedResponse = ($this->lastCurlError !== '' ? 'CURL ERROR: ' . $this->lastCurlError : $result['body']);
            call_user_func($this->loggerCallback, 'response', $action, $loggedResponse);
        }

        return $response;
    }

    /**
     * Odpověď API, nebo náhradní objekt se statusem, když tělo nelze přečíst
     * (síťová chyba, prázdná odpověď nebo chybová stránka místo JSON).
     */
    private static function decodeResponse(string $body, int $httpStatus): mixed {
        try {
            $response = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $response = null;
        }
        if ($response === null) {
            $response = new stdClass();
            $response->status = $httpStatus;
        }

        return $response;
    }

    /**
     * Text poslední síťové chyby (nenavázané spojení, vypršení časového limitu),
     * prázdný řetězec když poslední požadavek proběhl.
     */
    public function getLastCurlError(): string {
        return $this->lastCurlError;
    }

    /**
     * @param callable $callback accepting three strings, 'request'/'response', action and JSON encoded data
     */
    public function registerLogger(callable $callback): void {
        $this->loggerCallback = $callback;
    }

    /**
     * @param mixed[] $postData
     */
    public function add(string $carrier, array $postData): mixed {
        return $this->curlRequest($carrier, 'add', $postData);
    }

    /**
     * Update údajů jednotlivých balíků není možný.
     * Pokud potřebujete změnit údaje na některé zásilce, je potřeba ji nejprve odmazat pomocí metody DROP a poté znovu vložit přes metodu ADD.
     *
     * @param int|int[] $packageIdOrIds ID balíku, které předalo API při vložení balíku do systému
     */
    public function drop(string $carrier, int|array $packageIdOrIds): mixed {
        $postData = [];
        // mozno odeslat vice takovych poli, jako u add, nepodporuje Intime a Ulozenka
        if (is_array($packageIdOrIds)) {
            foreach ($packageIdOrIds as $packageId) {
                $postData[] = ['id' => $packageId];
            }
        } else {
            $postData = ['id' => $packageIdOrIds];
        }

        return $this->curlRequest($carrier, 'drop', $postData);
    }

    /**
     * Vrací všechny stavy balíku/balíků.
     *
     * @param string|string[] $carrierIdOrIds ID v rámci přepravce předané metodou ADD
     */
    public function track(string $carrier, string|array $carrierIdOrIds): mixed {
        $postData = [];
        // mozno odeslat vice takovych poli, jako u add
        if (is_array($carrierIdOrIds)) {
            foreach ($carrierIdOrIds as $carrierId) {
                $postData[] = ['id' => $carrierId];
            }
        } else {
            $postData = ['id' => $carrierIdOrIds];
        }

        return $this->curlRequest($carrier, 'track', $postData);
    }

    /**
     * Vrací všechny stavy balíku. Funguje s default $apiUrl.
     *
     * @param string $carrierId ID v rámci přepravce předané metodou ADD
     */
    public function trackV2(string $carrier, string $carrierId): mixed {
        $postData = ['id' => $carrierId];

        return $this->curlRequest('v2/' . $carrier, 'track', $postData);
    }

    /**
     * Vrací poslední stav balíku/balíků ve formě čísla a textové prezentace.
     *
     * @param string|string[] $carrierIdOrIds ID v rámci přepravce předané metodou ADD
     */
    public function trackstatus(string $carrier, string|array $carrierIdOrIds): mixed {
        $postData = [];
        // mozno odeslat vice takovych poli, jako u add
        if (is_array($carrierIdOrIds)) {
            foreach ($carrierIdOrIds as $carrierId) {
                $postData[] = ['id' => $carrierId];
            }
        } else {
            $postData = ['id' => $carrierIdOrIds];
        }

        return $this->curlRequest($carrier, 'trackstatus', $postData);
    }

    /**
     * Soupis dosud neodeslaných balíků se základními informacemi.
     */
    public function overview(string $carrier): mixed {
        return $this->curlRequest($carrier, 'overview');
    }

    /**
     * Metoda vracející hromadné PDF se štítky pro vyžádané balíčky (package_ids) u vybraného dopravce.
     * Doplňková metoda pro klienty, kteří netisknou štítky ihned po přidání metodou ADD, ale až dávkově a chtějí mít štítky v hromadném PDF.
     *
     * @param (int|string)[] $packageIds
     */
    public function labels(string $carrier, array $packageIds): mixed {
        $aData = ['package_ids' => $packageIds];

        return $this->curlRequest($carrier, 'labels', $aData);
    }

    /**
     * Kompletní informace ke konkrétnímu balíku.
     *
     * @param int $packageId ID balíku, které předalo API při vložení balíku do systému
     */
    public function package(string $carrier, int $packageId): mixed {
        return $this->curlRequest($carrier, 'package/' . $packageId);
    }

    /**
     * Předání dat do systému přepravce („objednání svozu“) pro dosud neodeslané balíky.
     *
     * @param (int|string)[] $packageIds nepovinný (pro vlastní řešení), výčet ID balíků (package_id – vrácené při metodě ADD), které budou zahrnuty do objednávaného svozu. Pokud je tento parametr prázdný, budou do objednávaného svozu zahrnuty všechny dosud neodeslané balíky. Žádáme vývojáře e-shopových řešení, aby nám tuto informaci zasílali povinně.
     * @param string|null $note nepovinný, poznámka pro dopravce (jen pro PPL)
     * @param string|null $date nepovinný, datum přijetí dopravce ke svozu (jen pro PPL) – pokud není vyplněno, vyplní se datum aktuálního dne.
     */
    public function order(string $carrier, array $packageIds, ?string $note = null, ?string $date = null): mixed {
        $postData = [];
        $postData['package_ids'] = $packageIds;
        if ($note !== null && $note !== '') {
            $postData['note'] = $note;
        }
        if ($date !== null && $date !== '') {
            $postData['date'] = $date;
        }

        return $this->curlRequest($carrier, 'order', $postData);
    }

    /**
     * Informace k poslednímu/konkrétnímu svozu – file_url, handover_url, labels_url, order_id.
     */
    public function orderview(string $carrier, int|string|null $orderId = null): mixed {
        return $this->curlRequest($carrier, 'orderview' . self::pathSegment($orderId));
    }

    /**
     * Vrací seznam služeb, které se dají použít u daného dopravce.
     */
    public function services(string $carrier): mixed {
        return $this->curlRequest($carrier, 'services');
    }

    /**
     * Vrací seznam názvů a id (hodnot pro atribut mu_type) možných manipulačních jednotek pro paletovou přepravu (Geis Cargo).
     */
    public function manipulationunits(string $carrier): mixed {
        return $this->curlRequest($carrier, 'manipulationunits');
    }

    /**
     * Vrací seznam poboček, na které se dají posílat zásilky u konkrétní služby.
     * Čísla poboček se poté dají předat do atributu branch_id v metodě ADD.
     * U dopravce Zásilkovna se zde předávají i čísla dalších služeb (např. Česká pošta nebo Expresní doručení Ostrava), které se předávají také do atributu branch_id.
     */
    public function branches(string $carrier, int|string|null $serviceType = null): mixed {
        return $this->curlRequest($carrier, 'branches' . self::pathSegment($serviceType));
    }

    /**
     * Obdoba metody BRANCHES s tím, že se pro každou pobočku vrací více informací.
     * Momentálně dostupná jen pro přepravce Zásilkovna.
     */
    public function fullbranches(string $carrier, int|string|null $serviceType = null): mixed {
        return $this->curlRequest($carrier, 'fullbranches' . self::pathSegment($serviceType));
    }

    /**
     * Seznam států, do kterých lze zasílat skrze jednotlivé služby přepravce.
     */
    public function countries4service(string $carrier): mixed {
        return $this->curlRequest($carrier, 'countries4service');
    }

    /**
     * Vrací výčet PSČ, na které se dají posílat zásilky u konkrétní služby.
     * Tato PSČ jsou platná pro atribut rec_zip v metodě ADD.
     */
    public function zipcodes(string $carrier, int|string|null $serviceType = null, ?string $country = null): mixed {
        return $this->curlRequest($carrier, 'zipcodes' . self::pathSegment($serviceType) . self::pathSegment($country));
    }

}
