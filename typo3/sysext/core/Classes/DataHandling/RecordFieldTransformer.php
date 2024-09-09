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

namespace TYPO3\CMS\Core\DataHandling;

use Doctrine\DBAL\Types\Type;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Persistence\RecordIdentityMap;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Domain\RecordPropertyClosure;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;
use TYPO3\CMS\Core\Resource\Collection\LazyFileReferenceCollection;
use TYPO3\CMS\Core\Resource\Collection\LazyFolderCollection;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Schema\Field\DateTimeFieldType;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\FileFieldType;
use TYPO3\CMS\Core\Schema\Field\FlexFormFieldType;
use TYPO3\CMS\Core\Schema\Field\RelationalFieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\StaticSelectFieldType;
use TYPO3\CMS\Core\Schema\FlexFormSchemaFactory;
use TYPO3\CMS\Core\Schema\RelationMap;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * This generic mapper takes a field value of a record, and maps the value
 * of a field with a specific type (TCA column type) to an expanded property
 * (e.g. a type "file" field to a collection of FileReference objects).
 *
 * Common examples are \DateTimeImmutable objects for TCA fields of type=datetime.
 *
 * In general, this class is very inflexible, and not configurable,
 * but we hope to extend this further in the future to add custom mappings.
 *
 * This class also calls the RelationResolver for any kind of resolved relations,
 * but tries to handle most of the logic on its own when no lazy-loading is needed.
 *
 * About lazy-laading: For all relation types, which do not have a "toOne" relationship,
 * a lazy collection is used. The relations in this collection are only resolved once
 * they are accessed. For the "toOne" relations, a RecordPropertyClosure is used, which
 * also initializes the corresponding record only when accessed. While a collection could
 * be empty after being resolved, a single record might resolve to NULL, in case of an
 * invalid relation value.
 *
 * @internal This class is not part of the TYPO3 Core API. It might get moved or changed.
 */
readonly class RecordFieldTransformer
{
    public function __construct(
        protected RelationResolver $relationResolver,
        protected ResourceFactory $resourceFactory,
        protected FlexFormService $flexFormService,
        protected FlexFormSchemaFactory $flexFormSchemaFactory,
        protected LinkService $linkService,
        protected TypoLinkCodecService $typoLinkCodecService,
        protected ConnectionPool $connectionPool,
        protected RecordIdentityMap $recordIdentityMap,
    ) {}

    public function transformField(FieldTypeInterface $fieldInformation, RawRecord $rawRecord, Context $context): mixed
    {
        $fieldValue = $rawRecord->get($fieldInformation->getName());

        // type=file needs to be handled before RelationalFieldTypeInterface
        if ($fieldInformation instanceof FileFieldType) {
            if ($fieldInformation->getRelationshipType()->hasOne()) {
                return new RecordPropertyClosure(
                    function () use ($rawRecord, $fieldInformation, $context): ?FileReference {
                        $fileReference = $this->relationResolver->resolveFileReferences($rawRecord, $fieldInformation, $context)[0] ?? null;
                        if ($fileReference === null) {
                            return null;
                        }
                        return new FileReference($fileReference->getProperties());
                    }
                );
            }
            return new LazyFileReferenceCollection($fieldValue, function () use ($rawRecord, $fieldInformation, $context): array {
                return $this->relationResolver->resolveFileReferences($rawRecord, $fieldInformation, $context);
            });
        }

        if ($fieldInformation instanceof RelationalFieldTypeInterface) {
            /** @var RecordFactory $recordFactory */
            // @todo This method is called by RecordFactory -> instantiating the factory here again shows, that those classes should actually be somehow belong together.
            $recordFactory = GeneralUtility::makeInstance(RecordFactory::class);
            if ($fieldInformation->getRelationshipType()->hasOne()) {
                return new RecordPropertyClosure(
                    function () use ($rawRecord, $fieldInformation, $context, $recordFactory): ?RecordInterface {
                        $recordData = $this->relationResolver->resolve($rawRecord, $fieldInformation, $context)[0] ?? null;
                        if ($recordData === null) {
                            return null;
                        }
                        $dbTable = $recordData['table'];
                        $row = $recordData['row'];
                        // check RecordIdentityMap for already loaded records
                        if ($this->recordIdentityMap->hasIdentifier($dbTable, (int)$row['uid'])) {
                            $record = $this->recordIdentityMap->findByIdentifier($dbTable, (int)$row['uid']);
                        } else {
                            $record = $recordFactory->createResolvedRecordFromDatabaseRow($dbTable, $row, $context);
                            $this->recordIdentityMap->add($record);
                        }
                        return $record;
                    }
                );
            }
            return new LazyRecordCollection(
                $fieldValue,
                function () use ($rawRecord, $fieldInformation, $context, $recordFactory): array {
                    $relationalRecords = [];
                    $recordData = $this->relationResolver->resolve($rawRecord, $fieldInformation, $context);
                    foreach ($recordData as $singleRecordData) {
                        $dbTable = $singleRecordData['table'];
                        $row = $singleRecordData['row'];
                        // check RecordIdentityMap for already loaded records
                        if ($this->recordIdentityMap->hasIdentifier($dbTable, (int)$row['uid'])) {
                            $relationalRecords[] = $this->recordIdentityMap->findByIdentifier($dbTable, (int)$row['uid']);
                        } else {
                            $record = $recordFactory->createResolvedRecordFromDatabaseRow($dbTable, $row, $context);
                            $this->recordIdentityMap->add($record);
                            $relationalRecords[] = $record;
                        }
                    }
                    return $relationalRecords;
                }
            );
        }

        if ($fieldInformation->isType(TableColumnType::FOLDER)) {
            if (in_array((string)($fieldInformation->getConfiguration()['relationship'] ?? ''), ['oneToOne', 'manyToOne'], true)) {
                return new RecordPropertyClosure(
                    function () use ($fieldValue): ?Folder {
                        $folder = $this->resolveFoldersRecursive(GeneralUtility::trimExplode(',', (string)$fieldValue, true, 1))[0] ?? null;
                        if ($folder === null) {
                            return null;
                        }
                        return new Folder($folder->getStorage(), $folder->getIdentifier(), $folder->getName());
                    }
                );
            }
            return new LazyFolderCollection($fieldValue, function () use ($fieldValue): array {
                return $this->resolveFoldersRecursive(GeneralUtility::trimExplode(',', (string)$fieldValue, true));
            });
        }

        // Static select lists is transformed into an array of values
        if ($fieldInformation instanceof StaticSelectFieldType) {
            $selectForcedToSingle = (string)($fieldInformation->getConfiguration()['renderType'] ?? '') === 'selectSingle';
            return $selectForcedToSingle ? $fieldValue : GeneralUtility::trimExplode(',', (string)$fieldValue, true);
        }
        if ($fieldInformation->isType(TableColumnType::FLEX)) {
            /** @var FlexFormFieldType $fieldInformation */
            return $this->processFlexForm($rawRecord, $fieldInformation, (string)$fieldValue, $context);
        }
        if ($fieldInformation->isType(TableColumnType::JSON)) {
            return Type::getType('json')->convertToPHPValue((string)$fieldValue, $this->connectionPool->getConnectionForTable($rawRecord->getMainType())->getDatabasePlatform());
        }
        if ($fieldInformation instanceof DateTimeFieldType) {
            return $fieldValue === null || (!$fieldInformation->isNullable() && $fieldValue === 0)
                ? null
                : new \DateTimeImmutable((MathUtility::canBeInterpretedAsInteger($fieldValue) ? '@' : '') . $fieldValue);
        }
        if ($fieldInformation->isType(TableColumnType::LINK)) {
            return TypolinkParameter::createFromTypolinkParts($this->typoLinkCodecService->decode($fieldValue));
        }
        return $fieldValue;
    }

    /**
     * @return Folder[]
     */
    protected function resolveFoldersRecursive(array $folders): array
    {
        $foldersRecursive = [];
        foreach ($folders as $singleFolder) {
            if ($singleFolder instanceof Folder === false) {
                $singleFolder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($singleFolder);
            }
            $foldersRecursive[] = $singleFolder;
            array_push($foldersRecursive, ...$this->resolveFoldersRecursive($singleFolder->getSubfolders()));
        }
        return $foldersRecursive;
    }

    /**
     * This method creates an array which contains all information which is valid from the
     * selected Schema. Ideally, this should be "FlexRecord" objects, and also keep the original values.
     * This functionality will likely change in the future.
     */
    protected function processFlexForm(RawRecord $record, FlexFormFieldType $fieldInformation, mixed $fieldValue, Context $context): array
    {
        $plainValues = $this->flexFormService->convertFlexFormContentToArray((string)$fieldValue);
        $usedSchema = $this->flexFormSchemaFactory->getSchemaForRecord(
            $record,
            $fieldInformation,
            // @todo: RelationMap does not work in FlexForm currently, as we do not have this information persisted somewhere
            new RelationMap()
        );

        if ($usedSchema !== null) {
            $resolvedValues = [];
            // Flatten keys (because we receive settings[mysetting] and we want settings.mysetting)
            $plainValues = ArrayUtility::flattenPlain($plainValues);
            $recordFactory = GeneralUtility::makeInstance(RecordFactory::class);
            foreach ($plainValues as $fieldName => $plainFieldValue) {
                // That's a "fun" workaround: In order to allow to process e.g. "sDEF/header", we need
                // to add this to the "rawRecord" (thus, we clone it), so it is within the array
                // and then set "sDEF/header" even though this is not a DB field. Then we keep it in "$fieldName"
                // which actually is the plain field name (in this case "header")
                $fieldInformationOfFlexField = $usedSchema->getField($fieldName);
                // No field given, we just skip the value, as it is not properly defined
                if ($fieldInformationOfFlexField === null) {
                    continue;
                }
                $rawRecordValues = array_replace($record->toArray(), [$fieldInformationOfFlexField->getName() => $plainFieldValue]);
                $fakeRawRecordWithFlexField = $recordFactory->createRawRecord($record->getMainType(), $rawRecordValues);
                $transformedValue = $this->transformField($fieldInformationOfFlexField, $fakeRawRecordWithFlexField, $context);
                $resolvedValues[$fieldName] = $transformedValue;
            }
            return ArrayUtility::unflatten($resolvedValues);
        }
        return $plainValues;
    }
}
