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

namespace TYPO3\CMS\Reactions\Reaction;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\ItemProcessingService;
use TYPO3\CMS\Core\DataHandling\ItemsProcessorContext;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Schema\Struct\SelectItemCollection;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Reactions\Authentication\ReactionUserAuthentication;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;
use TYPO3\CMS\Reactions\Validation\CreateRecordReactionField;
use TYPO3\CMS\Reactions\Validation\CreateRecordReactionTable;

/**
 * A reaction that creates a database record based on the payload within a request.
 *
 * @internal This is a specific reaction implementation and is not considered part of the Public TYPO3 API.
 */
class CreateRecordReaction implements ReactionInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly TcaSchemaFactory $tcaSchemaFactory,
        private readonly ItemProcessingService $itemProcessingService,
    ) {}

    public static function getType(): string
    {
        return 'create-record';
    }

    public static function getDescription(): string
    {
        return 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.reaction_type.create_record';
    }

    public static function getIconIdentifier(): string
    {
        return 'content-database';
    }

    public function react(ServerRequestInterface $request, array $payload, ReactionInstruction $reaction): ResponseInterface
    {
        // @todo: Response needs to be based on given accept headers
        $table = (string)($reaction->toArray()['table_name'] ?? '');
        $fields = (array)($reaction->toArray()['fields'] ?? []);

        if (!new CreateRecordReactionTable($table)->isAllowedForCreation()) {
            return $this->jsonResponse(['success' => false, 'error' => 'Invalid argument "table_name"'], 400);
        }

        if ($fields === []) {
            return $this->jsonResponse(['success' => false, 'error' => 'No fields given.'], 400);
        }

        $schema = $this->tcaSchemaFactory->get($table);
        $storagePid = (int)($reaction->toArray()['storage_pid'] ?? 0);
        $dataHandlerData = [];
        $skipped = [];
        foreach ($fields as $fieldName => $value) {
            $fieldName = (string)$fieldName;
            if ($value === '' || $value === null) {
                // The row exists in the map but carries nothing, which is how the field map
                // expresses a field the reaction leaves alone. Writing an empty value instead
                // would overrule the default the target table declares for it. It is asked
                // before the field is: a key carrying nothing writes nothing whether the
                // target table knows it or not, so a map left over from another table does
                // not report every one of its empty rows.
                continue;
            }
            if (!CreateRecordReactionField::isAllowedForCreation($schema, $fieldName)) {
                $skipped[$fieldName] = SkippedFieldReason::NOT_MAPPABLE->value;
                continue;
            }
            $field = $schema->getField($fieldName);
            if (!CreateRecordReactionField::isWritableBy($field, $table, $fieldName, $this->getBackendUser())) {
                $skipped[$fieldName] = SkippedFieldReason::NOT_PERMITTED->value;
                continue;
            }
            $value = $this->replacePlaceHolders($value, $payload);
            if ($value === null) {
                $skipped[$fieldName] = SkippedFieldReason::PLACEHOLDER_UNRESOLVED->value;
                continue;
            }
            if ($value === '') {
                $skipped[$fieldName] = SkippedFieldReason::RESOLVED_TO_NOTHING->value;
                continue;
            }
            if (CreateRecordReactionField::holdsBitmask($field->getType())) {
                $value = $this->normalizeCheckboxValue($value, $field, $table, $fieldName, $storagePid);
                if ($value === null) {
                    $skipped[$fieldName] = SkippedFieldReason::VALUE_NOT_HOLDABLE->value;
                    continue;
                }
            }
            if (CreateRecordReactionField::isComparedAgainstItems($field->getType())
                && !$this->isOfferedItemValue($value, $field, $table, $fieldName, $storagePid)
            ) {
                $skipped[$fieldName] = SkippedFieldReason::VALUE_NOT_OFFERED->value;
                continue;
            }
            $dataHandlerData[$fieldName] = $value;
        }

        if ($dataHandlerData === []) {
            return $this->jsonResponse($this->withReport([
                'success' => false,
                'error' => 'No field of the record could be written',
            ], $skipped), 400);
        }
        $dataHandlerData['pid'] = $storagePid;

        $data[$table][StringUtility::getUniqueId('NEW')] = $dataHandlerData;
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, [], $this->getBackendUser());
        $dataHandler->process_datamap();

        return $this->buildResponseFromDataHandler($dataHandler, 201, $skipped);
    }

    /**
     * Returns null where a placeholder cannot be resolved. The field is then left out of
     * the datamap entirely, so the default of the table applies, rather than the record
     * being written with a literal "${...}" in it.
     *
     * @internal only public due to tests
     */
    public function replacePlaceHolders(mixed $value, array $payload): ?string
    {
        if (!is_string($value)) {
            return is_scalar($value) ? (string)$value : null;
        }
        $unresolved = false;
        $resolved = preg_replace_callback(
            '/\$\{([^\}]*)\}/',
            static function (array $match) use ($payload, &$unresolved): string {
                try {
                    $replacement = ArrayUtility::getValueByPath($payload, $match[1], '.');
                } catch (\RuntimeException) {
                    // Either the payload does not carry this path, or the configured value
                    // names no path at all: "${}" is answered with an exception of its own.
                    $unresolved = true;
                    return '';
                }
                if (!is_scalar($replacement)) {
                    // The payload carries the path but not a value that can be written into
                    // a field, and casting it would store the string "Array".
                    $unresolved = true;
                    return '';
                }
                return (string)$replacement;
            },
            $value
        );
        return $unresolved ? null : $resolved;
    }

    /**
     * A checkbox column stores a bitmask, and DataHandler masks away anything outside its
     * range without reporting it: "true" ends up as 0 and 7 on a field of two boxes as 3,
     * neither of which is distinguishable from a state the caller asked for. The boolean
     * wordings a caller is likely to send are resolved here, a number is answered only
     * where the bitmask can hold it, and anything else is a value the column cannot hold.
     */
    private function normalizeCheckboxValue(string $value, FieldTypeInterface $field, string $table, string $fieldName, int $storagePid): ?string
    {
        $items = $this->composeItems($field, $table, $fieldName, $storagePid);
        if ($items === null) {
            return null;
        }
        $boxes = max(1, count($items));
        $value = trim($value);
        if (MathUtility::canBeInterpretedAsInteger($value)) {
            return (int)$value >= 0 && (int)$value <= (2 ** $boxes) - 1 ? $value : null;
        }
        $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($boolean === null || $boxes > 1) {
            return null;
        }
        return $boolean ? '1' : '0';
    }

    /**
     * Whether a select or radio offers the value as one of its items.
     *
     * Neither type is safe to pass on unchecked:
     *
     * - A select is never compared against its items by DataHandler, which only applies
     *   exclusiveKeys, authMode and maxitems. An unknown value would be stored as it is.
     * - A radio is compared, but a value that matches nothing is dropped silently, so the
     *   caller would read a 201 while the field kept its default.
     *
     * The items compared against are the ones the field map offered the value from, which
     * is why they are composed for the storage page rather than taken from the TCA alone.
     */
    private function isOfferedItemValue(string $value, FieldTypeInterface $field, string $table, string $fieldName, int $storagePid): bool
    {
        $type = $field->getType();
        $declared = SelectItemCollection::createFromArray(
            $this->declaredItems($field, $table, $fieldName, $storagePid),
            $type
        );
        if ($this->holdsValue($declared, $value)) {
            return true;
        }
        if (!$this->composesItems($field->getConfiguration())) {
            return false;
        }
        $items = $this->composeItems($field, $table, $fieldName, $storagePid);
        return $items !== null && $this->holdsValue($items, $value);
    }

    /**
     * The items a field offers on the page the records land on, which is the list the field
     * map offered when the value was picked. Null where a processor cannot be run at all:
     * it composed the list, so nothing else can say what belongs in it.
     *
     * @return SelectItemCollection|null
     */
    private function composeItems(FieldTypeInterface $field, string $table, string $fieldName, int $storagePid): ?SelectItemCollection
    {
        $configuration = $field->getConfiguration();
        $items = SelectItemCollection::createFromArray(
            $this->declaredItems($field, $table, $fieldName, $storagePid),
            $field->getType()
        );
        if (!$this->composesItems($configuration)) {
            return $items;
        }
        try {
            return $this->itemProcessingService->processItems(
                $items,
                new ItemsProcessorContext(
                    table: $table,
                    field: $fieldName,
                    // The row the field map compiled its control with. The record does not
                    // exist yet and lands on the storage page, so a processor reading the
                    // record it composes its items for reads the one the integrator saw.
                    row: ['uid' => 0, 'pid' => $storagePid],
                    fieldConfiguration: $configuration,
                    processorParameters: [],
                    realPid: $storagePid,
                    site: $this->itemProcessingService->resolveSite($storagePid),
                    fieldTSconfig: $this->fieldTsConfig($table, $fieldName, $storagePid),
                )
            );
        } catch (\Throwable) {
            // A processor is arbitrary code and may refuse to run outside the form it was
            // written for, by an exception or by an error.
            return null;
        }
    }

    private function composesItems(array $configuration): bool
    {
        return ($configuration['itemsProcFunc'] ?? '') !== '' || ($configuration['itemsProcessors'] ?? []) !== [];
    }

    /**
     * The items a field declares, plus TCEFORM "addItems" of the storage page.
     *
     * "addItems" is the only composition step that can name a value the field itself does
     * not declare, so it is the only one applied here. It is also select-only: the data
     * providers of a checkbox and a radio never read it, and neither does this.
     *
     * "keepItems" and "removeItems" are left out on purpose. They only narrow the list,
     * and they are evaluated per backend user. Narrowing here could reject a value the
     * field map legitimately offered to someone the restriction does not apply to.
     *
     * @return array<int, mixed>
     */
    private function declaredItems(FieldTypeInterface $field, string $table, string $fieldName, int $storagePid): array
    {
        $configuration = $field->getConfiguration();
        $items = is_array($configuration['items'] ?? null) ? $configuration['items'] : [];
        if ($field->getType() !== 'select') {
            return $items;
        }
        $addItems = $this->fieldTsConfig($table, $fieldName, $storagePid)['addItems.'] ?? null;
        foreach (is_array($addItems) ? $addItems : [] as $value => $label) {
            if (str_ends_with((string)$value, '.') || !is_scalar($label)) {
                continue;
            }
            $items[] = ['label' => (string)$label, 'value' => (string)$value];
        }
        return $items;
    }

    private function fieldTsConfig(string $table, string $fieldName, int $storagePid): array
    {
        $fieldTsConfig = BackendUtility::getPagesTSconfig($storagePid)['TCEFORM.'][$table . '.'][$fieldName . '.'] ?? null;
        return is_array($fieldTsConfig) ? $fieldTsConfig : [];
    }

    /**
     * @param iterable<SelectItem> $items
     */
    private function holdsValue(iterable $items, string $value): bool
    {
        foreach ($items as $item) {
            if (!$item->isDivider() && (string)$item->getValue() === $value) {
                return true;
            }
        }
        return false;
    }

    protected function buildResponseFromDataHandler(DataHandler $dataHandler, int $successCode = 200, array $skippedFields = []): ResponseInterface
    {
        // Success depends on whether at least one NEW id has been substituted
        $success = $dataHandler->substNEWwithIDs !== [];

        $statusCode = $successCode;
        $data = [
            'success' => $success,
        ];

        if (!$success) {
            $statusCode = 400;
            $data['error'] = 'Record could not be created';
        }

        $data = $this->withReport($data, $skippedFields, $this->describeDataHandlerErrors($dataHandler->errorLog));

        return $this->jsonResponse($data, $statusCode);
    }

    /**
     * Reports that DataHandler rejected something, without repeating what it said.
     *
     * Its messages are written for the backend log, not for whoever holds the secret of a
     * webhook. They can disclose the page the record was written to, the authMode a value
     * failed and the value it failed with, the value another record already holds where a
     * field has to be unique, and the raw message of the database driver. None of that is
     * known to the caller otherwise, and the set of messages grows with DataHandler, so
     * none of it is forwarded. The detail stays in the log it was written to.
     *
     * Fields the reaction itself refused are a different matter and are reported by name
     * in "skippedFields": those messages are composed here, not passed on.
     *
     * @param array<int, string> $errorLog
     * @return array<int, string>
     */
    private function describeDataHandlerErrors(array $errorLog): array
    {
        if ($errorLog === []) {
            return [];
        }
        return ['A value of the record was rejected by the target table and not written'];
    }

    /**
     * What the reaction did not write, beside whether it wrote a record at all.
     *
     * "skippedFields" names each field the reaction left out and why, so a caller reads a
     * field name and a reason rather than a sentence. "warnings" carries what has no field
     * to name it by. Both are absent where there is nothing to say, and "error" stays what
     * it was: the one reason the call itself failed, present only when it did.
     *
     * @param array<string, string> $skippedFields
     * @param array<int, string> $warnings
     */
    private function withReport(array $data, array $skippedFields, array $warnings = []): array
    {
        if ($skippedFields !== []) {
            $data['skippedFields'] = $skippedFields;
        }
        if ($warnings !== []) {
            $data['warnings'] = $warnings;
        }
        return $data;
    }

    protected function jsonResponse(array $data, int $statusCode = 200): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream((string)json_encode($data)));
    }

    private function getBackendUser(): ReactionUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
