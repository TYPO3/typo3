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

namespace TYPO3\CMS\Core\Tests\Unit\Log\Processor;

use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Processor\WebProcessor;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class WebProcessorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function webProcessorAddsWebDataToLogRecord()
    {
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_URI'] = '';
        $_SERVER['ORIG_SCRIPT_NAME'] = '';
        $_SERVER['REMOTE_ADDR'] = '';
        $_SERVER['QUERY_STRING'] = '';
        $_SERVER['SSL_SESSION_ID'] = '';

        $environmentVariables = GeneralUtility::getIndpEnv('_ARRAY');
        $logRecord = new LogRecord('test.core.log', LogLevel::DEBUG, 'test');
        $processor = new WebProcessor();
        $logRecord = $processor->processLogRecord($logRecord);
        foreach ($environmentVariables as $key => $value) {
            self::assertEquals($value, $logRecord['data'][$key]);
        }
    }
}
