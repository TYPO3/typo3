<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Form\Select;

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
 * Adds custom :html:`<optgroup>` tags inside an :ref:`<f:form.select> <typo3-fluid-form-select>`,
 * supports further child :ref:`<f:form.select.option> <typo3-fluid-form-select-option>` tags.
 */
class OptgroupViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'optgroup';

    /**
     * Initialize additional arguments available for this tag ViewHelper.
     */
    public function initializeArguments()
    {
        $this->registerUniversalTagAttributes();
        $this->registerArgument('additionalAttributes', 'array', 'Additional tag attributes. They will be added directly to the resulting HTML tag.');
        $this->registerArgument('data', 'array', 'Additional data-* attributes. They will each be added with a "data-" prefix.');
        $this->registerTagAttribute('label', 'string', 'Human-readable label property for the generated optgroup tag');
        $this->registerTagAttribute('disabled', 'boolean', 'If true, option group is rendered as disabled', false, false);
    }

    /**
     * @return string
     */
    public function render()
    {
        if ($this->arguments['disabled']) {
            $this->tag->addAttribute('disabled', 'disabled');
        } else {
            $this->tag->removeAttribute('disabled');
        }

        $this->tag->setContent($this->renderChildren());
        return $this->tag->render();
    }
}
