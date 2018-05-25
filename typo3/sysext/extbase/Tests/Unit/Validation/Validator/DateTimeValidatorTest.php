<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the Extbase framework.                          *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Extbase\Validation\Validator\DateTimeValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DateTimeValidatorTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider dateTimeValues
     */
    public function acceptsDateTimeValues($value)
    {
        $validator = new DateTimeValidator();
        $result = $validator->validate($value);

        $this->assertFalse($result->hasErrors());
    }

    /**
     * @return array
     */
    public function dateTimeValues(): array
    {
        return [
            \DateTime::class => [
                new \DateTime(),
            ],
            'Extended ' . \DateTime::class => [
                new class extends \DateTime {
                },
            ],
            \DateTimeImmutable::class => [
                new \DateTimeImmutable(),
            ],
            'Extended ' . \DateTimeImmutable::class => [
                new class extends \DateTimeImmutable {
                },
            ],
        ];
    }

    /**
     * @test
     */
    public function addsErrorForInvalidValue()
    {
        $validator = $this->getMockBuilder(DateTimeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->getMock();
        $result = $validator->validate(false);

        $this->assertTrue($result->hasErrors());
    }
}
