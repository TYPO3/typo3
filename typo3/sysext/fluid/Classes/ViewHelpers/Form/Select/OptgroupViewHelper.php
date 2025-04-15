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

namespace TYPO3\CMS\Fluid\ViewHelpers\Form\Select;

use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;

/**
 * ViewHelper for adding custom `<optgroup>` tags inside a `<f:form.select>`,
 * supports further child `<f:form.select.option>` tags.
 *
 * ```
 *   <f:form.select name="mySelect">
 *     <f:form.select.option value="1">Option one</f:form.select.option>
 *     <f:form.select.optgroup>
 *       <f:form.select.option value="3">Grouped option one</f:form.select.option>
 *       <f:form.select.option value="4">Grouped option two</f:form.select.option>
 *     </f:form.select.optgroup>
 *   </f:form.select>>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-form-select-optgroup
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-form-select-option
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-form-select
 */
final class OptgroupViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'optgroup';

    public function initializeArguments(): void
    {
        $this->registerArgument('additionalAttributes', 'array', 'Additional tag attributes. They will be added directly to the resulting HTML tag.');
        $this->registerArgument('data', 'array', 'Additional data-* attributes. They will each be added with a "data-" prefix.');
        $this->registerArgument('disabled', 'boolean', 'If true, option group is rendered as disabled', false, false);
    }

    public function render(): string
    {
        if ($this->arguments['disabled']) {
            $this->tag->addAttribute('disabled', 'disabled');
        }

        $this->tag->setContent($this->renderChildren());
        return $this->tag->render();
    }
}
