<?php

/**
 * Výchozí přenosová vrstva.
 *
 * @author http://www.janpotuznik.cz/
 * @link https://github.com/jan-potuznik/balikobot/
 */
final class BalikobotCurlClient implements BalikobotHttpClient {

    /** @var int sekundy na navázání spojení */
    private const CONNECT_TIMEOUT = 10;

    /** @var int sekundy na celý požadavek, add s více štítky může trvat */
    private const TIMEOUT = 60;

    /**
     * @param non-empty-string $url
     * @param list<string> $headers
     * @return array{body: string, httpStatus: int, error: string}
     */
    public function post(string $url, ?string $jsonBody, array $headers): array {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
        if ($jsonBody !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $curlResponse = curl_exec($ch);
        $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = ($curlResponse === false ? curl_error($ch) : '');

        return [
            'body' => ($curlResponse === false ? '' : (string)$curlResponse),
            'httpStatus' => $httpStatus,
            'error' => $error,
        ];
    }

}
