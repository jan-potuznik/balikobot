# balikobot
Jednoduchá PHP třída, která vám usnadní nasazení služby Balíkobot do vašeho e-shopu.

K datu zveřejnění obsahuje všechny podporované metody.

Případné zájemce seznámím i s třídou, která se stará o přípravu požadavků a zpracování odpovědí.
Vzhledem k tomu, že pracuje se specifickými struktarami e-shopu, není vhodné ji zveřejňovat.

Kompatibilita s PHP `>=8.2 <=8.5`.

## Vlastní HTTP vrstva

Ve výchozím stavu se o komunikaci stará curl. Čtvrtým parametrem konstruktoru lze podstrčit
vlastní implementaci `BalikobotHttpClient` (třeba nad Guzzlem):

```php
$balikobot = new Balikobot($user, $apiKey, null, new MujHttpClient());
```

## Testy

```sh
composer install
composer phpunit
composer phpunit:coverage
```

Přijímám návrhy na zlepšení - zakládejte issues a pull requesty.
