<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Form;

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

/**
 * Generates an :html:`<textarea>`.
 *
 * The value of the text area needs to be set via the ``value`` attribute, as with all other form ViewHelpers.
 *
 * Examples
 * ========
 *
 * Example::
 *
 *    <f:form.textarea name="myTextArea" value="This is shown inside the textarea" />
 *
 * Output::
 *
 *    <textarea name="myTextArea">This is shown inside the textarea</textarea>
 */
class TextareaViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'textarea';

    /**
     * Initialize the arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerTagAttribute('autofocus', 'string', 'Specifies that a text area should automatically get focus when the page loads');
        $this->registerTagAttribute('rows', 'int', 'The number of rows of a text area');
        $this->registerTagAttribute('cols', 'int', 'The number of columns of a text area');
        $this->registerTagAttribute('disabled', 'string', 'Specifies that the input element should be disabled when the page loads');
        $this->registerTagAttribute('placeholder', 'string', 'The placeholder of the textarea');
        $this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this ViewHelper', false, 'f3-form-error');
        $this->registerTagAttribute('readonly', 'string', 'The readonly attribute of the textarea', false);
        $this->registerArgument('required', 'bool', 'Specifies whether the textarea is required', false, false);
        $this->registerUniversalTagAttributes();
    }

    /**
     * Renders the textarea.
     *
     * @return string
     */
    public function render()
    {
        $required = $this->arguments['required'] ?? false;
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);
        $this->setRespectSubmittedDataValue(true);

        $this->tag->forceClosingTag(true);
        $this->tag->addAttribute('name', $name);
        if ($required === true) {
            $this->tag->addAttribute('required', 'required');
        }
        $this->tag->setContent(htmlspecialchars($this->getValueAttribute()));
        $this->addAdditionalIdentityPropertiesIfNeeded();
        $this->setErrorClassAttribute();

        return $this->tag->render();
    }
}
