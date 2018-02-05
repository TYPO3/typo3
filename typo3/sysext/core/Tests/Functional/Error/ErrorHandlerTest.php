<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Functional\Error;

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

/**
 * Testcase for class \TYPO3\CMS\Core\Error\ErrorHandler
 */
class ErrorHandlerTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    /**
     * @var array
     */
    protected $configurationToUseInTestInstance = [
        'DB' => [
            'Connections' => [
                'Default' => [
                    'initCommands' => 'SET NAMES \'UTF8\';',
                ],
            ],
        ],
    ];

    /**
     * @test
     */
    public function handleErrorFetchesDeprecations()
    {
        trigger_error(
            'The first error triggers database connection to be initialized and should be caught.',
            E_USER_DEPRECATED
        );
        trigger_error(
            'The second error should be caught by ErrorHandler as well.',
            E_USER_DEPRECATED
        );
        $this->assertTrue(true);
    }
}
