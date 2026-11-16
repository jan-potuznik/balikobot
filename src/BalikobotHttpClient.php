<?php

/**
 * Přenosová vrstva pro komunikaci s API. Vlastní implementací se dá curl nahradit
 * třeba Guzzlem nebo v testech dvojníkem, který nic neposílá.
 *
 * @author http://www.janpotuznik.cz/
 * @link https://github.com/jan-potuznik/balikobot/
 */
interface BalikobotHttpClient {

    /**
     * Odešle POST požadavek. Síťovou chybu nevyhazuje, předá ji v položce `error`.
     *
     * @param string|null $jsonBody tělo požadavku, null když se nic neposílá
     * @param string[] $headers hlavičky ve formátu `Název: hodnota`
     * @return array{body: string, httpStatus: int, error: string} `body` je prázdný řetězec
     *     když požadavek neproběhl, `httpStatus` 0 když se nepodařilo navázat spojení
     *     a `error` nese text síťové chyby, jinak prázdný řetězec
     */
    public function post(string $url, ?string $jsonBody, array $headers): array;

}
