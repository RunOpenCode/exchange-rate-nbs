<?php
/*
 * This file is part of the Exchange Rate package, an RunOpenCode project.
 *
 * Implementation of exchange rate crawler for National Bank of Serbia, http://www.nbs.rs.
 *
 * (c) 2017 RunOpenCode
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace RunOpenCode\ExchangeRate\NationalBankOfSerbia\Tests\Parser;

use PHPUnit\Framework\TestCase;
use RunOpenCode\ExchangeRate\Contract\RateInterface;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Parser\XmlParser;
use RunOpenCode\Sax\SaxParser;

class XmlParserTest extends TestCase
{
    /**
     * @test
     */
    public function median()
    {
        $this->assertParsing(__DIR__ . '/../Fixtures/median.xml', __DIR__ . '/../Fixtures/median_out.php');
    }

    /**
     * @test
     */
    public function foreignCash()
    {
        $this->assertParsing(__DIR__ . '/../Fixtures/foreign_cash.xml', __DIR__ . '/../Fixtures/foreign_cash_out.php');
    }

    /**
     * @test
     */
    public function foreignExchange()
    {
        $this->assertParsing(__DIR__ . '/../Fixtures/foreign_exchange.xml', __DIR__ . '/../Fixtures/foreign_exchange_out.php');
    }

    /**
     * @test
     *
     * @expectedException \RunOpenCode\ExchangeRate\NationalBankOfSerbia\Exception\RuntimeException
     * @expectedExceptionMessage Unable to parse XML source from National Bank of Serbia, reason: "mismatched tag", lineno: "1".
     */
    public function parseError()
    {
        $resource = fopen('php://memory', 'r+b');
        fwrite($resource, '<root></test></root>');
        rewind($resource);

        SaxParser::factory()->parse(new XmlParser(), $resource);
    }

    protected function assertParsing($pathToXmlInput, $pathToPhpOutput, $message = 'Should provide given rates.')
    {
        $rates = SaxParser::factory()->parse(new XmlParser(), fopen($pathToXmlInput, 'rb'));

        $this->assertSame(require $pathToPhpOutput, call_user_func(function($rates) {
            $flatten = array();
            /**
             * @var RateInterface $rate
             */
            foreach ($rates as $rate) {
                $flatten[] = array(
                    'currencyCode' => $rate->getCurrencyCode(),
                    'rateType' => $rate->getRateType(),
                    'value' => (string)$rate->getValue(),
                    'date' => $rate->getDate()->format('Y-m-d')
                );
            }

            return $flatten;
        }, $rates), $message);
    }
}
