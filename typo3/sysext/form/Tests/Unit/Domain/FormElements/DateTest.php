<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\FormElements;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\Date;
use TYPO3\CMS\Form\Mvc\ProcessingRule;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DateTest extends UnitTestCase
{
    #[Test]
    public function initializeFormElementConfiguresDateConversionForCustomElementType(): void
    {
        $propertyMappingConfiguration = $this->createMock(PropertyMappingConfiguration::class);
        $propertyMappingConfiguration
            ->expects($this->once())
            ->method('setTypeConverterOption')
            ->with(DateTimeConverter::class, DateTimeConverter::CONFIGURATION_DATE_FORMAT, 'Y-m-d');

        $processingRule = $this->createMock(ProcessingRule::class);
        $processingRule
            ->expects($this->once())
            ->method('setDataType')
            ->with(\DateTime::class);
        $processingRule
            ->expects($this->once())
            ->method('getPropertyMappingConfiguration')
            ->willReturn($propertyMappingConfiguration);

        $rootForm = $this->createMock(FormDefinition::class);
        $rootForm
            ->expects($this->exactly(2))
            ->method('getProcessingRule')
            ->with('birthDate')
            ->willReturn($processingRule);

        $subject = $this->getMockBuilder(Date::class)
            ->onlyMethods(['getRootForm'])
            ->setConstructorArgs(['birthDate', 'BirthDate'])
            ->getMock();
        $subject
            ->expects($this->exactly(2))
            ->method('getRootForm')
            ->willReturn($rootForm);

        self::assertSame('BirthDate', $subject->getType());
        $subject->initializeFormElement();
    }
}
