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

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Processor\WebProcessor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class WebProcessorTest extends UnitTestCase
{
    #[Test]
    public function webProcessorAddsWebDataToLogRecord(): void
    {
        $normalizedParams = NormalizedParams::createFromServerParams([
            'HTTP_HOST' => 'acme.com',
            'REQUEST_URI' => '/index.php?id=42',
            'SCRIPT_NAME' => '/index.php',
            'REMOTE_ADDR' => '127.0.0.1',
            'QUERY_STRING' => 'id=42',
        ]);
        $request = new ServerRequest('https://acme.com/index.php?id=42')
            ->withAttribute('normalizedParams', $normalizedParams)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $logRecord = new LogRecord('test.core.log', LogLevel::DEBUG, 'test');
        $processor = new WebProcessor();
        $logRecord = $processor->processLogRecord($logRecord);

        self::assertSame('acme.com', $logRecord['data']['HTTP_HOST']);
        self::assertSame('127.0.0.1', $logRecord['data']['REMOTE_ADDR']);
        self::assertSame('/index.php?id=42', $logRecord['data']['REQUEST_URI']);
        self::assertSame('id=42', $logRecord['data']['QUERY_STRING']);
    }

    #[Test]
    public function webProcessorAddsNoDataWithoutRequest(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);

        $logRecord = new LogRecord('test.core.log', LogLevel::DEBUG, 'test');
        $processor = new WebProcessor();
        $logRecord = $processor->processLogRecord($logRecord);

        self::assertSame([], $logRecord['data']);
    }
}
