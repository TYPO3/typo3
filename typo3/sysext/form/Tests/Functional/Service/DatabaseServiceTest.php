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

namespace TYPO3\CMS\Form\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Service\DatabaseService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DatabaseServiceTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];
    protected array $testExtensionsToLoad = ['typo3/sysext/form/Tests/Functional/Fixtures/Extensions/form_references'];
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/form/Tests/Functional/Service/Fixtures/FileadminPublic/Forms/' => 'fileadmin/',
        'typo3/sysext/form/Tests/Functional/Service/Fixtures/FileadminPrivate/Forms/' => 'fileadmin-private/',
        'typo3/sysext/form/Tests/Functional/Service/Fixtures/FileadminOffline/Forms/' => 'fileadmin-offline/',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseServiceTestImport.csv');
    }

    public static function existingFileFormIdentifierDataProvider(): \Generator
    {
        yield 'EXT:notation - public file exists' => [
            'yamlFile' => 'EXT:form_references/Resources/Public/Forms/contact2.form.yaml',
            'expectedHash' => 'd41d8cd98f00b204e9800998ecf8427a',
        ];
        yield 'EXT:notation - private file exists' => [
            'yamlFile' => 'EXT:form_references/Resources/Private/Forms/contact1.form.yaml',
            'expectedHash' => 'd41d8cd98f00b204e9800998ecf8427b',
        ];
        yield 'fileadmin notation 0-storage - public - file exists' => [
            'yamlFile' => '/fileadmin/contact4.form.yaml',
            'expectedHash' => 'd41d8cd98f00b204e9800998ecf8427j',
        ];
        yield 'fileadmin combined notation - public - file exists' => [
            'yamlFile' => '1:/contact4.form.yaml',
            'expectedHash' => 'd41d8cd98f00b204e9800998ecf8427j',
        ];
        yield 'fileadmin combined notation - private - file exists' => [
            'yamlFile' => '2:/contact3.form.yaml',
            'expectedHash' => 'd41d8cd98f00b204e9800998ecf8427k',
        ];
        yield 'fileadmin combined notation - offline - file exists' => [
            'yamlFile' => '3:/contact5.form.yaml',
            'expectedHash' => 'd41d8cd98f00b204e9800998ecf8427l',
        ];
        yield 'uid identifier (existing)' => [
            'yamlFile' => '4712',
            'expectedHash' => 'd41d8cd98f00b204e9800998ecf8427f',
        ];
    }

    #[Test]
    #[DataProvider('existingFileFormIdentifierDataProvider')]
    public function getReferencesByPersistenceIdentifierAcceptsValidFile(string $yamlFile, string $expectedHash): void
    {
        $subject = new DatabaseService();
        $data = $subject->getReferencesByPersistenceIdentifier($yamlFile);
        self::assertSame($expectedHash, $data[0]['hash']);
    }

    public static function missingFileFormIdentifierDataProvider(): \Generator
    {
        yield 'EXT:notation - public file does not exist' => [
            'yamlFile' => 'EXT:form_references/Resources/Public/Forms/contact0.form.yaml',
        ];
        yield 'EXT:notation - private file does not exist' => [
            'yamlFile' => 'EXT:form_references/Resources/Private/Forms/contact0.form.yaml',
        ];
        yield 'fileadmin notation 0-storage - public - file does not exist' => [
            'yamlFile' => '/contact0.form.yaml',
        ];
        yield 'fileadmin notation - public - file does not exist' => [
            'yamlFile' => '/fileadmin/contact0.form.yaml',
        ];
        yield 'fileadmin notation - private - file does not exist' => [
            'yamlFile' => '/fileadmin-private/contact0.form.yaml',
        ];
        yield 'fileadmin notation - offline - file does not exist' => [
            'yamlFile' => '/fileadmin-offline/contact0.form.yaml',
        ];
        yield 'random file path, not existing' => [
            'yamlFile' => '/something/something/DarkSide/contact0.form.yaml',
        ];
        yield 'random path, not existing' => [
            'yamlFile' => '/something/something/DarkSide/',
        ];
        yield 'empty identifier (blank)' => [
            'yamlFile' => ' ',
        ];
        yield 'uid identifier (missing)' => [
            'yamlFile' => '4711',
        ];
        yield 'fixed identifier (true)' => [
            'yamlFile' => 'true',
        ];
        yield 'fixed identifier (false)' => [
            'yamlFile' => 'false',
        ];
    }

    #[Test]
    #[DataProvider('missingFileFormIdentifierDataProvider')]
    public function getReferencesByPersistenceIdentifierSkipsMissingFile(string $yamlFile): void
    {
        $subject = new DatabaseService();
        $data = $subject->getReferencesByPersistenceIdentifier($yamlFile);
        // No sys_refindex files must be retrieved.
        self::assertSame([], $data);
    }

    public static function invalidFileFormIdentifierDataProvider(): \Generator
    {
        yield 'empty identifier' => [
            'yamlFile' => '',
        ];
        yield 'empty identifier (0)' => [
            'yamlFile' => '0',
        ];
    }

    #[Test]
    #[DataProvider('invalidFileFormIdentifierDataProvider')]
    public function getReferencesByPersistenceIdentifierRejectsInvalidIdentifier(string $yamlFile): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $subject = new DatabaseService();
        $data = $subject->getReferencesByPersistenceIdentifier($yamlFile);
        self::assertSame([], $data);
    }
}
