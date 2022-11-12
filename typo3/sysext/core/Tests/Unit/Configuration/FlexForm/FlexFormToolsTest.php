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

namespace TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm;

use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Tests\Unit\Fixtures\EventDispatcher\MockEventDispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FlexFormToolsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function traverseFlexFormXmlDataRecurseDoesNotFailOnNotExistingField(): void
    {
        $dataStruct = [
            'dummy_field' => [
                'config' => [],
            ],
        ];
        $pA = [
            'vKeys' => ['ES'],
            'callBackMethod_value' => 'dummy',
        ];
        $editData = [];
        $subject = $this->getMockBuilder(FlexFormTools::class)
            ->setConstructorArgs([new MockEventDispatcher()])
            ->onlyMethods(['executeCallBackMethod'])
            ->getMock();
        $subject->expects(self::never())->method('executeCallBackMethod');
        $subject->traverseFlexFormXMLData_recurse($dataStruct, $editData, $pA);
    }

    /**
     * @test
     */
    public function traverseFlexFormXmlDataRecurseDoesNotFailOnNotExistingArrayField(): void
    {
        $dataStruct = [
            'dummy_field' => [
                'type' => 'array',
                'el' => 'field_not_in_data',
            ],
        ];
        $pA = [
            'vKeys' => ['ES'],
            'callBackMethod_value' => 'dummy',
        ];
        $editData = [
            'field' => [
                'el' => 'dummy',
            ],
        ];
        $editData2 = [];
        $flexFormTools = new FlexFormTools(new NoopEventDispatcher());
        $flexFormTools->traverseFlexFormXMLData_recurse($dataStruct, $editData, $pA);
        $flexFormTools->traverseFlexFormXMLData_recurse($dataStruct, $editData2, $pA);
    }
}
