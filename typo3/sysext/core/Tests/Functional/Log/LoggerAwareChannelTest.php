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

namespace TYPO3\CMS\Core\Tests\Functional\Log;

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Tests\Functional\Fixtures\Log\DummyWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestLogger\ConstructorAttributeChannelTester;
use TYPO3Tests\TestLogger\LoggerAwareClassAttributeChannelTester;

/**
 * @requires PHP >= 8.0
 */
class LoggerAwareChannelTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_logger',
    ];

    protected $configurationToUseInTestInstance = [
        'LOG' => [
            'beep' => [
                'writerConfiguration' => [
                    LogLevel::DEBUG => [
                        DummyWriter::class => [],
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        Bootstrap::initializeLanguageObject();
        DummyWriter::$logs = [];
    }

    protected function tearDown(): void
    {
        DummyWriter::$logs = [];
        parent::tearDown();
    }

    /**
     * @test
     */
    public function classLevelChannelAttributeIsRead(): void
    {
        $container = $this->getContainer();
        $subject = $container->get(LoggerAwareClassAttributeChannelTester::class);

        $subject->run();

        self::assertInstanceOf(LogRecord::class, DummyWriter::$logs[0]);
        self::assertSame('beep beep', DummyWriter::$logs[0]->getMessage());
    }

    /**
     * @test
     */
    public function constructorChannelAttributeIsRead(): void
    {
        $container = $this->getContainer();
        $subject = $container->get(ConstructorAttributeChannelTester::class);

        $subject->run();

        self::assertInstanceOf(LogRecord::class, DummyWriter::$logs[0]);
        self::assertSame('beep beep', DummyWriter::$logs[0]->getMessage());
    }
}
