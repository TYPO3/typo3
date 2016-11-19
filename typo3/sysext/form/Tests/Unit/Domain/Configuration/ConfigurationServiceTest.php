<?php
namespace TYPO3\CMS\Form\Tests\Unit\Domain\Configuration;

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
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PrototypeNotFoundException;

/**
 * Test case
 */
class ConfigurationServiceTest extends UnitTestCase
{

    /**
     * @test
     */
    public function getPrototypeConfigurationReturnsPrototypeConfiguration()
    {
        $mockConfigurationService = $this->getAccessibleMock(ConfigurationService::class, [
            'dummy'
        ], [], '', false);

        $mockConfigurationService->_set('formSettings', [
            'prototypes' => [
                'standard' => [
                    'key' => 'value',
                ],
            ],
        ]);

        $expected = [
            'key' => 'value',
        ];

        $this->assertSame($expected, $mockConfigurationService->getPrototypeConfiguration('standard'));
    }

    /**
     * @test
     */
    public function getPrototypeConfigurationThrowsExceptionIfNoPrototypeFound()
    {
        $mockConfigurationService = $this->getAccessibleMock(ConfigurationService::class, [
            'dummy'
        ], [], '', false);

        $this->expectException(PrototypeNotFoundException::class);
        $this->expectExceptionCode(1475924277);

        $mockConfigurationService->_set('formSettings', [
            'prototypes' => [
                'noStandard' => [],
                ],
            ]);

        $mockConfigurationService->getPrototypeConfiguration('standard');
    }
}
