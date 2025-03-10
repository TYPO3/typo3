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

namespace TYPO3\CMS\Fluid\ViewHelpers\Form;

/**
 * ViewHelper which renders an `<input type="file">` file upload HTML form element.
 * Make sure to set the `enctype="multipart/form-data"` attribute on the surrounding form!
 *
 * ```
 *   <f:form.upload name="file" />
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-form-upload
 */
final class UploadViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'input';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this ViewHelper', false, 'f3-form-error');
    }

    public function render(): string
    {
        $multiple = isset($this->additionalArguments['multiple']);
        $name = $this->getName();
        $allowedFields = ['name', 'type', 'tmp_name', 'error', 'size'];
        foreach ($allowedFields as $fieldName) {
            if ($multiple) {
                $formTokenFieldName = sprintf('%s[*][%s]', $name, $fieldName);
            } else {
                $formTokenFieldName = $name . '[' . $fieldName . ']';
            }
            $this->registerFieldNameForFormTokenGeneration($formTokenFieldName);
        }
        $this->tag->addAttribute('type', 'file');

        if ($multiple) {
            $this->tag->addAttribute('name', $name . '[]');
        } else {
            $this->tag->addAttribute('name', $name);
        }

        $this->setErrorClassAttribute();
        return $this->tag->render();
    }
}
