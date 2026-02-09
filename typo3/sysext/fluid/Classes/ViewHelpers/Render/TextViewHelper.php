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
use TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlViewHelper;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3Fluid\Fluid\Core\Parser\UnsafeHTML;
use TYPO3Fluid\Fluid\Core\Parser\UnsafeHTMLString;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to render content based on records and fields from a TCA schema.
 * Handles the processing of both simple and rich text fields.
 *
 * ````html
 *   <f:render.text record="{page}" field="bodytext" />
 *   {record -> f:render.text(field: 'title')}
 *   <f:render.text field="subheader">{record}</f:render.text>
 * ````
 */
final class TextViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function __construct(
        private readonly TcaSchemaFactory $tcaSchema,
        private readonly RecordFactory $recordFactory,
    ) {}

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('record', PageInformation::class . '|' . RecordInterface::class, 'A Record API Object');
        $this->registerArgument('field', 'string', 'The field that should be rendered.', true);
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
        $record = $this->renderChildren();
        $field = $this->arguments['field'];

        if ($record instanceof PageInformation) {
            $record = $this->recordFactory->createResolvedRecordFromDatabaseRow('pages', $record->getPageRecord());
        }

        if (!$record instanceof RecordInterface) {
            throw new \InvalidArgumentException('The record argument must be an instance of ' . PageInformation::class . ' or ' . RecordInterface::class . '. Given: ' . get_debug_type($record), 1770539910);
        }

        $value = $record->get($field) ?? '';

        if (!is_string($value)) {
            $table = $record->getMainType();
            throw new \InvalidArgumentException('The value of the field "' . $table . '.' . $field . '" must be a string. Given: ' . get_debug_type($value), 1770321858);
        }

        $fieldSchema = $this->tcaSchema->get($record->getFullType())->getField($field);
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

        $table = $record->getMainType();
        throw new \InvalidArgumentException('The field "' . $table . '.' . $field . '" is not supported. Given: ' . get_debug_type($fieldSchema), 1770618219);
    }
}
