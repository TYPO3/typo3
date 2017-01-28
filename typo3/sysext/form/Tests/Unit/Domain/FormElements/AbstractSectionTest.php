<?php
namespace TYPO3\CMS\Form\Tests\Unit\Domain\FormElements;

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

use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotFoundException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractSection;

/**
 * Test case
 */
class AbstractSectionTest extends \TYPO3\Components\TestingFramework\Core\UnitTestCase
{

    /**
     * @test
     */
    public function createElementThrowsExceptionIfTypeDefinitionNotFound()
    {
        $mockAbstractSection = $this->getAccessibleMockForAbstractClass(AbstractSection::class,
            [], '', false, true, true, [
                'getRootForm',
            ]
        );

        $mockFormDefinition = $this->getAccessibleMock(FormDefinition::class, [
            'getTypeDefinitions',
            'getRenderingOptions'
        ], [], '', false);

        $mockFormDefinition
            ->expects($this->any())
            ->method('getTypeDefinitions')
            ->willReturn([]);

        $mockFormDefinition
            ->expects($this->any())
            ->method('getRenderingOptions')
            ->willReturn(['skipUnknownElements' => false]);

        $mockAbstractSection
            ->expects($this->any())
            ->method('getRootForm')
            ->willReturn($mockFormDefinition);

        $this->expectException(TypeDefinitionNotFoundException::class);
        $this->expectExceptionCode(1382364019);

        $mockAbstractSection->_call('createElement', '', '');
    }
}
