<?php

/**
 * http://www.balikobot.cz/dokumentace/Balikobot-dokumentace-API.pdf // v1.79 2017-01-30
 *
 * @author http://www.janpotuznik.cz/
 * @link https://github.com/jan-potuznik/balikobot/
 */
class balikobot {

    /** @var string[] */
    public static $response_status_codes = [
        200 => 'OK, operace proběhla v pořádku',
        208 => 'položka s doloženým ID již existuje. Data, která jsou navrácena, patří k původnímu záznamu',
        400 => 'operace neproběhla v pořádku, zkontrolujte konkrétní data',
        401 => 'Unauthorized - nejspíš chyba na straně Balikobotu',
        403 => 'přepravce není pro použité klíče aktivovaný',
        404 => 'zásilka neexistuje, nebo již byla zpracována',
        406 => 'nedorazila žádná data ke zpracování nebo nemůžou být akceptována',
        409 => 'konfigurační soubor daného dopravce nebo profil není vyplněn/konflikt mezi přijatými daty u zásilky (například u DPD pokud je u zásilky, která má být zaslána službou DPD Classic, zaslána dobírka cod_price a zároveň příznak, že se jedná o výměnnou zásilku swap)',
        413 => 'špatný formát dat',
        423 => 'tato funkce je dostupná jen pro „živé klíče“',
        501 => 'technologie toho dopravce ještě není implementována, pro bližší informace sledujte web www.balikobot.cz',
        503 => 'technologie dopravce není dostupná, požadavek bude vyřízen později',
    ];

    /** @var string[][] */
    public static $key_status_codes = [
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
            'delivery_date' => 'Datum má špatný formát nebo není povoleno.'
        ],
    ];

    /** @var string[] */
    public static $key_status_codes_simple = [
        406 => 'Nedorazila žádná data ke zpracování.',
        409 => 'Nepovolená kombinace služeb dobírky a výměnné zásilky.',
        413 => 'Špatný formát dat.',
        416 => 'Datum má špatný formát nebo není povoleno.',
    ];

    /** @var string */
    private $user;
    /** @var string */
    private $apiKey;
    /** @var string */
    private $apiUrl = 'https://api.balikobot.cz';
    /** @var callable */
    private $loggerCallback;

    /**
     * @param string $user
     * @param string $apiKey
     * @param string|null $apiUrl
     */
    public function __construct($user, $apiKey, $apiUrl = null) {
        $this->user = $user;
        $this->apiKey = $apiKey;
        if ($apiUrl !== null) {
            $this->apiUrl = $apiUrl;
        }
    }

    /**
     * Můžete případně použít třeba Guzzle.
     *
     * @param string $carrier
     * @param string $pozadavek
     * @param array|null $postData
     * @return mixed|stdClass
     */
    private function curlRequest($carrier, $pozadavek, $postData = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl . '/' . $carrier . '/' . $pozadavek);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        // uncomment only if really needed
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if ($postData) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . base64_encode($this->user . ':' . $this->apiKey), "Content-Type: application/json"]);
        if ($this->loggerCallback) {
            call_user_func($this->loggerCallback, 'request', $carrier . '/' . $pozadavek, json_encode($postData));
        }
        $curlResponse = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $response = json_decode($curlResponse, false);
        if ($response === null) {
            $response = new stdClass();
            $response->status = $httpStatus;
        }
        curl_close($ch);
        if ($this->loggerCallback) {
            call_user_func($this->loggerCallback, 'response', $carrier . '/' . $pozadavek, $curlResponse);
        }
        return $response;
    }

    /**
     * @param callback $callback accepting three strings, 'request'/'response', action and JSON encoded data
     * @return void
     */
    public function registerLogger($callback) {
        $this->loggerCallback = $callback;
    }

    /**
     * @param string $carrier
     * @param array $postData
     * @return mixed|stdClass
     */
    public function add($carrier, $postData) {
        return $this->curlRequest($carrier, 'add', $postData);
    }

    /**
     * Update údajů jednotlivých balíků není možný.
     * Pokud potřebujete změnit údaje na některé zásilce, je potřeba ji nejprve odmazat pomocí metody DROP a poté znovu vložit přes metodu ADD.
     *
     * @param string $carrier
     * @param int|int[] $packageIdOrIds ID balíku, které předalo API při vložení balíku do systému
     * @return mixed|stdClass
     */
    public function drop($carrier, $packageIdOrIds) {
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
     * @param string $carrier
     * @param string|string[] $carrierIdOrIds ID v rámci přepravce předané metodou ADD
     * @return mixed|stdClass
     */
    public function track($carrier, $carrierIdOrIds) {
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
     * Vrací poslední stav balíku/balíků ve formě čísla a textové prezentace.
     *
     * @param string $carrier
     * @param string|string[] $carrierIdOrIds ID v rámci přepravce předané metodou ADD
     * @return mixed|stdClass
     */
    public function trackstatus($carrier, $carrierIdOrIds) {
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
     * @param string $carrier
     * @return mixed|stdClass
     */
    public function overview($carrier) {
        return $this->curlRequest($carrier, 'overview');
    }

    /**
     * Metoda vracející hromadné PDF se štítky pro vyžádané balíčky (package_ids) u vybraného dopravce.
     * Doplňková metoda pro klienty, kteří netisknou štítky ihned po přidání metodou ADD, ale až dávkově a chtějí mít štítky v hromadném PDF.
     *
     * @param string $carrier
     * @param array $packageIds
     * @return mixed|stdClass
     */
    public function labels($carrier, $packageIds) {
        $aData = ['package_ids' => $packageIds];
        return $this->curlRequest($carrier, 'labels', $aData);
    }

    /**
     * Kompletní informace ke konkrétnímu balíku.
     *
     * @param string $carrier
     * @param int $packageId ID balíku, které předalo API při vložení balíku do systému
     * @return mixed|stdClass
     */
    public function package($carrier, $packageId) {
        return $this->curlRequest($carrier, 'package/' . $packageId);
    }

    /**
     * Předání dat do systému přepravce („objednání svozu“) pro dosud neodeslané balíky.
     *
     * @param string $carrier
     * @param array $packageIds nepovinný (pro vlastní řešení), výčet ID balíků (package_id – vrácené při metodě ADD), které budou zahrnuty do objednávaného svozu. Pokud je tento parametr prázdný, budou do objednávaného svozu zahrnuty všechny dosud neodeslané balíky. Žádáme vývojáře e-shopových řešení, aby nám tuto informaci zasílali povinně.
     * @param string $note nepovinný, poznámka pro dopravce (jen pro PPL)
     * @param string $date nepovinný, datum přijetí dopravce ke svozu (jen pro PPL) – pokud není vyplněno, vyplní se datum aktuálního dne.
     * @return mixed|stdClass
     */
    public function order($carrier, $packageIds, $note = null, $date = null) {
        $postData = [];
        $postData['package_ids'] = $packageIds;
        if ($note) {
            $postData['note'] = $note;
        }
        if ($date) {
            $postData['date'] = $date;
        }
        return $this->curlRequest($carrier, 'order', $postData);
    }

    /**
     * Informace k poslednímu/konkrétnímu svozu – file_url, handover_url, labels_url, order_id.
     * @param string $carrier
     * @param null $orderId
     * @return mixed|stdClass
     */
    public function orderview($carrier, $orderId = null) {
        return $this->curlRequest($carrier, 'orderview' . ($orderId ? '/' . $orderId : ''));
    }

    /**
     * Vrací seznam služeb, které se dají použít u daného dopravce.
     * @param string $carrier
     * @return mixed|stdClass
     */
    public function services($carrier) {
        return $this->curlRequest($carrier, 'services');
    }

    /**
     * Vrací seznam názvů a id (hodnot pro atribut mu_type) možných manipulačních jednotek pro paletovou přepravu (Geis Cargo).
     * @param string $carrier
     * @return mixed|stdClass
     */
    public function manipulationunits($carrier) {
        return $this->curlRequest($carrier, 'manipulationunits');
    }

    /**
     * Vrací seznam poboček, na které se dají posílat zásilky u konkrétní služby.
     * Čísla poboček se poté dají předat do atributu branch_id v metodě ADD.
     * U dopravce Zásilkovna se zde předávají i čísla dalších služeb (např. Česká pošta nebo Expresní doručení Ostrava), které se předávají také do atributu branch_id.
     * @param string $carrier
     * @param null $serviceType
     * @return mixed|stdClass
     */
    public function branches($carrier, $serviceType = null) {
        return $this->curlRequest($carrier, 'branches' . ($serviceType ? '/' . $serviceType : ''));
    }

    /**
     * Obdoba metody BRANCHES s tím, že se pro každou pobočku vrací více informací.
     * Momentálně dostupná jen pro přepravce Zásilkovna.
     * @param string $carrier
     * @param null $serviceType
     * @return mixed|stdClass
     */
    public function fullbranches($carrier, $serviceType = null) {
        return $this->curlRequest($carrier, 'fullbranches' . ($serviceType ? '/' . $serviceType : ''));
    }

    /**
     * Seznam států, do kterých lze zasílat skrze jednotlivé služby přepravce.
     * @param string $carrier
     * @return mixed|stdClass
     */
    public function countries4service($carrier) {
        return $this->curlRequest($carrier, 'countries4service');
    }

    /**
     * Vrací výčet PSČ, na které se dají posílat zásilky u konkrétní služby.
     * Tato PSČ jsou platná pro atribut rec_zip v metodě ADD.
     * @param string $carrier
     * @param string|null $serviceType
     * @param string|null $country
     * @return mixed|stdClass
     */
    public function zipcodes($carrier, $serviceType = null, $country = null) {
        return $this->curlRequest($carrier, 'zipcodes' . ($serviceType ? '/' . $serviceType : '') . ($country ? '/' . $country : ''));
    }

}
