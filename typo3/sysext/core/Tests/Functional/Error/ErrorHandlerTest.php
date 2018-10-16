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
        'SYS' => [
            'exceptionalErrors' => E_ALL & ~(E_STRICT | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR | E_DEPRECATED | E_USER_DEPRECATED | E_WARNING | E_USER_ERROR | E_USER_NOTICE | E_USER_WARNING),
        ],
    ];

    /**
     * Disabled on mssql: They don't support init command "SET NAMES 'UTF8'". That's
     * ok since this test is not about db platform support but error handling in core.
     *
     * @test
     * @group not-mssql
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
