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

namespace TYPO3\CMS\Form\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Form\DependencyInjection\FormYamlCollectorConfigurator;
use TYPO3\CMS\Form\Mvc\Configuration\FormYamlCollector;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FormYamlCollectorConfiguratorTest extends UnitTestCase
{
    private string $tempDir = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/typo3_form_yaml_test_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $path . '/' . $entry;
            is_dir($full) ? $this->removeDirectory($full) : unlink($full);
        }
        rmdir($path);
    }

    /**
     * Creates a minimal package fixture under $this->tempDir/<extensionKey>/ and
     * returns a mock PackageInterface pointing to it.
     *
     * @param array<string,mixed>|null $configYaml  null = do not create config.yaml, array = YAML content
     */
    private function makePackage(
        string $extensionKey,
        string $setDirectoryName,
        ?array $configYaml,
    ): PackageInterface&MockObject {
        $pkgPath = $this->tempDir . '/' . $extensionKey . '/';
        $setDir  = $pkgPath . 'Configuration/Form/' . $setDirectoryName;
        mkdir($setDir, 0777, true);

        if ($configYaml !== null) {
            file_put_contents($setDir . '/config.yaml', \Symfony\Component\Yaml\Yaml::dump($configYaml));
        }

        $package = $this->createMock(PackageInterface::class);
        $package->method('getPackagePath')->willReturn($pkgPath);
        $package->method('getPackageKey')->willReturn($extensionKey);
        return $package;
    }

    /** Builds a configurator with a mocked PackageManager. */
    private function makeConfigurator(
        array $packages,
        ?LoggerInterface $logger = null,
    ): FormYamlCollectorConfigurator {
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getActivePackages')->willReturn($packages);

        return new FormYamlCollectorConfigurator(
            $packageManager,
            $logger ?? self::createStub(LoggerInterface::class),
        );
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    #[Test]
    public function configureRegistersConfigYamlWithCorrectExtPath(): void
    {
        $package = $this->makePackage('my_ext', 'MySet', [
            'name'     => 'vendor/my-set',
            'priority' => 100,
        ]);

        $collector = new FormYamlCollector();
        $this->makeConfigurator([$package])->configure($collector);

        self::assertSame(
            ['EXT:my_ext/Configuration/Form/MySet/config.yaml'],
            $collector->getPaths()
        );
    }

    #[Test]
    public function configureUsesDefaultPriority100WhenPriorityIsAbsent(): void
    {
        $package = $this->makePackage('my_ext', 'MySet', ['name' => 'vendor/my-set']);

        $collector = new FormYamlCollector();
        $this->makeConfigurator([$package])->configure($collector);

        $defs = $collector->getAllConfigurations();
        self::assertCount(1, $defs);
        self::assertSame(100, $defs[0]->priority);
    }

    #[Test]
    public function configureRegistersSetThatHasOnlyConfigYaml(): void
    {
        $package = $this->makePackage('my_extension', 'MinimalSet', ['name' => 'vendor/minimal-set']);

        $collector = new FormYamlCollector();
        $this->makeConfigurator([$package])->configure($collector);

        self::assertSame(
            ['EXT:my_extension/Configuration/Form/MinimalSet/config.yaml'],
            $collector->getPaths()
        );
    }

    #[Test]
    public function configureSkipsPackageWithoutFormConfigDirectory(): void
    {
        $pkgPath = $this->tempDir . '/no_form_dir/';
        mkdir($pkgPath, 0777, true);

        $package = $this->createMock(PackageInterface::class);
        $package->method('getPackagePath')->willReturn($pkgPath);
        $package->method('getPackageKey')->willReturn('no_form_dir');

        $collector = new FormYamlCollector();
        $this->makeConfigurator([$package])->configure($collector);

        self::assertSame([], $collector->getPaths());
    }

    #[Test]
    public function configureSkipsDisabledSetByDeclaredName(): void
    {
        $package = $this->makePackage('my_ext', 'MySet', [
            'name'     => 'vendor/disabled-set',
            'priority' => 100,
        ]);

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['form']['disabledSets'] = ['vendor/disabled-set'];

        $collector = new FormYamlCollector();
        $this->makeConfigurator([$package])->configure($collector);

        unset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['form']['disabledSets']);

        self::assertSame([], $collector->getPaths());
    }

    #[Test]
    public function configureDoesNotSkipSetWhoseNameIsNotInDisabledList(): void
    {
        $package = $this->makePackage('my_ext', 'MySet', [
            'name'     => 'vendor/active-set',
            'priority' => 100,
        ]);

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['form']['disabledSets'] = ['vendor/other-set'];

        $collector = new FormYamlCollector();
        $this->makeConfigurator([$package])->configure($collector);

        unset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['form']['disabledSets']);

        self::assertCount(1, $collector->getPaths());
    }

    #[Test]
    public function configureDoesNotSkipSetWithEmptyNameEvenIfDisabledListIsPopulated(): void
    {
        // A set without a declared name cannot be disabled via the disabled list
        $package = $this->makePackage('my_ext', 'MySet', ['priority' => 100]); // no 'name' key

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['form']['disabledSets'] = [''];

        $collector = new FormYamlCollector();
        $this->makeConfigurator([$package])->configure($collector);

        unset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['form']['disabledSets']);

        self::assertCount(1, $collector->getPaths());
    }

    #[Test]
    public function configureLogsWarningAndSkipsSetWithInvalidConfigYaml(): void
    {
        $pkgPath = $this->tempDir . '/broken_ext/';
        $setDir  = $pkgPath . 'Configuration/Form/BrokenSet';
        mkdir($setDir, 0777, true);
        file_put_contents($setDir . '/config.yaml', "name: valid\nbroken: [\n  unclosed");

        $package = $this->createMock(PackageInterface::class);
        $package->method('getPackagePath')->willReturn($pkgPath);
        $package->method('getPackageKey')->willReturn('broken_ext');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with(
                self::stringContains('could not parse config.yaml'),
                self::arrayHasKey('file')
            );

        $collector = new FormYamlCollector();
        $this->makeConfigurator([$package], $logger)->configure($collector);

        self::assertSame([], $collector->getPaths());
    }

    #[Test]
    public function configureLogsWarningAndSkipsSetWhenConfigYamlIsNotAnArray(): void
    {
        $pkgPath = $this->tempDir . '/scalar_ext/';
        $setDir  = $pkgPath . 'Configuration/Form/ScalarSet';
        mkdir($setDir, 0777, true);
        // YAML scalar (string) instead of mapping
        file_put_contents($setDir . '/config.yaml', 'just a string');

        $package = $this->createMock(PackageInterface::class);
        $package->method('getPackagePath')->willReturn($pkgPath);
        $package->method('getPackageKey')->willReturn('scalar_ext');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with(
                self::stringContains('did not return an array'),
                self::arrayHasKey('file')
            );

        $collector = new FormYamlCollector();
        $this->makeConfigurator([$package], $logger)->configure($collector);

        self::assertSame([], $collector->getPaths());
    }

    #[Test]
    public function configureCollectsSetsFromMultiplePackagesAndSortsByPriority(): void
    {
        $pkgCore = $this->makePackage('form', 'Base', ['name' => 'typo3/form-base', 'priority' => 10]);
        $pkgSite = $this->makePackage('site_pkg', 'Site', ['name' => 'site/form', 'priority' => 200]);

        $collector = new FormYamlCollector();
        $this->makeConfigurator([$pkgSite, $pkgCore])->configure($collector);

        self::assertSame(
            [
                'EXT:form/Configuration/Form/Base/config.yaml',
                'EXT:site_pkg/Configuration/Form/Site/config.yaml',
            ],
            $collector->getPaths()
        );
    }

    #[Test]
    public function configureCollectsMultipleSetsFromSinglePackage(): void
    {
        $pkgPath = $this->tempDir . '/multi_ext/';

        foreach (['SetA' => 10, 'SetB' => 20] as $name => $priority) {
            $setDir = $pkgPath . 'Configuration/Form/' . $name;
            mkdir($setDir, 0777, true);
            file_put_contents($setDir . '/config.yaml', \Symfony\Component\Yaml\Yaml::dump([
                'name'     => 'vendor/' . strtolower($name),
                'priority' => $priority,
            ]));
        }

        $package = $this->createMock(PackageInterface::class);
        $package->method('getPackagePath')->willReturn($pkgPath);
        $package->method('getPackageKey')->willReturn('multi_ext');

        $collector = new FormYamlCollector();
        $this->makeConfigurator([$package])->configure($collector);

        self::assertSame(
            [
                'EXT:multi_ext/Configuration/Form/SetA/config.yaml',
                'EXT:multi_ext/Configuration/Form/SetB/config.yaml',
            ],
            $collector->getPaths()
        );
    }

    #[Test]
    public function configureStoresCorrectSetNameOnDefinition(): void
    {
        $package = $this->makePackage('my_ext', 'MySet', [
            'name'     => 'vendor/named-set',
            'priority' => 100,
        ]);

        $collector = new FormYamlCollector();
        $this->makeConfigurator([$package])->configure($collector);

        $defs = $collector->getAllConfigurations();
        self::assertCount(1, $defs);
        self::assertSame('vendor/named-set', $defs[0]->setName);
    }
}
