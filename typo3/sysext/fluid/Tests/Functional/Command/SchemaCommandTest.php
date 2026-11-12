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

namespace TYPO3\CMS\Fluid\Tests\Functional\Command;

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Command\SchemaCommand;
use TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperResolverDelegateRegistry;
use TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperResolverFactoryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SchemaCommandTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function schemasAreWrittenToDedicatedTransientFolderByDefault(): void
    {
        $directory = Environment::getVarPath() . '/transient/fluid-schema/';

        $exitCode = new CommandTester($this->createCommand())->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertNotSame([], GeneralUtility::getFilesInDir($directory, 'xsd'));
        self::assertSame([], GeneralUtility::getFilesInDir(Environment::getVarPath() . '/transient/', 'xsd'));
    }

    #[Test]
    public function schemasOfFormerDefaultLocationAreRemoved(): void
    {
        $formerDirectory = Environment::getVarPath() . '/transient/';
        GeneralUtility::mkdir_deep($formerDirectory);
        file_put_contents($formerDirectory . 'schema_Stale.xsd', '');
        file_put_contents($formerDirectory . 'unrelated.xsd', '');

        new CommandTester($this->createCommand())->execute([]);

        self::assertFileDoesNotExist($formerDirectory . 'schema_Stale.xsd');
        self::assertFileExists($formerDirectory . 'unrelated.xsd');
    }

    #[Test]
    public function staleSchemasInTargetDirectoryAreRemoved(): void
    {
        $directory = Environment::getVarPath() . '/schema-stale/';
        GeneralUtility::mkdir_deep($directory);
        file_put_contents($directory . 'schema_Stale.xsd', '');

        new CommandTester($this->createCommand())->execute(['path' => $directory]);

        self::assertFileDoesNotExist($directory . 'schema_Stale.xsd');
        self::assertNotSame([], GeneralUtility::getFilesInDir($directory, 'xsd'));
    }

    #[Test]
    public function commandFailsIfDirectoryCanNotBeCreated(): void
    {
        $file = Environment::getVarPath() . '/schema-is-a-file';
        file_put_contents($file, '');
        $tester = new CommandTester($this->createCommand());

        $exitCode = $tester->execute(['path' => $file]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('could not be created', $tester->getDisplay());
    }

    #[Test]
    public function commandFailsIfSchemaFileCanNotBeWritten(): void
    {
        $directory = Environment::getVarPath() . '/schema-blocked/';
        $blockedFile = $directory . 'schema_TYPO3Fluid_Fluid_ViewHelpers.xsd';
        GeneralUtility::mkdir_deep($blockedFile);
        $tester = new CommandTester($this->createCommand());

        $exitCode = $tester->execute(['path' => $directory]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString($blockedFile, $tester->getDisplay());
    }

    #[Test]
    public function schemasAreWrittenToGivenAbsolutePath(): void
    {
        $directory = Environment::getVarPath() . '/schema-absolute/';

        $exitCode = new CommandTester($this->createCommand())->execute(['path' => $directory]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertNotSame([], GeneralUtility::getFilesInDir($directory, 'xsd'));
    }

    #[Test]
    public function schemasAreWrittenToGivenPathRelativeToProjectRoot(): void
    {
        $exitCode = new CommandTester($this->createCommand())->execute(['path' => 'var/schema-relative']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertNotSame([], GeneralUtility::getFilesInDir(Environment::getProjectPath() . '/var/schema-relative/', 'xsd'));
    }

    private function createCommand(): SchemaCommand
    {
        return new SchemaCommand(
            $this->get(ClassLoader::class),
            $this->get(ViewHelperResolverFactoryInterface::class),
            $this->get(ViewHelperResolverDelegateRegistry::class),
        );
    }
}
