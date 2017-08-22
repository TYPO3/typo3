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
 * View Helper which creates a simple Password Text Box (<input type="password">).
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:form.password name="myPassword" />
 * </code>
 * <output>
 * <input type="password" name="myPassword" value="default value" />
 * </output>
 *
 * @api
 */
class PasswordViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'input';

    /**
     * Initialize the arguments.
     *
     * @api
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerTagAttribute(
            'disabled',
            'string',
            'Specifies that the input element should be disabled when the page loads'
        );
        $this->registerTagAttribute(
            'maxlength',
            'int',
            'The maxlength attribute of the input field (will not be validated)'
        );
        $this->registerTagAttribute('placeholder', 'string', 'The placeholder of the textfield');
        $this->registerTagAttribute('readonly', 'string', 'The readonly attribute of the input field');
        $this->registerTagAttribute('size', 'int', 'The size of the input field');
        $this->registerArgument(
            'errorClass',
            'string',
            'CSS class to set if there are errors for this view helper',
            false,
            'f3-form-error'
        );
        $this->registerUniversalTagAttributes();
    }

    /**
     * Renders the password input field.
     *
     * @return string
     * @api
     */
    public function render()
    {
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);
        $this->setRespectSubmittedDataValue(true);

        $this->tag->addAttribute('type', 'password');
        $this->tag->addAttribute('name', $name);
        $this->tag->addAttribute('value', $this->getValueAttribute());

        $this->addAdditionalIdentityPropertiesIfNeeded();
        $this->setErrorClassAttribute();

        return $this->tag->render();
    }
}
