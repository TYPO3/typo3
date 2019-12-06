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

class IntegerConverterTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function convertToInteger()
    {
        $propertyMapper = GeneralUtility::getContainer()->get(PropertyMapper::class);

        self::assertSame(10, $propertyMapper->convert(10, 'integer'));

        self::assertSame(10, $propertyMapper->convert('10', 'integer'));
        self::assertSame(10, $propertyMapper->convert('10.5', 'integer'));
        self::assertSame(10000, $propertyMapper->convert('10E3', 'integer'));
        self::assertNull($propertyMapper->convert('string', 'integer'));
        self::assertNull($propertyMapper->convert('', 'integer'));
    }
}
