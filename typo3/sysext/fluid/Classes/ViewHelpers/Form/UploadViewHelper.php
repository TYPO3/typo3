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
 * A view helper which generates an <input type="file"> HTML element.
 * Make sure to set enctype="multipart/form-data" on the form!
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:form.upload name="file" />
 * </code>
 * <output>
 * <input type="file" name="file" />
 * </output>
 */
class UploadViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper
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
        $this->registerTagAttribute('disabled', 'string', 'Specifies that the input element should be disabled when the page loads');
        $this->registerTagAttribute('multiple', 'string', 'Specifies that the file input element should allow multiple selection of files');
        $this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this view helper', false, 'f3-form-error');
        $this->registerUniversalTagAttributes();
    }

    /**
     * Renders the upload field.
     *
     * @return string
     */
    public function render()
    {
        $name = $this->getName();
        $allowedFields = ['name', 'type', 'tmp_name', 'error', 'size'];
        foreach ($allowedFields as $fieldName) {
            $this->registerFieldNameForFormTokenGeneration($name . '[' . $fieldName . ']');
        }
        $this->tag->addAttribute('type', 'file');

        if (isset($this->arguments['multiple'])) {
            $this->tag->addAttribute('name', $name . '[]');
        } else {
            $this->tag->addAttribute('name', $name);
        }

        $this->setErrorClassAttribute();
        return $this->tag->render();
    }
}
