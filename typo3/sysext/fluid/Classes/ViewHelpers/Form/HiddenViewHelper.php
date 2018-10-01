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
 * Renders an <input type="hidden" ...> tag.
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:form.hidden name="myHiddenValue" value="42" />
 * </code>
 * <output>
 * <input type="hidden" name="myHiddenValue" value="42" />
 * </output>
 *
 * You can also use the "property" attribute if you have bound an object to the form.
 * See <f:form> for more documentation.
 */
class HiddenViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'input';

    /**
     * Initialize the arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
    }

    /**
     * Renders the hidden field.
     *
     * @return string
     */
    public function render()
    {
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);
        $this->setRespectSubmittedDataValue(true);

        $this->tag->addAttribute('type', 'hidden');
        $this->tag->addAttribute('name', $name);
        $this->tag->addAttribute('value', $this->getValueAttribute());

        return $this->tag->render();
    }
}
