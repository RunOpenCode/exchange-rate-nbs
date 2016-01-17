<?php
/*
 * This file is part of the Exchange Rate package, an RunOpenCode project.
 *
 * Implementation of exchange rate crawler for National Bank of Serbia, http://www.nbs.rs.
 *
 * (c) 2016 RunOpenCode
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace RunOpenCode\ExchangeRate\NationalBankOfSerbia\Tests\Util;

use GuzzleHttp\Psr7\Stream;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Parser\XmlParser;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Util\NbsBrowser;

class NbsBrowserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function fetchMedian()
    {
        $this->assertXmlDocumentIsValid('default');
    }

    /**
     * @test
     */
    public function fetchForeignCash()
    {
        $this->assertXmlDocumentIsValid('foreign_cash_buying');
    }

    /**
     * @test
     */
    public function fetchForeignExchange()
    {
        $this->assertXmlDocumentIsValid('foreign_exchange_buying');
    }

    /**
     * Validates response from server from National Bank of Serbia.
     *
     * <b>NOTE TO READER:</b> This is not proper test against returned response. Proper test would check response against
     * XMLSchema, however, IT department of National Bank of Serbia apparently does not think that it is convenient to deliver
     * XMLSchema with XML document. I wonder if they have it at all...
     *
     * @param string $rateType
     */
    protected function assertXmlDocumentIsValid($rateType)
    {
        $browser = new NbsBrowser();
        $xmlContent = $browser->getXmlDocument(new \DateTime('now'), $rateType)->getContents();
        $parser = new XmlParser();

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $xmlContent);
        rewind($stream);

        $parser->parse(new Stream($stream), \Closure::bind(function($rates){
            $this->assertTrue(count($rates) > 0);
        }, $this));


        switch ($rateType) {
            case 'default':
                $this->assertContains('OFFICIAL MIDDLE EXCHANGE RATE OF THE DINAR', $xmlContent, 'Should be XML with median exchange rates.');
                break;
            case 'foreign_cash_buying':
                $this->assertContains('FOREIGN CASH', $xmlContent, 'Should be XML with foreign cash rates.');
                break;
            case 'foreign_exchange_buying':
                $this->assertContains('FOREIGN EXCHANGE', $xmlContent, 'Should be XML with foreign exchange rates.');
                break;
        }
    }
}
