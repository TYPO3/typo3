<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers\Form;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper;

/**
 * View Helper for rendering Extension Manager Configuration Form
 * @internal
 */
class TypoScriptConstantsViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
{
    /**
     * @var array
     */
    public $viewHelperMapping = [
        'int' => 'renderIntegerField',
        'int+' => 'renderPositiveIntegerField',
        'integer' => 'renderIntegerField',
        'color' => 'renderColorPicker',
        'wrap' => 'renderWrapField',
        'offset' => 'renderOffsetField',
        'options' => 'renderOptionSelect',
        'boolean' => 'renderCheckbox',
        'user' => 'renderUserFunction',
        'small' => 'renderSmallTextField',
        'string' => 'renderTextField',
        'input' => 'renderTextField',  // only for backwards compatibility, many extensions depend on that
        'default' => 'renderTextField' // only for backwards compatibility, many extensions depend on that
    ];

    /**
     * @var string
     */
    public $tagName = 'input';

    /**
     * Initialize arguments of this view helper
     *
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('name', 'string', 'Name of input tag');
        $this->registerArgument('value', 'mixed', 'Value of input tag');
        $this->registerUniversalTagAttributes();
    }

    /**
     * Render
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
     * @return string the rendered tag
     */
    public function render(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration)
    {
        $input = '';
        if (isset($this->viewHelperMapping[$configuration->getType()]) && method_exists($this, $this->viewHelperMapping[$configuration->getType()])) {
            $input = $this->{$this->viewHelperMapping[$configuration->getType()]}($configuration);
        } else {
            $input = $this->{$this->viewHelperMapping['default']}($configuration);
        }

        return $input;
    }

    /**
     * Render field of type color picker
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
     * @return string
     */
    protected function renderColorPicker(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration)
    {
        $elementId = 'em-' . $configuration->getName();
        $elementName = $this->getName($configuration);

        // configure the field
        $this->tag->setTagName('input');
        $this->tag->addAttribute('type', 'text');
        $this->tag->addAttribute('id', $elementId);
        $this->tag->addAttribute('name', $elementName);
        $this->tag->addAttribute('data-formengine-input-name', $elementName);
        $this->tag->addAttribute('class', 'form-control');
        if ($configuration->getValue() !== null) {
            $this->tag->addAttribute('value', $configuration->getValue());
        }

        // configure colorpicker wizard
        $params = [
            'formName' => 'configurationform',
            'itemName' => $elementName,
        ];
        $onClick =
            'this.blur();' .
            'vHWin=window.open(' .
                GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('wizard_colorpicker', ['P' => $params])) . ' + \'&P[currentValue]=\' + encodeURIComponent(document.getElementById(' . GeneralUtility::quoteJSvalue($elementId) . ').value),' .
                '\'popUpem-' . GeneralUtility::shortmd5($elementName) . '\',' .
                '\'height=400,width=400,status=0,menubar=0,scrollbars=1\'' .
            ');' .
            'vHWin.focus();' .
            'return false;';

        // wrap the field
        $output = '<div class="form-wizards-wrap form-wizards-aside">'
                . '<div class="form-wizards-element">' . $this->tag->render() . '</div>'
                . '<div class="form-wizards-items"><a href="#" onClick="' . htmlspecialchars($onClick) . '" class="btn btn-default"><span class="t3-icon fa fa-eyedropper"></span></a></div>'
                . '</div>';

        return $output;
    }

    /**
     * Render field of type "offset"
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
     * @return string
     */
    protected function renderOffsetField(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration)
    {
        $this->tag->setTagName('input');
        $this->tag->addAttribute('type', 'text');
        $this->tag->addAttribute('id', 'em-' . $configuration->getName());
        $this->tag->addAttribute('name', $this->getName($configuration));
        $this->tag->addAttribute('class', 'form-control t3js-emconf-offset');
        if ($configuration->getValue() !== null) {
            $this->tag->addAttribute('value', $configuration->getValue());
        }
        return $this->tag->render();
    }

    /**
     * Render field of type "wrap"
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
     * @return string
     */
    protected function renderWrapField(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration)
    {
        $this->tag->setTagName('input');
        $this->tag->addAttribute('type', 'text');
        $this->tag->addAttribute('id', 'em-' . $configuration->getName());
        $this->tag->addAttribute('name', $this->getName($configuration));
        $this->tag->addAttribute('class', 'form-control t3js-emconf-wrap');
        if ($configuration->getValue() !== null) {
            $this->tag->addAttribute('value', $configuration->getValue());
        }
        return $this->tag->render();
    }

    /**
     * Render field of type "option"
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
     * @return string
     */
    protected function renderOptionSelect(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration)
    {
        $this->tag->setTagName('select');
        $this->tag->addAttribute('id', 'em-' . $configuration->getName());
        $this->tag->addAttribute('name', $this->getName($configuration));
        $this->tag->addAttribute('class', 'form-control');
        $optionValueArray = $configuration->getGeneric();
        $output = '';
        foreach ($optionValueArray as $label => $value) {
            $output .= '<option value="' . htmlspecialchars($value) . '"';
            if ($configuration->getValue() == $value) {
                $output .= ' selected="selected"';
            }
            $output .= '>' . $GLOBALS['LANG']->sL($label, true) . '</option>';
        }
        $this->tag->setContent($output);
        return $this->tag->render();
    }

    /**
     * Render field of type "int+"
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
     * @return string
     */
    protected function renderPositiveIntegerField(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration)
    {
        $this->tag->setTagName('input');
        $this->tag->addAttribute('type', 'number');
        $this->tag->addAttribute('id', 'em-' . $configuration->getName());
        $this->tag->addAttribute('name', $this->getName($configuration));
        $this->tag->addAttribute('class', 'form-control');
        $this->tag->addAttribute('min', '0');
        if ($configuration->getValue() !== null) {
            $this->tag->addAttribute('value', $configuration->getValue());
        }
        return $this->tag->render();
    }

    /**
     * Render field of type "integer"
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
     * @return string
     */
    protected function renderIntegerField(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration)
    {
        $this->tag->setTagName('input');
        $this->tag->addAttribute('type', 'number');
        $this->tag->addAttribute('id', 'em-' . $configuration->getName());
        $this->tag->addAttribute('name', $this->getName($configuration));
        $this->tag->addAttribute('class', 'form-control');
        if ($configuration->getValue() !== null) {
            $this->tag->addAttribute('value', $configuration->getValue());
        }
        return $this->tag->render();
    }

    /**
     * Render field of type "text"
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
     * @return string
     */
    protected function renderTextField(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration)
    {
        $this->tag->setTagName('input');
        $this->tag->addAttribute('type', 'text');
        $this->tag->addAttribute('id', 'em-' . $configuration->getName());
        $this->tag->addAttribute('name', $this->getName($configuration));
        $this->tag->addAttribute('class', 'form-control');
        if ($configuration->getValue() !== null) {
            $this->tag->addAttribute('value', $configuration->getValue());
        }
        return $this->tag->render();
    }

    /**
     * Render field of type "small text"
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
     * @return string
     */
    protected function renderSmallTextField(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration)
    {
        return $this->renderTextField($configuration);
    }

    /**
     * Render field of type "checkbox"
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
     * @return string
     */
    public function renderCheckbox(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration)
    {
        $this->tag->addAttribute('type', 'checkbox');
        $this->tag->addAttribute('name', $this->getName($configuration));
        $this->tag->addAttribute('value', 1);
        $this->tag->addAttribute('id', 'em-' . $configuration->getName());
        if ($configuration->getValue() == 1) {
            $this->tag->addAttribute('checked', 'checked');
        }
        $hiddenField = $this->renderHiddenFieldForEmptyValue($configuration);
        return '<div class="checkbox">' . $hiddenField . '<label>' . $this->tag->render() . '</label></div>';
    }

    /**
     * Render field of type "userFunc"
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
     * @return string
     */
    protected function renderUserFunction(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration)
    {
        $userFunction = $configuration->getGeneric();
        $userFunctionParams = [
            'fieldName' => $this->getName($configuration),
            'fieldValue' => $configuration->getValue(),
            'propertyName' => $configuration->getName()
        ];
        return \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($userFunction, $userFunctionParams, $this, '');
    }

    /**
     * Get Field Name
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
     * @return string
     */
    protected function getName(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration)
    {
        return 'tx_extensionmanager_tools_extensionmanagerextensionmanager[config][' . $configuration->getName() . '][value]';
    }

    /**
     * Render a hidden field for empty values
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
     * @return string
     */
    protected function renderHiddenFieldForEmptyValue($configuration)
    {
        $hiddenFieldNames = [];
        if ($this->viewHelperVariableContainer->exists(FormViewHelper::class, 'renderedHiddenFields')) {
            $hiddenFieldNames = $this->viewHelperVariableContainer->get(FormViewHelper::class, 'renderedHiddenFields');
        }
        $fieldName = $this->getName($configuration);
        if (substr($fieldName, -2) === '[]') {
            $fieldName = substr($fieldName, 0, -2);
        }
        if (!in_array($fieldName, $hiddenFieldNames)) {
            $hiddenFieldNames[] = $fieldName;
            $this->viewHelperVariableContainer->addOrUpdate(FormViewHelper::class, 'renderedHiddenFields', $hiddenFieldNames);
            return '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="0" />';
        }
        return '';
    }
}
