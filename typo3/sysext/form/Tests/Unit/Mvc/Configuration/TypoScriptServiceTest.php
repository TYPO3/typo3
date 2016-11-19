<?php
namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Configuration;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Form\Mvc\Configuration\TypoScriptService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Test case
 */
class TypoScriptServiceTest extends UnitTestCase
{
    /**
     * @test
     */
    public function resolveTypoScriptConfigurationReturnsResolvedConfiguration()
    {
        $mockTypoScriptService = $this->getAccessibleMock(TypoScriptService::class, [
            'getTypoScriptFrontendController'
        ], [], '', false);

        $mockContentObjectRenderer = $this->getMockBuilder(
            ContentObjectRenderer::class
        )->getMock();

        $fakeTypoScriptFrontendController = new \stdClass;
        $fakeTypoScriptFrontendController->cObj = $mockContentObjectRenderer;

        $mockContentObjectRenderer
            ->expects($this->any())
            ->method('cObjGetSingle')
            ->with('TEXT', ['value' => 'rambo'])
            ->will($this->returnValue('rambo'));

        $mockTypoScriptService
            ->expects($this->any())
            ->method('getTypoScriptFrontendController')
            ->willReturn($fakeTypoScriptFrontendController);

        $input = [
            'key.' => [
                'john' => 'TEXT',
                'john.' => [
                    'value' => 'rambo'
                ],
            ],
        ];
        $expected = [
            'key' => [
                'john' => 'rambo',
            ],
        ];

        $this->assertSame($expected, $mockTypoScriptService->_call('resolveTypoScriptConfiguration', $input));
    }
}
