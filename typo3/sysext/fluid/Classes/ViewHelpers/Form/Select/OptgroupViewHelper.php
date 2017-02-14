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
 * Adds custom `<optgroup>` tags inside an `<f:form.select>`,
 * supports further child `<f:form.select.option>` tags.
 *
 * @api
 */
class OptgroupViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'optgroup';

    /**
     * Initialize additional arguments available for this tag view helper.
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
            $this->tag->addAttributes('disabled', 'disabled');
        } else {
            $this->tag->removeAttribute('disabled');
        }

        $this->tag->setContent($this->renderChildren());
        return $this->tag->render();
    }
}
