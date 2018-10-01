<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Hooks;

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

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;
use TYPO3\CMS\Form\Slot\FilePersistenceSlot;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\ReferenceIndexUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Update wizard to migrate all forms currently in use to new ending
 * @internal
 */
class FormFileExtensionUpdate implements ChattyInterface, UpgradeWizardInterface
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var FormPersistenceManager
     */
    protected $persistenceManager;

    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @var ReferenceIndex
     */
    protected $referenceIndex;

    /**
     * @var FlexFormTools
     */
    protected $flexFormTools;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * Return the identifier for this wizard
     * This should be the same string as used in the ext_localconf class registration
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'formFileExtension';
    }

    /**
     * Return the speaking name of this wizard
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Rename form definition file extension from .yaml to .form.yaml';
    }

    /**
     * Return the description for this wizard
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Form definition files need to be named *.form.yaml to have a way of distinguishing form yaml ' .
               'configuration files from other yaml configuration files. This wizard will analyze and rename found files.';
    }

    /**
     * Returns an array of class names of Prerequisite classes
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     *
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            ReferenceIndexUpdatedPrerequisite::class,
            DatabaseUpdatedPrerequisite::class
        ];
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Checks whether updates are required.
     *
     * @return bool Whether an update is required (TRUE) or not (FALSE)
     */
    public function updateNecessary(): bool
    {
        $updateNeeded = false;

        $this->persistenceManager = $this->getObjectManager()->get(FormPersistenceManager::class);
        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        foreach ($this->getFormDefinitionsInformation() as $formDefinitionInformation) {
            if (
                (
                    $formDefinitionInformation['hasNewFileExtension'] === true
                    && $formDefinitionInformation['hasReferencesForOldFileExtension'] === false
                    && $formDefinitionInformation['hasReferencesForNewFileExtension'] === false
                )
                || (
                    $formDefinitionInformation['hasNewFileExtension'] === false
                    && $formDefinitionInformation['location'] === 'extension'
                    && $formDefinitionInformation['hasReferencesForOldFileExtension'] === false
                    && $formDefinitionInformation['hasReferencesForNewFileExtension'] === false
                )
            ) {
                continue;
            }

            if (
                $formDefinitionInformation['hasNewFileExtension'] === false
                && $formDefinitionInformation['location'] === 'storage'
            ) {
                $updateNeeded = true;
                $this->output->writeln('Form definition files were found that should be migrated to be named .form.yaml.');
            }

            if (
                $formDefinitionInformation['hasNewFileExtension']
                && $formDefinitionInformation['hasReferencesForOldFileExtension']
            ) {
                $updateNeeded = true;
                $this->output->writeln('Referenced form definition files found that should be updated.');
            }

            if (
                $formDefinitionInformation['referencesForOldFileExtensionNeedsFlexformUpdates'] === true
                || $formDefinitionInformation['referencesForNewFileExtensionNeedsFlexformUpdates'] === true
            ) {
                $updateNeeded = true;
                if ($formDefinitionInformation['hasNewFileExtension'] === true) {
                    $this->output->writeln('Referenced form definition files found that should be updated.');
                } elseif ($formDefinitionInformation['location'] === 'storage') {
                    $this->output->writeln('Referenced form definition files found that should be updated.');
                } else {
                    $this->output->writeln(
                        '<warning>There are references to form definitions which are located in extensions and thus cannot be renamed automatically by this wizard.'
                      . 'This form definitions from extensions that do not end with .form.yaml have to be renamed by hand!'
                      . 'After that you can run this wizard again to migrate the references.</warning>'
                    );
                }
            }
        }

        return $updateNeeded;
    }

    /**
     * Performs the accordant updates.
     *
     * @return bool Whether everything went smoothly or not
     */
    public function executeUpdate(): bool
    {
        $success = true;

        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $filePersistenceSlot = GeneralUtility::makeInstance(FilePersistenceSlot::class);

        $this->connection = $connectionPool->getConnectionForTable('tt_content');
        $this->persistenceManager = $this->getObjectManager()->get(FormPersistenceManager::class);
        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $this->referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
        $this->flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);

        $filePersistenceSlot->defineInvocation(
            FilePersistenceSlot::COMMAND_FILE_RENAME,
            true
        );

        $formDefinitionsInformation = $this->getFormDefinitionsInformation();
        foreach ($formDefinitionsInformation as $currentPersistenceIdentifier => $formDefinitionInformation) {
            if (
                (
                    $formDefinitionInformation['hasNewFileExtension'] === true
                    && $formDefinitionInformation['hasReferencesForOldFileExtension'] === false
                    && $formDefinitionInformation['hasReferencesForNewFileExtension'] === false
                )
                || (
                    $formDefinitionInformation['hasNewFileExtension'] === false
                    && $formDefinitionInformation['location'] === 'extension'
                    && $formDefinitionInformation['hasReferencesForOldFileExtension'] === false
                    && $formDefinitionInformation['hasReferencesForNewFileExtension'] === false
                )
            ) {
                continue;
            }

            if (
                $formDefinitionInformation['hasNewFileExtension'] === true
                && (
                    $formDefinitionInformation['hasReferencesForOldFileExtension'] === true
                    || $formDefinitionInformation['hasReferencesForNewFileExtension'] === true
                )
            ) {
                foreach ($formDefinitionInformation['referencesForOldFileExtension'] as $referenceForOldFileExtension) {
                    $newFlexformXml = $this->generateNewFlexformForReference(
                        $referenceForOldFileExtension,
                        $referenceForOldFileExtension['sheetIdentifiersWhichNeedsUpdate'],
                        $formDefinitionInformation['persistenceIdentifier']
                    );
                    $this->updateContentReference(
                        $referenceForOldFileExtension['ttContentUid'],
                        $newFlexformXml,
                        true
                    );
                }

                foreach ($formDefinitionInformation['referencesForNewFileExtension'] as $referenceForNewFileExtension) {
                    $newFlexformXml = $this->generateNewFlexformForReference(
                        $referenceForNewFileExtension,
                        $referenceForNewFileExtension['sheetIdentifiersWhichNeedsUpdate']
                    );
                    $this->updateContentReference(
                        $referenceForNewFileExtension['ttContentUid'],
                        $newFlexformXml
                    );
                }

                continue;
            }

            if ($formDefinitionInformation['location'] === 'storage') {
                $file = $formDefinitionInformation['file'];

                $newPossiblePersistenceIdentifier = $this->persistenceManager->getUniquePersistenceIdentifier(
                    $file->getNameWithoutExtension(),
                    $file->getParentFolder()->getCombinedIdentifier()
                );
                $newFileName = PathUtility::pathinfo(
                    $newPossiblePersistenceIdentifier,
                    PATHINFO_BASENAME
                );

                try {
                    $file->rename($newFileName, DuplicationBehavior::RENAME);
                    $newPersistenceIdentifier = $file->getCombinedIdentifier();
                } catch (\Exception $e) {
                    $this->output->writeln(sprintf(
                        '<error>Failed to rename form definition "%s" to "%s".</error>',
                        $formDefinitionInformation['persistenceIdentifier'],
                        $newFileName
                    ));
                    $success = false;
                    continue;
                }

                if (
                    $formDefinitionInformation['hasReferencesForOldFileExtension'] === true
                    || $formDefinitionInformation['hasReferencesForNewFileExtension'] === true
                ) {
                    foreach ($formDefinitionInformation['referencesForOldFileExtension'] as $referenceForOldFileExtension) {
                        $sheetIdentifiersWhichNeedsUpdate = $this->getSheetIdentifiersWhichNeedsUpdate(
                            $referenceForOldFileExtension['flexform'],
                            $formDefinitionsInformation,
                            $currentPersistenceIdentifier,
                            $formDefinitionInformation['persistenceIdentifier'],
                            $newPersistenceIdentifier
                        );
                        $newFlexformXml = $this->generateNewFlexformForReference(
                            $referenceForOldFileExtension,
                            $sheetIdentifiersWhichNeedsUpdate,
                            $newPersistenceIdentifier
                        );
                        $this->updateContentReference(
                            $referenceForOldFileExtension['ttContentUid'],
                            $newFlexformXml
                        );
                    }

                    foreach ($formDefinitionInformation['referencesForNewFileExtension'] as $referenceForNewFileExtension) {
                        $sheetIdentifiersWhichNeedsUpdate = $this->getSheetIdentifiersWhichNeedsUpdate(
                            $referenceForNewFileExtension['flexform'],
                            $formDefinitionsInformation,
                            $currentPersistenceIdentifier,
                            $formDefinitionInformation['persistenceIdentifier'],
                            $newPersistenceIdentifier
                        );
                        $newFlexformXml = $this->generateNewFlexformForReference(
                            $referenceForNewFileExtension,
                            $sheetIdentifiersWhichNeedsUpdate,
                            $newPersistenceIdentifier
                        );
                        $this->updateContentReference(
                            $referenceForNewFileExtension['ttContentUid'],
                            $newFlexformXml
                        );
                    }
                }
            } else {
                $success = false;
                $this->output->writeln(sprintf(
                    '<error>Failed to rename form definition "%s" to "%s". You have to be rename it by hand!. '
                  . 'After that you can run this wizard again to migrate the references.</error>',
                    $formDefinitionInformation['persistenceIdentifier'],
                    $this->getNewPersistenceIdentifier($formDefinitionInformation['persistenceIdentifier'])
                ));
            }
        }

        $filePersistenceSlot->defineInvocation(
            FilePersistenceSlot::COMMAND_FILE_RENAME,
            null
        );

        return $success;
    }

    /**
     * @return array
     */
    protected function getFormDefinitionsInformation(): array
    {
        $formDefinitionsInformation = array_merge(
            $this->getFormDefinitionsInformationFromStorages(),
            $this->getFormDefinitionsInformationFromExtensions()
        );

        $formDefinitionsInformation = $this->enrichFormDefinitionsInformationWithDataFromReferences($formDefinitionsInformation);

        return $formDefinitionsInformation;
    }

    /**
     * @return array
     */
    protected function getFormDefinitionsInformationFromStorages(): array
    {
        $formDefinitionsInformation =  [];

        foreach ($this->persistenceManager->retrieveYamlFilesFromStorageFolders() as $file) {
            $persistenceIdentifier = $file->getCombinedIdentifier();

            $formDefinition = $this->getFormDefinition($file);
            if (empty($formDefinition)) {
                continue;
            }

            $formDefinitionsInformation[$persistenceIdentifier] = $this->setFormDefinitionInformationData(
                $persistenceIdentifier,
                $formDefinition,
                $file,
                'storage'
            );
        }

        return $formDefinitionsInformation;
    }

    /**
     * @return array
     */
    protected function getFormDefinitionsInformationFromExtensions(): array
    {
        $formDefinitionsInformation =  [];

        foreach ($this->persistenceManager->retrieveYamlFilesFromExtensionFolders() as $persistenceIdentifier => $_) {
            try {
                $file = $this->resourceFactory->retrieveFileOrFolderObject($persistenceIdentifier);
            } catch (\Exception $exception) {
                continue;
            }

            $formDefinition = $this->getFormDefinition($file);
            if (empty($formDefinition)) {
                continue;
            }

            $formDefinitionsInformation[$persistenceIdentifier] = $this->setFormDefinitionInformationData(
                $persistenceIdentifier,
                $formDefinition,
                $file,
                'extension'
            );
        }

        return $formDefinitionsInformation;
    }

    /**
     * @param string $persistenceIdentifier
     * @param array $formDefinition
     * @param File $file
     * @param string $localtion
     * @return array
     */
    protected function setFormDefinitionInformationData(
        string $persistenceIdentifier,
        array $formDefinition,
        File $file,
        string $localtion
    ): array {
        return [
            'location' => $localtion,
            'persistenceIdentifier' => $persistenceIdentifier,
            'prototypeName' => $formDefinition['prototypeName'],
            'formIdentifier' => $formDefinition['identifier'],
            'file' => $file,
            'referencesForOldFileExtension' => [],
            'referencesForNewFileExtension' => [],
            'hasNewFileExtension' => $this->hasNewFileExtension($persistenceIdentifier),
            'hasReferencesForOldFileExtension' => false,
            'hasReferencesForNewFileExtension' => false,
            'referencesForOldFileExtensionNeedsFlexformUpdates' => false,
            'referencesForNewFileExtensionNeedsFlexformUpdates' => false,
        ];
    }

    /**
     * @param array $formDefinitionsInformation
     * @return array
     */
    protected function enrichFormDefinitionsInformationWithDataFromReferences(array $formDefinitionsInformation): array
    {
        foreach ($this->getAllFlexformFieldsFromFormPlugins() as $pluginData) {
            if (empty($pluginData['pi_flexform'])) {
                continue;
            }
            $flexform = GeneralUtility::xml2array($pluginData['pi_flexform']);
            $referencedPersistenceIdentifier = $this->getPersistenceIdentifierFromFlexform($flexform);
            $referenceHasNewFileExtension = $this->hasNewFileExtension($referencedPersistenceIdentifier);
            $possibleOldReferencedPersistenceIdentifier = $this->getOldPersistenceIdentifier($referencedPersistenceIdentifier);
            $possibleNewReferencedPersistenceIdentifier = $this->getNewPersistenceIdentifier($referencedPersistenceIdentifier);

            $referenceData = [
                'scope' => null,
                'ttContentUid' => (int)$pluginData['uid'],
                'flexform' => $flexform,
                'sheetIdentifiersWhichNeedsUpdate' => [],
            ];

            $targetPersistenceIdentifier = null;
            if (array_key_exists($referencedPersistenceIdentifier, $formDefinitionsInformation)) {
                $targetPersistenceIdentifier = $referencedPersistenceIdentifier;
                if ($referenceHasNewFileExtension) {
                    $referenceData['scope'] = 'referencesForNewFileExtension';
                } else {
                    $referenceData['scope'] = 'referencesForOldFileExtension';
                }
            } else {
                if ($referenceHasNewFileExtension) {
                    if (array_key_exists($possibleOldReferencedPersistenceIdentifier, $formDefinitionsInformation)) {
                        $targetPersistenceIdentifier = $possibleOldReferencedPersistenceIdentifier;
                        $referenceData['scope'] = 'referencesForNewFileExtension';
                    } else {
                        // There is no existing file for this reference
                        continue;
                    }
                } else {
                    if (array_key_exists($possibleNewReferencedPersistenceIdentifier, $formDefinitionsInformation)) {
                        $targetPersistenceIdentifier = $possibleNewReferencedPersistenceIdentifier;
                        $referenceData['scope'] = 'referencesForOldFileExtension';
                    } else {
                        // There is no existing file for this reference
                        continue;
                    }
                }
            }

            $referenceData['sheetIdentifiersWhichNeedsUpdate'] = $this->getSheetIdentifiersWhichNeedsUpdate(
                $flexform,
                $formDefinitionsInformation,
                $targetPersistenceIdentifier,
                $possibleOldReferencedPersistenceIdentifier,
                $possibleNewReferencedPersistenceIdentifier
            );

            $scope = $referenceData['scope'];

            $formDefinitionsInformation[$targetPersistenceIdentifier][$scope][] = $referenceData;
            if ($scope === 'referencesForOldFileExtension') {
                $formDefinitionsInformation[$targetPersistenceIdentifier]['hasReferencesForOldFileExtension'] = true;
                $formDefinitionsInformation[$targetPersistenceIdentifier]['referencesForOldFileExtensionNeedsFlexformUpdates'] = !empty($referenceData['sheetIdentifiersWhichNeedsUpdate']);
            } else {
                $formDefinitionsInformation[$targetPersistenceIdentifier]['hasReferencesForNewFileExtension'] = true;
                $formDefinitionsInformation[$targetPersistenceIdentifier]['referencesForNewFileExtensionNeedsFlexformUpdates'] = !empty($referenceData['sheetIdentifiersWhichNeedsUpdate']);
            }
        }

        return $formDefinitionsInformation;
    }

    /**
     * @param array $flexform
     * @param array $formDefinitionsInformation
     * @param string $targetPersistenceIdentifier
     * @param string $possibleOldReferencedPersistenceIdentifier
     * @param string $possibleNewReferencedPersistenceIdentifier
     * @return array
     */
    protected function getSheetIdentifiersWhichNeedsUpdate(
        array $flexform,
        array $formDefinitionsInformation,
        string $targetPersistenceIdentifier,
        string $possibleOldReferencedPersistenceIdentifier,
        string $possibleNewReferencedPersistenceIdentifier
    ): array {
        $sheetIdentifiersWhichNeedsUpdate = [];

        $sheetIdentifiers = $this->getSheetIdentifiersForFinisherOverrides($flexform);
        foreach ($sheetIdentifiers as $currentSheetIdentifier => $finisherIdentifier) {
            $sheetIdentifierForOldPersistenceIdentifier = $this->buildExpectedSheetIdentifier(
                $possibleOldReferencedPersistenceIdentifier,
                $formDefinitionsInformation[$targetPersistenceIdentifier]['prototypeName'],
                $formDefinitionsInformation[$targetPersistenceIdentifier]['formIdentifier'],
                $finisherIdentifier
            );

            $sheetIdentifierForNewPersistenceIdentifier = $this->buildExpectedSheetIdentifier(
                $possibleNewReferencedPersistenceIdentifier,
                $formDefinitionsInformation[$targetPersistenceIdentifier]['prototypeName'],
                $formDefinitionsInformation[$targetPersistenceIdentifier]['formIdentifier'],
                $finisherIdentifier
            );

            if (
                $currentSheetIdentifier === $sheetIdentifierForOldPersistenceIdentifier
                && !array_key_exists($sheetIdentifierForNewPersistenceIdentifier, $sheetIdentifiers)
            ) {
                $sheetIdentifiersWhichNeedsUpdate[$currentSheetIdentifier] = $sheetIdentifierForNewPersistenceIdentifier;
            }
        }

        return $sheetIdentifiersWhichNeedsUpdate;
    }

    /**
     * @param array $flexform
     * @return array
     */
    protected function getSheetIdentifiersForFinisherOverrides(array $flexform): array
    {
        $sheetIdentifiers = [];
        foreach ($this->getFinisherSheetsFromFlexform($flexform) as $sheetIdentifier => $sheetData) {
            $itemOptionPath = array_keys($sheetData['lDEF']);
            $firstSheetItemOptionPath = array_shift($itemOptionPath);
            preg_match('#^settings\.finishers\.(.*)\..+$#', $firstSheetItemOptionPath, $matches);
            if (!isset($matches[1])) {
                continue;
            }
            $sheetIdentifiers[$sheetIdentifier] = $matches[1];
        }

        return $sheetIdentifiers;
    }

    /**
     * @param array $flexform
     * @return array
     */
    protected function getFinisherSheetsFromFlexform(array $flexform): array
    {
        if (!isset($flexform['data'])) {
            return [];
        }

        return array_filter(
            $flexform['data'],
            function ($key) {
                return $key !== 'sDEF' && strlen($key) === 32;
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @param array $flexform
     * @return string
     */
    protected function getPersistenceIdentifierFromFlexform(array $flexform): string
    {
        return $flexform['data']['sDEF']['lDEF']['settings.persistenceIdentifier']['vDEF'] ?? '';
    }

    /**
     * @param array $referenceData
     * @param array $sheetIdentifiersWhichNeedsUpdate
     * @param string $newPersistenceIdentifier
     * @return string
     */
    protected function generateNewFlexformForReference(
        array $referenceData,
        array $sheetIdentifiersWhichNeedsUpdate,
        string $newPersistenceIdentifier = ''
    ): string {
        $flexform = $referenceData['flexform'];
        if (!empty($newPersistenceIdentifier)) {
            $flexform['data']['sDEF']['lDEF']['settings.persistenceIdentifier']['vDEF'] = $newPersistenceIdentifier;
        }

        foreach ($sheetIdentifiersWhichNeedsUpdate as $oldSheetIdentifier => $newSheetIdentifier) {
            $flexform['data'][$newSheetIdentifier] = $flexform['data'][$oldSheetIdentifier];
            unset($flexform['data'][$oldSheetIdentifier]);
        }

        return $this->flexFormTools->flexArray2Xml($flexform, true);
    }

    /**
     * @param string $persistenceIdentifier
     * @return bool
     */
    protected function hasNewFileExtension(string $persistenceIdentifier): bool
    {
        return StringUtility::endsWith(
            $persistenceIdentifier,
            FormPersistenceManager::FORM_DEFINITION_FILE_EXTENSION
        );
    }

    /**
     * @param array $formDefinition
     * @return bool
     */
    protected function looksLikeAFormDefinition(array $formDefinition): bool
    {
        return isset($formDefinition['identifier'], $formDefinition['type']) && $formDefinition['type'] === 'Form';
    }

    /**
     * @param string $persistenceIdentifier
     * @return string
     */
    protected function getOldPersistenceIdentifier(string $persistenceIdentifier): string
    {
        return preg_replace(
            '
            #^(.*)(\.form\.yaml)$#',
            '${1}.yaml',
            $persistenceIdentifier
        );
    }

    /**
     * @param string $persistenceIdentifier
     * @return string
     */
    protected function getNewPersistenceIdentifier(string $persistenceIdentifier): string
    {
        return preg_replace(
            '#(?<!\.form).yaml$#',
            '.form.yaml',
            $persistenceIdentifier
        );
    }

    /**
     * @param string $persistenceIdentifier
     * @param string $prototypeName
     * @param string $formIdentifier
     * @param string $finisherIdentifier
     * @return string
     */
    protected function buildExpectedSheetIdentifier(
        string $persistenceIdentifier,
        string $prototypeName,
        string $formIdentifier,
        string $finisherIdentifier
    ): string {
        return md5(
            implode('', [
                $persistenceIdentifier,
                $prototypeName,
                $formIdentifier,
                $finisherIdentifier
            ])
        );
    }

    /**
     * @param File $file
     * @return array
     */
    protected function getFormDefinition(File $file): array
    {
        try {
            $rawYamlContent = $file->getContents();
            $formDefinition = $this->extractMetaDataFromCouldBeFormDefinition($rawYamlContent);

            if (!$this->looksLikeAFormDefinition($formDefinition)) {
                $formDefinition = [];
            }
        } catch (\Exception $exception) {
            $formDefinition = [];
        }

        return $formDefinition;
    }

    /**
     * @param string $maybeRawFormDefinition
     * @return array
     */
    protected function extractMetaDataFromCouldBeFormDefinition(string $maybeRawFormDefinition): array
    {
        $metaDataProperties = ['identifier', 'type', 'label', 'prototypeName'];
        $metaData = [];
        foreach (explode("\n", $maybeRawFormDefinition) as $line) {
            if (empty($line) || $line[0] === ' ') {
                continue;
            }

            [$key, $value] = explode(':', $line);
            if (
                empty($key)
                || empty($value)
                || !in_array($key, $metaDataProperties)
            ) {
                continue;
            }

            $value = trim($value, ' \'"');
            $metaData[$key] = $value;
        }

        return $metaData;
    }

    /**
     * @return array
     */
    protected function getAllFlexformFieldsFromFormPlugins(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $records = $queryBuilder
            ->select('uid', 'pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'CType',
                    $queryBuilder->createNamedParameter('form_formframework', \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAll();

        return $records;
    }

    /**
     * @param int $uid
     * @param string $flexform
     * @param bool $updateRefindex
     */
    protected function updateContentReference(
        int $uid,
        string $flexform,
        bool $updateRefindex = false
    ): void {
        $this->connection->update(
            'tt_content',
            ['pi_flexform' => $flexform],
            ['uid' => $uid]
        );

        if (!$updateRefindex) {
            return;
        }

        $this->referenceIndex->updateRefIndexTable(
            'tt_content',
            $uid
        );
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager(): ObjectManager
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }
}
