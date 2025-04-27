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

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\Fixtures\Log\DummyWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestLogger\ConstructorAttributeChannelTester;
use TYPO3Tests\TestLogger\ConstructorClassAttributeChannelTester;
use TYPO3Tests\TestLogger\ConstructorWithoutAttributeTester;
use TYPO3Tests\TestLogger\GrandParentInjectMethodClassAttributeChannelTester;
use TYPO3Tests\TestLogger\GrandParentInjectMethodNoClassAttributeTester;
use TYPO3Tests\TestLogger\InjectMethodAttributeChannelTester;
use TYPO3Tests\TestLogger\InjectMethodClassAttributeChannelTester;
use TYPO3Tests\TestLogger\InjectMethodWithoutAttributeChannelTester;
use TYPO3Tests\TestLogger\LoggerAwareClassAttributeChannelTester;
use TYPO3Tests\TestLogger\LoggerAwareClassWithoutAttributeChannelTester;
use TYPO3Tests\TestLogger\ParentInjectMethodAttributeChannelTester;
use TYPO3Tests\TestLogger\ParentInjectMethodClassAttributeChannelTester;
use TYPO3Tests\TestLogger\ParentInjectMethodWithoutAttributeChannelTester;

final class LoggerInterfaceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_logger',
    ];

    protected array $configurationToUseInTestInstance = [
        'LOG' => [
            'writerConfiguration' => [
                LogLevel::DEBUG => [
                    DummyWriter::class => [],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        DummyWriter::$logs = [];
    }

    protected function tearDown(): void
    {
        DummyWriter::$logs = [];
        parent::tearDown();
    }

    #[Test]
    public function loggerAwareClassWithClassLevelChannelAttributeIsRead(): void
    {
        $container = $this->getContainer();
        $container->get(LoggerAwareClassAttributeChannelTester::class);

        self::assertCount(1, DummyWriter::$logs);
        self::assertSame('beep beep', DummyWriter::$logs[0]->getMessage());
        self::assertSame('beep', DummyWriter::$logs[0]->getComponent());
    }

    #[Test]
    public function loggerAwareClassWithoutClassLevelChannelAttributeUsesClassname(): void
    {
        $container = $this->getContainer();
        $container->get(LoggerAwareClassWithoutAttributeChannelTester::class);

        self::assertCount(1, DummyWriter::$logs);
        self::assertSame('beep beep', DummyWriter::$logs[0]->getMessage());
        self::assertSame($this->normalizeClassNamespace(LoggerAwareClassWithoutAttributeChannelTester::class), DummyWriter::$logs[0]->getComponent());
    }

    #[Test]
    public function constructorWithoutChannelAttributeUsesClassName(): void
    {
        $container = $this->getContainer();
        $container->get(ConstructorWithoutAttributeTester::class);

        self::assertCount(1, DummyWriter::$logs);
        self::assertSame('beep beep', DummyWriter::$logs[0]->getMessage());
        self::assertSame($this->normalizeClassNamespace(ConstructorWithoutAttributeTester::class), DummyWriter::$logs[0]->getComponent());
    }

    #[Test]
    public function constructorChannelAttributeIsRead(): void
    {
        $container = $this->getContainer();
        $container->get(ConstructorAttributeChannelTester::class);

        self::assertCount(1, DummyWriter::$logs);
        self::assertSame('beep beep', DummyWriter::$logs[0]->getMessage());
        self::assertSame('beep', DummyWriter::$logs[0]->getComponent());
    }

    #[Test]
    public function constructorClassChannelAttributeIsRead(): void
    {
        $container = $this->getContainer();
        $container->get(ConstructorClassAttributeChannelTester::class);

        self::assertCount(1, DummyWriter::$logs);
        self::assertSame('beep beep', DummyWriter::$logs[0]->getMessage());
        self::assertSame('beep', DummyWriter::$logs[0]->getComponent());
    }

    #[Test]
    public function injectMethodChannelAttributeIsRead(): void
    {
        $container = $this->getContainer();
        $container->get(InjectMethodAttributeChannelTester::class);

        self::assertCount(1, DummyWriter::$logs);
        self::assertSame('beep beep', DummyWriter::$logs[0]->getMessage());
        self::assertSame('beep', DummyWriter::$logs[0]->getComponent());
    }

    #[Test]
    public function injectMethodClassChannelAttributeIsRead(): void
    {
        $container = $this->getContainer();
        $container->get(InjectMethodClassAttributeChannelTester::class);

        self::assertCount(1, DummyWriter::$logs);
        self::assertSame('beep beep', DummyWriter::$logs[0]->getMessage());
        self::assertSame('beep', DummyWriter::$logs[0]->getComponent());
    }

    #[Test]
    public function injectMethodWithoutChannelAttributeUsesClassName(): void
    {
        $container = $this->getContainer();
        $container->get(InjectMethodWithoutAttributeChannelTester::class);

        self::assertCount(1, DummyWriter::$logs);
        self::assertSame('beep beep', DummyWriter::$logs[0]->getMessage());
        self::assertSame($this->normalizeClassNamespace(InjectMethodWithoutAttributeChannelTester::class), DummyWriter::$logs[0]->getComponent());
    }

    #[Test]
    public function parentInjectMethodChannelAttributeIsRead(): void
    {
        $container = $this->getContainer();
        $container->get(ParentInjectMethodAttributeChannelTester::class);

        self::assertCount(1, DummyWriter::$logs);
        self::assertSame('beep beep', DummyWriter::$logs[0]->getMessage());
        self::assertSame('parent-beep', DummyWriter::$logs[0]->getComponent());
    }

    #[Test]
    public function parentInjectMethodClassChannelAttributeIsRead(): void
    {
        $container = $this->getContainer();
        $container->get(ParentInjectMethodClassAttributeChannelTester::class);

        self::assertCount(1, DummyWriter::$logs);
        self::assertSame('beep beep', DummyWriter::$logs[0]->getMessage());
        self::assertSame('parent-beep', DummyWriter::$logs[0]->getComponent());
    }

    #[Test]
    public function grandParentInjectMethodClassChannelAttributeIsRead(): void
    {
        $container = $this->getContainer();
        $container->get(GrandParentInjectMethodClassAttributeChannelTester::class);

        self::assertCount(1, DummyWriter::$logs);
        self::assertSame('beep beep', DummyWriter::$logs[0]->getMessage());
        self::assertSame('grand-parent-beep', DummyWriter::$logs[0]->getComponent());
    }

    #[Test]
    public function GrandParentInjectMethodNoClassAttributeTesterChildAttributeIsRead(): void
    {
        $container = $this->getContainer();
        $container->get(GrandParentInjectMethodNoClassAttributeTester::class);

        self::assertCount(1, DummyWriter::$logs);
        self::assertSame('beep beep', DummyWriter::$logs[0]->getMessage());
        self::assertSame('parent-beep', DummyWriter::$logs[0]->getComponent());
    }

    #[Test]
    public function parentInjectMethodWithoutChannelAttributeUsesClassName(): void
    {
        $container = $this->getContainer();
        $container->get(ParentInjectMethodWithoutAttributeChannelTester::class);

        self::assertCount(1, DummyWriter::$logs);
        self::assertSame('beep beep', DummyWriter::$logs[0]->getMessage());
        self::assertSame($this->normalizeClassNamespace(ParentInjectMethodWithoutAttributeChannelTester::class), DummyWriter::$logs[0]->getComponent());
    }

    /**
     * @param non-empty-string $className
     * @return non-empty-string
     */
    private function normalizeClassNamespace(string $className): string
    {
        return str_replace(['\\'], ['.'], $className);
    }
}
