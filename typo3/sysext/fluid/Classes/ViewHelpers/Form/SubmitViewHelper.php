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
 * Creates a submit button.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:form.submit value="Send Mail" />
 * </code>
 * <output>
 * <input type="submit" />
 * </output>
 *
 * <code title="Dummy content for template preview">
 * <f:submit name="mySubmit" value="Send Mail"><button>dummy button</button></f:submit>
 * </code>
 * <output>
 * <input type="submit" name="mySubmit" value="Send Mail" />
 * </output>
 */
class SubmitViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper
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
        $this->registerTagAttribute(
            'disabled',
            'string',
            'Specifies that the input element should be disabled when the page loads'
        );
        $this->registerUniversalTagAttributes();
    }

    /**
     * Renders the submit button.
     *
     * @return string
     */
    public function render()
    {
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);

        $this->tag->addAttribute('type', 'submit');
        $this->tag->addAttribute('value', $this->getValueAttribute());
        if (!empty($name)) {
            $this->tag->addAttribute('name', $name);
        }

        return $this->tag->render();
    }
}
