<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Extbase\Tests\Functional\Property\TypeConverter;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BooleanConverterTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function convertToBoolean()
    {
        $propertyMapper = GeneralUtility::getContainer()->get(PropertyMapper::class);

        self::assertTrue($propertyMapper->convert(true, 'boolean'));
        self::assertFalse($propertyMapper->convert(false, 'boolean'));

        self::assertTrue($propertyMapper->convert('true', 'boolean'));
        self::assertTrue($propertyMapper->convert('false', 'boolean'));
        self::assertTrue($propertyMapper->convert('1', 'boolean'));
        self::assertFalse($propertyMapper->convert('0', 'boolean'));
        self::assertTrue($propertyMapper->convert('string', 'boolean'));
        self::assertFalse($propertyMapper->convert('', 'boolean'));
    }
}
