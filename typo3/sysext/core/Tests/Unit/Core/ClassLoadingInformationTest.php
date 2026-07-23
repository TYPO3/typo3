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

namespace TYPO3\CMS\Core\Tests\Unit\Core;

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ClassLoadingInformationTest extends UnitTestCase
{
    private const EXTENSION_PATH = '/fixture/typo3conf/ext/test_extension/Classes';
    private const LIBRARY_PATH = '/fixture/vendor/typo3/test-library/src';
    private const PREFIX = 'TYPO3\\CMS\\TestExtension\\';
    protected bool $backupEnvironment = true;

    private ?ClassLoader $backedUpClassLoader = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backedUpClassLoader = ClassLoadingInformation::getClassLoader();
    }

    protected function tearDown(): void
    {
        if ($this->backedUpClassLoader !== null) {
            ClassLoadingInformation::setClassLoader($this->backedUpClassLoader);
        }
        parent::tearDown();
    }

    #[Test]
    public function registerClassLoadingInformationKeepsAlreadyRegisteredPsr4Paths(): void
    {
        $classLoader = $this->setUpFixtureInstance();
        $classLoader->addPsr4(self::PREFIX, [self::LIBRARY_PATH]);

        ClassLoadingInformation::registerClassLoadingInformation();

        self::assertSame(
            [self::EXTENSION_PATH, self::LIBRARY_PATH],
            $classLoader->getPrefixesPsr4()[self::PREFIX]
        );
    }

    #[Test]
    public function registerClassLoadingInformationDoesNotAddPsr4PathsTwice(): void
    {
        $classLoader = $this->setUpFixtureInstance();
        $classLoader->addPsr4(self::PREFIX, [self::LIBRARY_PATH]);

        ClassLoadingInformation::registerClassLoadingInformation();
        ClassLoadingInformation::registerClassLoadingInformation();

        self::assertSame(
            [self::EXTENSION_PATH, self::LIBRARY_PATH],
            $classLoader->getPrefixesPsr4()[self::PREFIX]
        );
    }

    /**
     * Points Environment to a fixture instance carrying a dumped autoload_psr4.php and
     * registers a fresh Composer class loader as the one to manipulate.
     */
    private function setUpFixtureInstance(): ClassLoader
    {
        $instancePath = __DIR__ . '/Fixtures/class_loading_information';
        Environment::initialize(
            new ApplicationContext('Testing'),
            true,
            false,
            $instancePath,
            $instancePath,
            $instancePath . '/typo3temp/var',
            $instancePath . '/typo3conf',
            '',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $classLoader = new ClassLoader();
        ClassLoadingInformation::setClassLoader($classLoader);
        return $classLoader;
    }
}
