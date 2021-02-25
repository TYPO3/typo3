<?php

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
 * Creates a button.
 *
 * Examples
 * ========
 *
 * Defaults::
 *
 *    <f:form.button>Send Mail</f:form.button>
 *
 * Output::
 *
 *    <button type="submit" name="" value="">Send Mail</button>
 *
 * Disabled cancel button with some HTML5 attributes::
 *
 *    <f:form.button type="reset" disabled="disabled"
 *        name="buttonName" value="buttonValue"
 *        formmethod="post" formnovalidate="formnovalidate"
 *    >
 *        Cancel
 *    </f:form.button>
 *
 * Output::
 *
 *    <button disabled="disabled" formmethod="post" formnovalidate="formnovalidate" type="reset" name="myForm[buttonName]" value="buttonValue">Cancel</button>
 */
class ButtonViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'button';

    /**
     * Initialize the arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerTagAttribute(
            'autofocus',
            'string',
            'Specifies that a button should automatically get focus when the page loads'
        );
        $this->registerTagAttribute(
            'disabled',
            'string',
            'Specifies that the input element should be disabled when the page loads'
        );
        $this->registerTagAttribute('form', 'string', 'Specifies one or more forms the button belongs to');
        $this->registerTagAttribute(
            'formaction',
            'string',
            'Specifies where to send the form-data when a form is submitted. Only for type="submit"'
        );
        $this->registerTagAttribute(
            'formenctype',
            'string',
            'Specifies how form-data should be encoded before sending it to a server. Only for type="submit" (e.g. "application/x-www-form-urlencoded", "multipart/form-data" or "text/plain")'
        );
        $this->registerTagAttribute(
            'formmethod',
            'string',
            'Specifies how to send the form-data (which HTTP method to use). Only for type="submit" (e.g. "get" or "post")'
        );
        $this->registerTagAttribute(
            'formnovalidate',
            'string',
            'Specifies that the form-data should not be validated on submission. Only for type="submit"'
        );
        $this->registerTagAttribute(
            'formtarget',
            'string',
            'Specifies where to display the response after submitting the form. Only for type="submit" (e.g. "_blank", "_self", "_parent", "_top", "framename")'
        );
        $this->registerUniversalTagAttributes();
        $this->registerArgument('type', 'string', 'Specifies the type of button (e.g. "button", "reset" or "submit")', false, 'submit');
    }

    /**
     * Renders the button.
     *
     * @return string
     */
    public function render()
    {
        $type = $this->arguments['type'];
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);

        $this->tag->addAttribute('type', $type);
        $this->tag->addAttribute('name', $name);
        $this->tag->addAttribute('value', $this->getValueAttribute());
        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);

        return $this->tag->render();
    }
}
