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
namespace RunOpenCode\ExchangeRate\NationalBankOfSerbia\Exception;

use RunOpenCode\ExchangeRate\NationalBankOfSerbia\Contract\ExceptionInterface;

/**
 * Class RuntimeException
 *
 * @package RunOpenCode\ExchangeRate\NationalBankOfSerbia\Exception
 */
class RuntimeException extends \RunOpenCode\ExchangeRate\Exception\RuntimeException implements ExceptionInterface
{

}
