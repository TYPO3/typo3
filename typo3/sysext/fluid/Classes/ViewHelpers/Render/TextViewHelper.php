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

use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3Fluid\Fluid\Core\Parser\UnsafeHTML;
use TYPO3Fluid\Fluid\Core\Parser\UnsafeHTMLString;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class TextViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('record', PageInformation::class . '|' . RecordInterface::class, 'A Record API Object', true);
        $this->registerArgument('field', 'string', 'The field that should be rendered.', true);

        $this->registerArgument('allowNewlines', 'bool', 'Allows newLines and converts them to <br>', false, false);
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

        $allowNewlines = $this->arguments['allowNewlines'];

        if ($record instanceof PageInformation) {
            $value = $record->getPageRecord()[$field] ?? '';
        } else {
            $value = $record->get($field) ?? '';
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException('The value of the field "' . $field . '" must be a string. Given: ' . get_debug_type($value), 1770321858);
        }

        // We need to manually escape the output here to pass it to UnsafeHTMLString.
        $html = htmlspecialchars($value);

        if ($allowNewlines) {
            $html = nl2br($html);
        }
        return new UnsafeHTMLString($html);
    }
}
