<?php

namespace RunOpenCode\ExchangeRate\NationalBankOfSerbia;

final class Api
{
    const NAME = 'national_bank_of_serbia';

    private static $supports = array(
        'default' => array('EUR', 'AUD', 'CAD', 'CNY', 'HRK', 'CZK', 'DKK', 'HUF', 'JPY', 'KWD', 'NOK', 'RUB', 'SEK', 'CHF',
                           'GBP', 'USD', 'BAM', 'PLN', 'ATS', 'BEF', 'FIM', 'FRF', 'DEM', 'GRD', 'IEP', 'ITL', 'LUF', 'PTE',
                           'ESP'),
        'foreign_cache_buying' => array('EUR', 'CHF', 'USD'),
        'foreign_cache_selling' => array('EUR', 'CHF', 'USD'),
        'foreign_exchange_buying' => array('EUR', 'AUD', 'CAD', 'CNY', 'DKK', 'JPY', 'NOK', 'RUB', 'SEK', 'CHF', 'GBP', 'USD'),
        'foreign_exchange_selling' => array('EUR', 'AUD', 'CAD', 'CNY', 'DKK', 'JPY', 'NOK', 'RUB', 'SEK', 'CHF', 'GBP', 'USD')
    );

    private function __construct() { }

    public static function supports($currencyCode, $rateType)
    {
        if (
            !array_key_exists($rateType, self::$supports)
            ||
            !in_array($currencyCode, self::$supports[$rateType], true)
        ) {
            return false;
        }

        return true;
    }
}
