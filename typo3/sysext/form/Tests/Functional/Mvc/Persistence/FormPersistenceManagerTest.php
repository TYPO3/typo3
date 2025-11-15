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

namespace TYPO3\CMS\Form\Tests\Functional\Mvc\Persistence;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FormPersistenceManagerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'form',
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/form/Tests/Functional/Mvc/Persistence/Fixtures/Extensions/test_form_persistence',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/form/Tests/Functional/Mvc/Persistence/Fixtures/Folders/fileadmin/form_definitions' => 'fileadmin/form_definitions',
    ];

    protected array $configurationToUseInTestInstance = [
        'FE' => [
            'defaultTypoScript_setup' => '@import "EXT:form/Tests/Functional/Mvc/Persistence/Fixtures/formSetup.typoscript"',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/be_users.csv');
        $this->setUpBackendUser(1);
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    #[Test]
    public function loadFormFromFilemountStorageReturnsFormDefinition(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file_storage.csv');
        $subject = $this->get(FormPersistenceManager::class);
        $persistenceIdentifier = '1:/form_definitions/TestFilemountForm.form.yaml';
        $result = $subject->load($persistenceIdentifier);

        self::assertArrayHasKey('identifier', $result);
        self::assertArrayHasKey('label', $result);
        self::assertArrayHasKey('renderables', $result);
        self::assertSame('filemount-test-form', $result['identifier']);
        self::assertSame('Test Filemount Form', $result['label']);
        self::assertSame('standard', $result['prototypeName']);
    }

    #[Test]
    public function loadFormFromExtensionStorageReturnsFormDefinition(): void
    {
        $subject = $this->get(FormPersistenceManager::class);
        $persistenceIdentifier = 'EXT:test_form_persistence/Resources/Private/Forms/TestForm.form.yaml';
        $result = $subject->load($persistenceIdentifier);

        self::assertArrayHasKey('identifier', $result);
        self::assertArrayHasKey('label', $result);
        self::assertArrayHasKey('renderables', $result);
        self::assertSame('test-extension-form', $result['identifier']);
        self::assertSame('Test Extension Form', $result['label']);
        self::assertSame('standard', $result['prototypeName']);
    }

    #[Test]
    public function loadNonExistentFormFromFilemountStorageReturnsInvalidFormDefinition(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file_storage.csv');
        $subject = $this->get(FormPersistenceManager::class);
        $persistenceIdentifier = '1:/form_definitions/NonExistentForm.form.yaml';
        $result = $subject->load($persistenceIdentifier);

        self::assertArrayHasKey('invalid', $result);
        self::assertTrue($result['invalid']);
        self::assertArrayHasKey('label', $result);
        self::assertArrayHasKey('identifier', $result);
        self::assertSame($persistenceIdentifier, $result['identifier']);
    }

    #[Test]
    public function loadNonExistentFormFromExtensionStorageReturnsInvalidFormDefinition(): void
    {
        $subject = $this->get(FormPersistenceManager::class);
        $persistenceIdentifier = 'EXT:test_form_persistence/Resources/Private/Forms/NonExistent.form.yaml';
        $result = $subject->load($persistenceIdentifier);

        self::assertArrayHasKey('invalid', $result);
        self::assertTrue($result['invalid']);
        self::assertArrayHasKey('label', $result);
        self::assertArrayHasKey('identifier', $result);
        self::assertSame($persistenceIdentifier, $result['identifier']);
    }
}
