<?php

/**
 * http://www.balikobot.cz/dokumentace/Balikobot-dokumentace-API.pdf // v1.79 2017-01-30
 */
class balikobot {

	private static $USER = '';
	private static $API_KEY = '';
	public static $response_status_codes = array(
		200 => 'OK, operace proběhla v pořádku',
		208 => 'položka s doloženým ID již existuje. Data, která jsou navrácena, patří k původnímu záznamu',
		400 => 'operace neproběhla v pořádku, zkontrolujte konkrétní data',
		403 => 'přepravce není pro použité klíče aktivovaný',
		404 => 'zásilka neexistuje, nebo již byla zpracována',
		406 => 'nedorazila žádná data ke zpracování nebo nemůžou být akceptována',
		409 => 'konfigurační soubor daného dopravce nebo profil není vyplněn/konflikt mezi přijatými daty u zásilky (například u DPD pokud je u zásilky, která má být zaslána službou DPD Classic, zaslána dobírka cod_price a zároveň příznak, že se jedná o výměnnou zásilku swap)',
		413 => 'špatný formát dat',
		423 => 'tato funkce je dostupná jen pro „živé klíče“',
		501 => 'technologie toho dopravce ještě není implementována, pro bližší informace sledujte web www.balikobot.cz',
		503 => 'technologie dopravce není dostupná, požadavek bude vyřízen později',
	);
	public static $key_status_codes = array(
		406 => 'Nedorazila žádná data ke zpracování.',
		409 => 'Nepovolená kombinace služeb dobírky a výměnné zásilky.',
		413 => 'Špatný formát dat.',
		416 => 'Datum má špatný formát nebo není povoleno.',
	);

	public static function init($user, $api_key) {
		self::$USER = $user;
		self::$API_KEY = $api_key;
	}

	private static function curl_request($dopravce, $pozadavek, $aData = NULL) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://api.balikobot.cz/' . $dopravce . '/' . $pozadavek);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if ($aData) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($aData));
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Basic " . base64_encode(self::$USER . ':' . self::$API_KEY), "Content-Type: application/json"));
		$response = curl_exec($ch);
		curl_close($ch);
		$aResponse = json_decode($response);
		return $aResponse;
	}

	public static function add($dopravce, $aData) {
		return self::curl_request($dopravce, 'add', $aData);
	}

	/**
	 * Update údajů jednotlivých balíků není možný.
	 * Pokud potřebujete změnit údaje na některé zásilce, je potřeba ji nejprve odmazat pomocí metody DROP a poté znovu vložit přes metodu ADD.
	 *
	 * @param type $package_id ID balíku, které předalo API při vložení balíku do systému
	 */
	public static function drop($dopravce, $package_id) {
		// mozno odeslat vice takovych poli, jako u add, nepodporuje Intime a Ulozenka
		if (is_array($package_id)) {
			foreach ($package_id as $p_id) {
				$aData[] = array('id' => $p_id);
			}
		} else {
			$aData = array('id' => $package_id);
		}

		return self::curl_request($dopravce, 'drop', $aData);
	}

	/**
	 * Vrací všechny stavy balíku/balíků.
	 *
	 * @param type $carrier_id ID v rámci přepravce předané metodou ADD
	 */
	public static function track($dopravce, $carrier_id) {
		// mozno odeslat vice takovych poli, jako u add
		if (is_array($carrier_id)) {
			foreach ($carrier_id as $c_id) {
				$aData[] = array('id' => $c_id);
			}
		} else {
			$aData = array('id' => $carrier_id);
		}
		$aData = array('id' => $carrier_id);
		return self::curl_request($dopravce, 'track', $aData);
	}

	/**
	 * Vrací poslední stav balíku/balíků ve formě čísla a textové prezentace.
	 *
	 * @param type $carrier_id ID v rámci přepravce předané metodou ADD
	 */
	public static function trackstatus($dopravce, $carrier_id) {
		// mozno odeslat vice takovych poli, jako u add
		if (is_array($carrier_id)) {
			foreach ($carrier_id as $c_id) {
				$aData[] = array('id' => $c_id);
			}
		} else {
			$aData = array('id' => $carrier_id);
		}
		$aData = array('id' => $carrier_id);
		return self::curl_request($dopravce, 'trackstatus', $aData);
	}

	/**
	 * Soupis dosud neodeslaných balíků se základními informacemi.
	 */
	public static function overview($dopravce) {
		return self::curl_request($dopravce, 'overview');
	}

	/**
	 * Metoda vracející hromadné PDF se štítky pro vyžádané balíčky (package_ids) u vybraného dopravce.
	 * Doplňková metoda pro klienty, kteří netisknou štítky ihned po přidání metodou ADD, ale až dávkově a chtějí mít štítky v hromadném PDF.
	 *
	 * @param array $package_ids
	 */
	public static function labels($dopravce, $package_ids) {
		$aData = array('package_ids' => $package_ids);
		return self::curl_request($dopravce, 'labels', $aData);
	}

	/**
	 * Kompletní informace ke konkrétnímu balíku.
	 *
	 * @param type $package_id ID balíku, které předalo API při vložení balíku do systému
	 */
	public static function package($dopravce, $package_id) {
		return self::curl_request($dopravce, 'package/' . $package_id);
	}

	/**
	 * Předání dat do systému přepravce („objednání svozu“) pro dosud neodeslané balíky.
	 * 
	 * @param array $package_ids nepovinný (pro vlastní řešení), výčet ID balíků (package_id – vrácené při metodě ADD), které budou zahrnuty do objednávaného svozu. Pokud je tento parametr prázdný, budou do objednávaného svozu zahrnuty všechny dosud neodeslané balíky. Žádáme vývojáře e-shopových řešení, aby nám tuto informaci zasílali povinně.
	 * @param string $note nepovinný, poznámka pro dopravce (jen pro PPL)
	 * @param string $date nepovinný, datum přijetí dopravce ke svozu (jen pro PPL) – pokud není vyplněno, vyplní se datum aktuálního dne.
	 */
	public static function order($dopravce, $package_ids, $note = NULL, $date = NULL) {
		$aData = array();
		$aData['package_ids'] = $package_ids;
		if ($note) {
			$aData['note'] = $note;
		}
		if ($date) {
			$aData['date'] = $date;
		}
		return self::curl_request($dopravce, 'order', $aData);
	}

	/**
	 * Informace k poslednímu/konkrétnímu svozu – file_url, handover_url, labels_url, order_id.
	 */
	public static function orderview($dopravce, $order_id = NULL) {
		return self::curl_request($dopravce, 'orderview' . ($order_id ? '/' . $order_id : ''));
	}

	/**
	 * Vrací seznam služeb, které se dají použít u daného dopravce.
	 */
	public static function services($dopravce) {
		return self::curl_request($dopravce, 'services');
	}

	/**
	 * Vrací seznam názvů a id (hodnot pro atribut mu_type) možných manipulačních jednotek pro paletovou přepravu (Geis Cargo).
	 */
	public static function manipulationunits($dopravce) {
		return self::curl_request($dopravce, 'manipulationunits');
	}

	/**
	 * Vrací seznam poboček, na které se dají posílat zásilky u konkrétní služby.
	 * Čísla poboček se poté dají předat do atributu branch_id v metodě ADD.
	 * U dopravce Zásilkovna se zde předávají i čísla dalších služeb (např. Česká pošta nebo Expresní doručení Ostrava), které se předávají také do atributu branch_id.
	 */
	public static function branches($dopravce, $service_type = NULL) {
		return self::curl_request($dopravce, 'branches' . ($service_type ? '/' . $service_type : ''));
	}

	/**
	 * Obdoba metody BRANCHES s tím, že se pro každou pobočku vrací více informací.
	 * Momentálně dostupná jen pro přepravce Zásilkovna.
	 */
	public static function fullbranches($dopravce, $service_type = NULL) {
		return self::curl_request($dopravce, 'fullbranches' . ($service_type ? '/' . $service_type : ''));
	}

	/**
	 * Seznam států, do kterých lze zasílat skrze jednotlivé služby přepravce.
	 */
	public static function countries4service($dopravce) {
		return self::curl_request($dopravce, 'countries4service');
	}

	/**
	 * Vrací výčet PSČ, na které se dají posílat zásilky u konkrétní služby.
	 * Tato PSČ jsou platná pro atribut rec_zip v metodě ADD.
	 */
	public static function zipcodes($dopravce, $service_type = NULL, $country = NULL) {
		return self::curl_request($dopravce, 'zipcodes' . ($service_type ? '/' . $service_type : '') . ($country ? '/' . $country : ''));
	}

}
