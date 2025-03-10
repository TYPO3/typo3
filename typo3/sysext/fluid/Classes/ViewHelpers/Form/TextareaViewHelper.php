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
 * ViewHelper which renders a `<textarea>` large text input area inside a form.
 *
 * The value of the text area needs to be set via the `value` attribute, as with all other f:form ViewHelpers.
 *
 * ```
 *   <f:form.textarea name="myTextArea" value="This is shown inside the textarea" />
 *   <f:form.textarea property="myProperty" />
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-form-textarea
 */
final class TextareaViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'textarea';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this ViewHelper', false, 'f3-form-error');
        $this->registerArgument('required', 'bool', 'Specifies whether the textarea is required', false, false);
    }

    public function render(): string
    {
        $required = $this->arguments['required'];
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);
        $this->setRespectSubmittedDataValue(true);

        $this->tag->forceClosingTag(true);
        $this->tag->addAttribute('name', $name);
        if ($required === true) {
            $this->tag->addAttribute('required', 'required');
        }
        $this->tag->setContent(htmlspecialchars((string)$this->getValueAttribute()));
        $this->addAdditionalIdentityPropertiesIfNeeded();
        $this->setErrorClassAttribute();

        return $this->tag->render();
    }
}
