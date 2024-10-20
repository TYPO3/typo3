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
final class ButtonViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'button';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('type', 'string', 'Specifies the type of button (e.g. "button", "reset" or "submit")', false, 'submit');
    }

    public function render(): string
    {
        $type = $this->arguments['type'];
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);

        $this->tag->addAttribute('type', $type);
        $this->tag->addAttribute('name', $name);
        $this->tag->addAttribute('value', $this->getValueAttribute());
        $this->tag->setContent((string)$this->renderChildren());
        $this->tag->forceClosingTag(true);

        return $this->tag->render();
    }
}
