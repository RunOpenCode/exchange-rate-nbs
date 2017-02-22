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
namespace RunOpenCode\ExchangeRate\NationalBankOfSerbia\Parser;

use RunOpenCode\ExchangeRate\Contract\RateInterface;
use RunOpenCode\ExchangeRate\Model\Rate;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Api;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Enum\RateType;
use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Exception\RuntimeException;
use RunOpenCode\Sax\Handler\AbstractSaxHandler;

/**
 * Class XmlParser
 *
 * Parse XML document with daily rates from National Bank of Serbia.
 *
 * @package RunOpenCode\ExchangeRate\NationalBankOfSerbia\Parser
 */
class XmlParser extends AbstractSaxHandler
{
    /**
     * @var RateInterface[]
     */
    private $rates;

    /**
     * @var \SplStack
     */
    private $stack;

    /**
     * @var array
     */
    private $currentRate;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var string
     */
    private $rateType;

    /**
     * {@inheritdoc}
     */
    protected function onDocumentStart($parser, $stream)
    {
        $this->rates = array();
        $this->stack = new \SplStack();
        $this->currentRate = array();
        $this->date = new \DateTime('now');
        $this->rateType = RateType::MEDIAN;
    }

    /**
     * {@inheritdoc}
     */
    protected function onElementStart($parser, $name, $attributes)
    {
        $this->stack->push($name);

        if ($name === 'ITEM') {
            $this->currentRate = array();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function onElementData($parser, $data)
    {
        if (!empty($data)) {

            switch ($this->stack->top()) {
                case 'DATE':
                    $this->date = \DateTime::createFromFormat('d.m.Y', $data);
                    break;
                case 'TYPE':
                    $data = trim($data);
                    if ($data === 'FOREIGN EXCHANGE') {
                        $this->rateType = 'foreign_exchange';
                    } elseif ($data === 'FOREIGN CASH') {
                        $this->rateType = 'foreign_cash';
                    }
                    break;
                case 'CURRENCY':
                    $this->currentRate['currencyCode'] = trim($data);
                    break;
                case 'UNIT':
                    $this->currentRate['unit'] = (int) trim($data);
                    break;
                case 'BUYING_RATE':
                    $this->currentRate['buyingRate'] = (float) trim($data);
                    break;
                case 'SELLING_RATE':
                    $this->currentRate['sellingRate'] = (float) trim($data);
                    break;
                case 'MIDDLE_RATE':
                    $this->currentRate['middleRate'] = (float) trim($data);
                    break;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function onElementEnd($parser, $name)
    {
        $this->stack->pop();

        $buildRate = function($value, $currencyCode, $rateType, $date) {

            return new Rate(
                Api::NAME,
                $value,
                $currencyCode,
                $rateType,
                $date,
                'RSD',
                new \DateTime('now'),
                new \DateTime('now')
            );
        };

        if ($name === 'ITEM') {

            if (array_key_exists('buyingRate', $this->currentRate)) {

                $this->rates[] = $buildRate(
                    $this->currentRate['buyingRate'] / $this->currentRate['unit'],
                    $this->currentRate['currencyCode'],
                    $this->rateType . '_buying',
                    $this->date
                );
            }

            if (array_key_exists('sellingRate', $this->currentRate)) {

                $this->rates[] = $buildRate(
                    $this->currentRate['sellingRate'] / $this->currentRate['unit'],
                    $this->currentRate['currencyCode'],
                    $this->rateType . '_selling',
                    $this->date
                );
            }

            if (array_key_exists('middleRate', $this->currentRate)) {

                $this->rates[] = $buildRate(
                    $this->currentRate['middleRate'] / $this->currentRate['unit'],
                    $this->currentRate['currencyCode'],
                    RateType::MEDIAN,
                    $this->date
                );
            }

            $this->currentRate = array();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function onDocumentEnd($parser, $stream)
    {
        // noop
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException
     */
    protected function onParseError($message, $code, $lineno)
    {
        throw new RuntimeException(sprintf('Unable to parse XML source from National Bank of Serbia, reason: "%s", lineno: "%s".', $message, $lineno), $code);
    }

    /**
     * {@inheritdoc}
     */
    protected function getResult()
    {
        return $this->rates;
    }
}
