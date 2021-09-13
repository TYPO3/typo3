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

namespace TYPO3\CMS\Backend\Form\Behavior;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Updates `TBE_EDITOR` value (the default action),
 * in case a particular field has been changed.
 */
class UpdateValueOnFieldChange implements OnFieldChangeInterface
{
    protected string $tableName;
    protected string $identifier;
    protected string $fieldName;
    protected string $elementName;

    public function __construct(string $tableName, string $identifier, string $fieldName, string $elementName)
    {
        $this->tableName = $tableName;
        $this->identifier = $identifier;
        $this->fieldName = $fieldName;
        $this->elementName = $elementName;
    }

    public function __toString(): string
    {
        return $this->generateInlineJavaScript();
    }

    public function withElementName(string $elementName): self
    {
        if ($this->elementName === $elementName) {
            return $this;
        }
        $target = clone $this;
        $target->elementName = $elementName;
        return $target;
    }

    public function toArray(): array
    {
        return [
            'name' => 'typo3-backend-form-update-value',
            'data' => [
                'tableName' => $this->tableName,
                'identifier' => $this->identifier,
                'fieldName' => $this->fieldName,
                'elementName' => $this->elementName,
            ],
        ];
    }

    protected function generateInlineJavaScript(): string
    {
        $args = array_map(
            [GeneralUtility::class, 'quoteJSvalue'],
            [$this->tableName, $this->identifier, $this->fieldName, $this->elementName]
        );
        return sprintf('TBE_EDITOR.fieldChanged(%s);', implode(',', $args));
    }
}
