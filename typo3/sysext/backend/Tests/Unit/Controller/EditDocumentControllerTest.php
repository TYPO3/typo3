<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Controller;

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

use TYPO3\CMS\Backend\Controller\EditDocumentController;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Tests for EditDocumentController
 */
class EditDocumentControllerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function parseAdditionalGetParametersCreatesCorrectParameterArray()
    {
        $typoScript = [
            'tx_myext.' => [
                'controller' => 'test',
                'action' => 'run'
            ],
            'magic' => 'yes'
        ];
        $expectedParameters = [
            'tx_myext' => [
                'controller' => 'test',
                'action' => 'run'
            ],
            'magic' => 'yes'
        ];
        $result = [];
        $mock = $this->getAccessibleMock(EditDocumentController::class, ['dummy'], [], '', false);
        $mock->_callRef('parseAdditionalGetParameters', $result, $typoScript);
        $this->assertSame($expectedParameters, $result);
    }
}
