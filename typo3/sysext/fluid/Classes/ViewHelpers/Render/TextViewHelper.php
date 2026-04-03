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

namespace TYPO3\CMS\Fluid\ViewHelpers\Render;

use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Schema\Field\InputFieldType;
use TYPO3\CMS\Core\Schema\Field\TextFieldType;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlViewHelper;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3Fluid\Fluid\Core\Parser\UnsafeHTML;
use TYPO3Fluid\Fluid\Core\Parser\UnsafeHTMLString;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\InvalidArgumentValueException;

/**
 * ViewHelper to render content based on records and fields from a TCA schema.
 * Handles the processing of both simple and rich text fields.
 *
 * Can also handle extbase models, you still need to provide the field name, not the property name.
 *
 * ```html
 *   <f:render.text record="{page}" field="bodytext" />
 *   {record -> f:render.text(field: 'title')}
 *   <f:render.text field="subheader">{record}</f:render.text>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-render-text
 */
final class TextViewHelper extends AbstractViewHelper
{
    /**
     * We need to disable escaping for the children, otherwise extbase models are given as string to the viewHelper.
     * AbstractDomainObject has a __toString method, fluid executes it before giving use the object.
     * This is a deeper issue in Fluid that we cannot easily resolve.
     * This ViewHelper escapes the output itself, so we can safely disable escaping for the children and output.
     */
    protected $escapeChildren = false;

    protected $escapeOutput = false;

    public function __construct(
        private readonly TcaSchemaFactory $tcaSchema,
        private readonly RecordFactory $recordFactory,
        private readonly DataMapFactory $dataMapFactory,
    ) {}

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('record', PageInformation::class . '|' . RecordInterface::class . '|' . DomainObjectInterface::class, 'A Record API Object or extbase model');
        $this->registerArgument('field', 'string', 'The database field that should be rendered (even if extbase model is used).', true);
    }

    public function getContentArgumentName(): string
    {
        return 'record';
    }

    public function validateAdditionalArguments(array $arguments): void
    {
        // This prevents the default Fluid exception from being thrown for this ViewHelper if it's used
        // with arguments that aren't defined in initialArguments(). We do this to make it possible for
        // extensions to offer additional functionality by overriding this ViewHelper, which sometimes
        // requires adding more (most likely optional) arguments to the ViewHelper's definition.
        // Note that this is probably not a long-term solution and might change with future TYPO3 major
        // versions. Currently, it has minimal impact to template authors and makes things possible
        // for extensions that wouldn't be possible otherwise.
    }

    public function render(): UnsafeHTML
    {
        $input = $this->renderChildren();
        $field = $this->arguments['field'];

        if ($input instanceof PageInformation) {
            $input = $this->recordFactory->createResolvedRecordFromDatabaseRow('pages', $input->getPageRecord());
        }

        if (!$input instanceof RecordInterface && !$input instanceof DomainObjectInterface) {
            throw new InvalidArgumentValueException(
                'The record argument must be an instance of ' . PageInformation::class . ' or ' . RecordInterface::class . ' or ' . DomainObjectInterface::class . ' . Given: ' . get_debug_type($input),
                1770539910,
            );
        }

        ['table' => $table, 'fullType' => $fullType, 'value' => $value] = $this->extractInformation($input, $field);

        if (!is_string($value)) {
            throw new InvalidArgumentValueException('The value of the field "' . $table . '.' . $field . '" must be a string. Given: ' . get_debug_type($value), 1770321858);
        }

        $fieldSchema = $this->tcaSchema->get($fullType)->getField($field);
        if ($fieldSchema instanceof InputFieldType) {
            return new UnsafeHTMLString(htmlspecialchars($value));
        }

        if ($fieldSchema instanceof TextFieldType) {
            if (!$fieldSchema->isRichText()) {
                return new UnsafeHTMLString(nl2br(htmlspecialchars($value)));
            }

            return new UnsafeHTMLString(
                $this->renderingContext->getViewHelperInvoker()->invoke(
                    HtmlViewHelper::class,
                    [],
                    $this->renderingContext,
                    fn() => $value,
                ),
            );
        }

        throw new InvalidArgumentValueException('The field "' . $table . '.' . $field . '" is not supported. Given: ' . get_debug_type($fieldSchema), 1770618219);
    }

    /**
     * @return array{table: string, fullType: string, value: mixed}
     */
    private function extractInformation(RecordInterface|DomainObjectInterface $input, string $field): array
    {
        if ($input instanceof RecordInterface) {
            return [
                'table' => $input->getMainType(),
                'fullType' => $input->getFullType(),
                'value' => $input->get($field) ?? '',
            ];
        }
        $dataMap = $this->dataMapFactory->buildDataMap($input::class);

        $recordType = $this->getRecordType($input, $dataMap);

        return [
            'table' => $dataMap->getTableName(),
            'fullType' => $dataMap->getTableName() . ($recordType ? '.' . $recordType : ''),
            'value' => $this->getResultingValue($input, $dataMap, $field),
        ];
    }

    private function getRecordType(DomainObjectInterface $input, DataMap $dataMap): ?string
    {
        $recordType = $dataMap->getRecordType();
        if ($recordType !== null) {
            return $recordType;
        }

        $recordTypeFieldName = $dataMap->getRecordTypeColumnName();
        if ($recordTypeFieldName === null) {
            return null;
        }

        foreach ($input->_getProperties() as $propertyName => $value) {
            if ($dataMap->getColumnMap($propertyName)?->columnName === $recordTypeFieldName) {
                return $value;
            }
        }
        throw new InvalidArgumentValueException('The record type field "' . $recordTypeFieldName . '" does not exist in the given model ' . $input::class . '.', 1771507212);
    }

    private function getResultingValue(DomainObjectInterface $input, DataMap $dataMap, string $field): mixed
    {
        foreach ($input->_getProperties() as $propertyName => $value) {
            if ($dataMap->getColumnMap($propertyName)?->columnName === $field) {
                return $value ?? '';
            }
        }

        throw new InvalidArgumentValueException('Could not find the field "' . $field . '" in the given model ' . $input::class . '.', 1771507213);
    }
}
