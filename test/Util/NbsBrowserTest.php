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
namespace RunOpenCode\ExchangeRate\NationalBankOfSerbia\Tests\Util;

use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Enum\RateType;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Parser\XmlParser;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Util\NbsBrowser;

class NbsBrowserTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function fetchMedian()
    {
        $this->assertXmlDocumentIsValid(RateType::MEDIAN);
    }

    /**
     * @test
     *
     * @return void
     */
    public function fetchForeignCash()
    {
        $this->assertXmlDocumentIsValid(RateType::FOREIGN_CASH_BUYING);
    }

    /**
     * @test
     *
     * @return void
     */
    public function fetchForeignExchange()
    {
        $this->assertXmlDocumentIsValid(RateType::FOREIGN_EXCHANGE_BUYING);
    }

    /**
     * Validates response from server from National Bank of Serbia.
     *
     * <b>NOTE TO READER:</b> This is not proper test against returned response. Proper test would check response against
     * XMLSchema, however, IT department of National Bank of Serbia apparently does not think that it is convenient to deliver
     * XMLSchema with XML document. I wonder if they have it at all...
     *
     * @param string $rateType
     *
     * @return void
     */
    protected function assertXmlDocumentIsValid($rateType)
    {
        $browser = new NbsBrowser();
        $xmlContent = $browser->getXmlDocument(new \DateTime('now'), $rateType)->getContents();
        $parser = new XmlParser();

        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, $xmlContent);
        rewind($stream);

        $rates = $parser->parse(new Stream($stream));
        $this->assertTrue(count($rates) > 0);

        switch ($rateType) {
            case RateType::MEDIAN:
                $this->assertContains('<Type>srednjiKurs</Type>', $xmlContent, 'Should be XML with type "srednjiKurs".');
                break;
            case RateType::FOREIGN_CASH_BUYING:
                $this->assertContains('<Type>efektiva</Type>', $xmlContent, 'Should be XML with type "efektiva".');
                break;
            case RateType::FOREIGN_EXCHANGE_BUYING:
                $this->assertContains('<Type>devize</Type>', $xmlContent, 'Should be XML with type "devize".');
                break;
        }
    }
}
