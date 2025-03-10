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
 * ViewHelper which renders a form button.
 *
 * ```
 *   <f:form.button type="reset" disabled="disabled"
 *         name="buttonName" value="buttonValue"
 *         formmethod="post" formnovalidate="formnovalidate"
 *   >Cancel</f:form.button>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-form-button
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
