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

namespace TYPO3\CMS\Form\Tests\Functional\Hooks;

use Doctrine\DBAL\FetchMode;
use Symfony\Component\Console\Output\NullOutput;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Form\Hooks\FormFileExtensionUpdate;
use TYPO3\CMS\Form\Slot\FilePersistenceSlot;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FormFileExtensionUpdateTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'form',
    ];

    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/form/Tests/Functional/Hooks/Fixtures/test_resources',
    ];

    /**
     * @var FormFileExtensionUpdate
     */
    private $subject;

    /**
     * @var FilePersistenceSlot
     */
    private $slot;

    /**
     * @var FlexFormTools
     */
    private $flexForm;

    /**
     * @var ReferenceIndex
     */
    private $referenceIndex;

    /**
     * @var Folder
     */
    private $storageFolder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $folderIdentifier = 'form_definitions';
        $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getStorageObject(1);

        if ($storage->hasFolder($folderIdentifier)) {
            $storage->getFolder($folderIdentifier)->delete(true);
        }

        $output = new NullOutput();
        $this->subject = GeneralUtility::makeInstance(FormFileExtensionUpdate::class);
        $this->subject->setOutput($output);
        $this->slot = GeneralUtility::makeInstance(FilePersistenceSlot::class);
        $this->flexForm = GeneralUtility::makeInstance(FlexFormTools::class);
        $this->referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
        $this->storageFolder = $storage->createFolder($folderIdentifier);
    }

    protected function tearDown(): void
    {
        $this->storageFolder->delete(true);
        parent::tearDown();
    }

    /*
     * --- CHECK FOR UPDATE ---
     */

    /**
     * @return bool
     */
    private function invokeCheckForUpdate(): bool
    {
        return $this->subject->updateNecessary();
    }

    /**
     * @test
     */
    public function updateIsNotRequiredHavingUpdatedFormDefinitions()
    {
        $this->createStorageFormDefinition('updated', false);
        self::assertFalse($this->invokeCheckForUpdate());
    }

    /**
     * @test
     */
    public function updateIsRequiredHavingOutdatedStorageFormDefinitions()
    {
        $this->createStorageFormDefinition('legacy', true);
        self::assertTrue($this->invokeCheckForUpdate());
    }

    /**
     * @test
     */
    public function updateIsNotRequiredHavingUpdatedStorageReferences()
    {
        $this->createStorageFormDefinition('updated', false);
        $this->createReference(
            $this->createStorageFileIdentifier('updated.form.yaml'),
            'updated'
        );
        self::assertFalse($this->invokeCheckForUpdate());
    }

    /**
     * @test
     */
    public function updateIsNotRequiredHavingUpdatedStorageReferencesWithFinisherOverrides(
    ) {
        $this->createStorageFormDefinition('updated', false);
        $finisherOverrides = [
            'FirstFinisher' => StringUtility::getUniqueId(),
            'SecondFinisher' => StringUtility::getUniqueId(),
        ];
        $this->createReference(
            $this->createStorageFileIdentifier('updated.form.yaml'),
            'updated',
            $finisherOverrides
        );
        self::assertFalse($this->invokeCheckForUpdate());
    }

    /**
     * @test
     */
    public function updateIsRequiredHavingOutdatedStorageReferences()
    {
        // form definition was renamed already
        $this->createStorageFormDefinition('updated', false);
        // but references not updated yet
        $this->createReference(
            $this->createStorageFileIdentifier('updated.yaml'),
            'updated'
        );
        self::assertTrue($this->invokeCheckForUpdate());
    }

    /**
     * @test
     */
    public function updateIsRequiredHavingOutdatedStorageReferencesWithFinisherOverrides(
    ) {
        // form definition was renamed already
        $this->createStorageFormDefinition('updated', false);
        // but references not updated yet
        $finisherOverrides = [
            'FirstFinisher' => StringUtility::getUniqueId(),
            'SecondFinisher' => StringUtility::getUniqueId(),
        ];
        $this->createReference(
            $this->createStorageFileIdentifier('updated.yaml'),
            'updated',
            $finisherOverrides
        );
        self::assertTrue($this->invokeCheckForUpdate());
    }

    /**
     * @test
     */
    public function updateIsNotRequiredHavingOutdatedExtensionFormDefinitions()
    {
        $this->setUpAllowedExtensionPaths();
        self::assertFalse($this->invokeCheckForUpdate());
    }

    /**
     * @test
     */
    public function updateIsNotRequiredHavingUpdatedExtensionReferences()
    {
        $this->setUpAllowedExtensionPaths();
        $this->createReference(
            $this->createExtensionFileIdentifier('updated.form.yaml'),
            'updated'
        );
        self::assertFalse($this->invokeCheckForUpdate());
    }

    /**
     * @test
     */
    public function updateIsRequiredHavingOutdatedExtensionReferences()
    {
        $this->setUpAllowedExtensionPaths();
        $this->createReference(
            $this->createExtensionFileIdentifier('updated.yaml'),
            'updated'
        );
        self::assertTrue($this->invokeCheckForUpdate());
    }

    /**
     * @test
     */
    public function updateIsRequiredHavingOutdatedExtensionReferencesWithFinisherOverrides(
    ) {
        $this->setUpAllowedExtensionPaths();
        $finisherOverrides = [
            'FirstFinisher' => StringUtility::getUniqueId(),
            'SecondFinisher' => StringUtility::getUniqueId(),
        ];
        $this->createReference(
            $this->createExtensionFileIdentifier('updated.yaml'),
            'updated',
            $finisherOverrides
        );
        self::assertTrue($this->invokeCheckForUpdate());
    }

    /*
     * --- PERFORM UPDATE ---
     */

    private function invokePerformUpdate(): bool
    {
        return $this->subject->executeUpdate();
    }

    /**
     * @test
     */
    public function performUpdateSucceedsHavingOutdatedStorageFormDefinitions()
    {
        $this->createStorageFormDefinition('legacy', true);
        self::assertTrue(
            $this->invokePerformUpdate()
        );
        self::assertTrue(
            $this->storageFolder->hasFile('legacy.form.yaml')
        );
    }

    /**
     * @test
     */
    public function performUpdateSucceedsHavingOutdatedStorageReferences()
    {
        // form definition was renamed already
        $this->createStorageFormDefinition('updated', false);
        // but references not updated yet
        $this->createReference(
            $this->createStorageFileIdentifier('updated.yaml'),
            'updated'
        );
        // having an additional reference
        $this->createReference(
            $this->createStorageFileIdentifier('updated.yaml'),
            'updated'
        );
        self::assertTrue(
            $this->invokePerformUpdate()
        );
        $expectedFileIdentifier = $this->createStorageFileIdentifier(
            'updated.form.yaml'
        );
        foreach ($this->retrieveAllFlexForms() as $flexForm) {
            self::assertSame(
                $expectedFileIdentifier,
                $flexForm['data']['sDEF']['lDEF']['settings.persistenceIdentifier']['vDEF']
            );
        }
    }

    /**
     * @test
     */
    public function performUpdateSucceedsHavingOutdatedStorageReferencesWithFinisherOverrides(
    ) {
        // form definition was renamed already
        $this->createStorageFormDefinition('updated', false);
        // but references not updated yet
        $finisherOverrides = [
            'FirstFinisher' => StringUtility::getUniqueId(),
            'SecondFinisher' => StringUtility::getUniqueId(),
        ];
        $this->createReference(
            $this->createStorageFileIdentifier('updated.yaml'),
            'updated',
            $finisherOverrides
        );
        // having an additional reference
        $this->createReference(
            $this->createStorageFileIdentifier('updated.yaml'),
            'updated',
            $finisherOverrides
        );
        self::assertTrue(
            $this->invokePerformUpdate()
        );
        $expectedFileIdentifier = $this->createStorageFileIdentifier(
            'updated.form.yaml'
        );
        $expectedSheetIdentifiers = $this->createFinisherOverridesSheetIdentifiers(
            $expectedFileIdentifier,
            'updated',
            $finisherOverrides
        );
        foreach ($this->retrieveAllFlexForms() as $flexForm) {
            self::assertSame(
                $expectedFileIdentifier,
                $flexForm['data']['sDEF']['lDEF']['settings.persistenceIdentifier']['vDEF'] ?? null
            );
            foreach ($finisherOverrides as $finisherIdentifier => $finisherValue) {
                $sheetIdentifier = $expectedSheetIdentifiers[$finisherIdentifier];
                $propertyName = sprintf(
                    'settings.finishers.%s.value',
                    $finisherIdentifier
                );
                self::assertSame(
                    $finisherValue,
                    $flexForm['data'][$sheetIdentifier]['lDEF'][$propertyName]['vDEF'] ?? null
                );
            }
        }
    }

    /**
     * @test
     */
    public function performUpdateSucceedsHavingOutdatedExtensionReferences()
    {
        $this->setUpAllowedExtensionPaths();
        $this->createReference(
            $this->createExtensionFileIdentifier('updated.yaml'),
            'updated'
        );
        // having an additional reference
        $this->createReference(
            $this->createExtensionFileIdentifier('updated.yaml'),
            'updated'
        );
        self::assertTrue(
            $this->invokePerformUpdate()
        );
        $expectedFileIdentifier = $this->createExtensionFileIdentifier(
            'updated.form.yaml'
        );
        foreach ($this->retrieveAllFlexForms() as $flexForm) {
            self::assertSame(
                $expectedFileIdentifier,
                $flexForm['data']['sDEF']['lDEF']['settings.persistenceIdentifier']['vDEF'] ?? null
            );
        }
    }

    /**
     * @test
     */
    public function performUpdateSucceedsHavingOutdatedExtensionReferencesWithFinisherOverrides(
    ) {
        $this->setUpAllowedExtensionPaths();
        $finisherOverrides = [
            'FirstFinisher' => StringUtility::getUniqueId(),
            'SecondFinisher' => StringUtility::getUniqueId(),
        ];
        $this->createReference(
            $this->createExtensionFileIdentifier('updated.yaml'),
            'updated',
            $finisherOverrides
        );
        // having an additional reference
        $this->createReference(
            $this->createExtensionFileIdentifier('updated.yaml'),
            'updated',
            $finisherOverrides
        );
        self::assertTrue(
            $this->invokePerformUpdate()
        );
    }

    /*
     * --- HELPER FUNCTIONS ---
     */

    /**
     * @param string $name
     * @param bool $legacy
     */
    private function createStorageFormDefinition(
        string $name,
        bool $legacy = false
    ) {
        $content = implode(LF, [
            'type: Form',
            'identifier: ' . $name,
            'prototypeName: standard'
        ]);

        $fileName = $name . '.' . ($legacy ? 'yaml' : 'form.yaml');
        $fileIdentifier = $this->createStorageFileIdentifier($fileName);

        if (!$legacy) {
            $this->slot->allowInvocation(
                FilePersistenceSlot::COMMAND_FILE_CREATE,
                $fileIdentifier
            );
            $this->slot->allowInvocation(
                FilePersistenceSlot::COMMAND_FILE_SET_CONTENTS,
                $fileIdentifier,
                $this->slot->getContentSignature($content)
            );
        }

        $this->storageFolder->createFile($fileName)->setContents($content);
    }

    /**
     * @param string $fileIdentifier
     * @param string $formIdentifier
     * @param array $finisherOverrides
     */
    private function createReference(
        string $fileIdentifier,
        string $formIdentifier,
        array $finisherOverrides = []
    ) {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tt_content');

        $flexForm = [
            'data' => [
                'sDEF' => [
                    'lDEF' => [
                        'settings.persistenceIdentifier' => [
                            'vDEF' => $fileIdentifier,
                        ],
                        'settings.overrideFinishers' => [
                            'vDEF' => empty($finisherOverrides) ? '0' : '1',
                        ],
                    ]
                ]
            ]
        ];

        $sheetIdentifiers = $this->createFinisherOverridesSheetIdentifiers(
            $fileIdentifier,
            $formIdentifier,
            $finisherOverrides
        );
        foreach ($finisherOverrides as $finisherIdentifier => $finisherValue) {
            $sheetIdentifier = $sheetIdentifiers[$finisherIdentifier];
            $propertyName = sprintf(
                'settings.finishers.%s.value',
                $finisherIdentifier
            );
            $flexForm['data'][$sheetIdentifier]['lDEF'] = [
                $propertyName => [
                    'vDEF' => $finisherValue
                ],
            ];
        }

        $values = [
            'pid' => 1,
            'header' => sprintf(
                'Form Content Element for "%s"',
                $formIdentifier
            ),
            'CType' => 'form_formframework',
            'pi_flexform' => $this->flexForm
                ->flexArray2Xml($flexForm, true)
        ];

        $connection->insert('tt_content', $values);
        $id = $connection->lastInsertId('tt_content');
        $this->referenceIndex->updateRefIndexTable('tt_content', $id);
    }

    /**
     * Sets up additional paths to allow using form definitions from extension.
     */
    private function setUpAllowedExtensionPaths()
    {
        ExtensionManagementUtility::addTypoScriptSetup(trim('
            module.tx_form.settings.yamlConfigurations {
                110 = EXT:test_resources/Configuration/Yaml/AllowedExtensionPaths.yaml
            }
            plugin.tx_form.settings.yamlConfigurations {
                110 = EXT:test_resources/Configuration/Yaml/AllowedExtensionPaths.yaml
            }
        '));
    }

    /**
     * @return array
     */
    private function retrieveAllFlexForms(): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tt_content');

        return array_map(
            function (array $record) {
                return GeneralUtility::xml2array($record['pi_flexform']);
            },
            $connection->select(['pi_flexform'], 'tt_content')
                ->fetchAll(FetchMode::ASSOCIATIVE)
        );
    }

    /**
     * @param string $fileIdentifier
     * @param string $formIdentifier
     * @param array $finisherOverrides
     * @return array
     */
    private function createFinisherOverridesSheetIdentifiers(
        string $fileIdentifier,
        string $formIdentifier,
        array $finisherOverrides
    ): array {
        $sheetIdentifiers = [];
        foreach (array_keys($finisherOverrides) as $finisherIdentifier) {
            $sheetIdentifiers[$finisherIdentifier] = md5(
                $fileIdentifier
                . 'standard'
                . $formIdentifier
                . $finisherIdentifier
            );
        }
        return $sheetIdentifiers;
    }

    /**
     * @param string $fileName
     * @return string
     */
    private function createStorageFileIdentifier(string $fileName): string
    {
        return $this->storageFolder->getCombinedIdentifier() . $fileName;
    }

    /**
     * @param string $fileName
     * @return string
     */
    private function createExtensionFileIdentifier(string $fileName): string
    {
        return 'EXT:test_resources/Configuration/Form/' . $fileName;
    }
}
