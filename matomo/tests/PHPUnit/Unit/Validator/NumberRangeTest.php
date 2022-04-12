<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Unit\Translation\Loader;

use Piwik\Validators\NumberRange;

/**
 * @group Validator
 * @group NumberRange
 * @group NumberRangeTest
 */
class NumberRangeTest extends \PHPUnit\Framework\TestCase
{
    public function test_validate_successValueNotEmpty()
    {
        $this->validate('5', '4', '5');
        $this->validate('5', '5', '5');
        $this->validate('5', '5', '7');
        $this->validate('5', '4', '6');
        $this->validate(5, 4, '6');
        $this->validate(5.43, 5.30, 5.50);
        $this->validate('5');
        $this->validate('5', 4);
        $this->validate('5', null, '6');
        $this->validate('-5', -10, '-4');

        $this->assertTrue(true);
    }

    public function test_validate_failValueIsTooLow()
    {
        $this->expectException(\Piwik\Validators\Exception::class);
        $this->expectExceptionMessage('General_ValidatorErrorNumberTooLow');
        $this->validate(3, 5);
    }

    public function test_validate_failValueIsTooHigh()
    {
        $this->expectException(\Piwik\Validators\Exception::class);
        $this->expectExceptionMessage('General_ValidatorErrorNumberTooHigh');
        $this->validate(10, null, 8);
    }

    public function test_validate_failValueIsTooNotInRange()
    {
        $this->expectException(\Piwik\Validators\Exception::class);
        $this->expectExceptionMessage('General_ValidatorErrorNumberTooHigh');
        $this->validate(10, 5, 8);
    }

    public function test_validate_failValueIsTooNotInRangeFloat()
    {
        $this->expectException(\Piwik\Validators\Exception::class);
        $this->expectExceptionMessage('General_ValidatorErrorNumberTooLow');
        $this->validate(5.43, 5.44, 8);
    }

    public function test_validate_failValueIsNotNumber()
    {
        $this->expectException(\Piwik\Validators\Exception::class);
        $this->expectExceptionMessage('General_ValidatorErrorNotANumber');
        $this->validate('foo');
    }

    private function validate($value, $min = null, $max = null)
    {
        $validator = new NumberRange($min, $max);
        $validator->validate($value);
    }
}
