<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\ViewHelpers\Form;

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

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * ViewHelper for rendering extension configuration forms
 * @internal
 */
class TypoScriptConstantsViewHelper extends AbstractTagBasedViewHelper
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
        'input' => 'renderTextField', // only for backwards compatibility, many extensions depend on that
        'default' => 'renderTextField' // only for backwards compatibility, many extensions depend on that
    ];

    /**
     * @var string
     */
    public $tagName = 'input';

    /**
     * Initialize arguments of this ViewHelper
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('name', 'string', 'Name of input tag');
        $this->registerArgument('value', 'mixed', 'Value of input tag');
        $this->registerArgument('configuration', 'array', '', true);
        $this->registerUniversalTagAttributes();
    }

    /**
     * Create a fresh instance of $this->tag each time this VH is called.
     */
    public function initialize()
    {
        $this->setTagBuilder(new TagBuilder($this->tagName));
        parent::initialize();
    }

    /**
     * Render
     *
     * @return string the rendered tag
     */
    public function render(): string
    {
        /** @var array $configuration */
        $configuration = $this->arguments['configuration'];
        if (isset($this->viewHelperMapping[$configuration['type']]) && method_exists($this, $this->viewHelperMapping[$configuration['type']])) {
            $input = $this->{$this->viewHelperMapping[$configuration['type']]}($configuration);
        } else {
            $input = $this->{$this->viewHelperMapping['default']}($configuration);
        }

        return $input;
    }

    /**
     * Render field of type color picker
     *
     * @param array $configuration
     * @return string
     */
    protected function renderColorPicker(array $configuration): string
    {
        $elementName = $this->getName($configuration);

        // configure the field
        $this->tag->setTagName('input');
        $this->tag->addAttribute('type', 'text');
        $this->addIdAttribute($configuration);
        $this->tag->addAttribute('name', $elementName);
        $this->tag->addAttribute('data-formengine-input-name', $elementName);
        $this->tag->addAttribute('class', 'form-control');
        if ($configuration['value'] !== null) {
            $this->tag->addAttribute('value', $configuration['value']);
        }

        $output = '
            <div class="form-wizards-element">
                <input class="form-control t3js-color-input formengine-colorpickerelement t3js-color-picker" type="text"
                  name="' . htmlspecialchars($elementName) . '" value="' . $this->tag->getAttribute('value') . '"/>
                <script type="text/javascript">
                    require([\'TYPO3/CMS/Backend/ColorPicker\'], function(ColorPicker){ColorPicker.initialize()});
                </script>
            </div>';

        return $output;
    }

    /**
     * Render field of type "offset"
     *
     * @param array $configuration
     * @return string
     */
    protected function renderOffsetField(array $configuration): string
    {
        $this->tag->setTagName('input');
        $this->tag->addAttribute('type', 'text');
        $this->addIdAttribute($configuration);
        $this->tag->addAttribute('name', $this->getName($configuration));
        $this->tag->addAttribute('class', 'form-control t3js-emconf-offset');
        if ($configuration['value'] !== null) {
            $this->tag->addAttribute('value', $configuration['value']);
        }
        return $this->tag->render();
    }

    /**
     * Render field of type "wrap"
     *
     * @param array $configuration
     * @return string
     */
    protected function renderWrapField(array $configuration): string
    {
        $this->tag->setTagName('input');
        $this->tag->addAttribute('type', 'text');
        $this->addIdAttribute($configuration);
        $this->tag->addAttribute('name', $this->getName($configuration));
        $this->tag->addAttribute('class', 'form-control t3js-emconf-wrap');
        if ($configuration['value'] !== null) {
            $this->tag->addAttribute('value', $configuration['value']);
        }
        return $this->tag->render();
    }

    /**
     * Render field of type "option"
     *
     * @param array $configuration
     * @return string
     */
    protected function renderOptionSelect(array $configuration): string
    {
        $this->tag->setTagName('select');
        $this->addIdAttribute($configuration);
        $this->tag->addAttribute('name', $this->getName($configuration));
        $this->tag->addAttribute('class', 'form-control');
        $optionValueArray = $configuration['generic'];
        $output = '';
        $languageService = $this->getLanguageService();
        foreach ($optionValueArray as $label => $value) {
            $output .= '<option value="' . htmlspecialchars($value) . '"';
            if ($configuration['value'] == $value) {
                $output .= ' selected="selected"';
            }
            $output .= '>' . htmlspecialchars($languageService->sL($label)) . '</option>';
        }
        $this->tag->setContent($output);
        return $this->tag->render();
    }

    /**
     * Render field of type "int+"
     *
     * @param array $configuration
     * @return string
     */
    protected function renderPositiveIntegerField(array $configuration): string
    {
        $this->tag->setTagName('input');
        $this->tag->addAttribute('type', 'number');
        $this->addIdAttribute($configuration);
        $this->tag->addAttribute('name', $this->getName($configuration));
        $this->tag->addAttribute('class', 'form-control');
        $this->tag->addAttribute('min', '0');
        if ($configuration['value'] !== null) {
            $this->tag->addAttribute('value', $configuration['value']);
        }
        return $this->tag->render();
    }

    /**
     * Render field of type "integer"
     *
     * @param array $configuration
     * @return string
     */
    protected function renderIntegerField(array $configuration): string
    {
        $this->tag->setTagName('input');
        $this->tag->addAttribute('type', 'number');
        $this->addIdAttribute($configuration);
        $this->tag->addAttribute('name', $this->getName($configuration));
        $this->tag->addAttribute('class', 'form-control');
        if ($configuration['value'] !== null) {
            $this->tag->addAttribute('value', $configuration['value']);
        }
        return $this->tag->render();
    }

    /**
     * Render field of type "text"
     *
     * @param array $configuration
     * @return string
     */
    protected function renderTextField(array $configuration): string
    {
        $this->tag->setTagName('input');
        $this->tag->addAttribute('type', 'text');
        $this->addIdAttribute($configuration);
        $this->tag->addAttribute('name', $this->getName($configuration));
        $this->tag->addAttribute('class', 'form-control');
        if ($configuration['value'] !== null) {
            $this->tag->addAttribute('value', $configuration['value']);
        }
        return $this->tag->render();
    }

    /**
     * Render field of type "small text"
     *
     * @param array $configuration
     * @return string
     */
    protected function renderSmallTextField(array $configuration): string
    {
        return $this->renderTextField($configuration);
    }

    /**
     * Render field of type "checkbox"
     *
     * @param array $configuration
     * @return string
     */
    public function renderCheckbox(array $configuration): string
    {
        $this->tag->addAttribute('type', 'checkbox');
        $this->tag->addAttribute('name', $this->getName($configuration));
        $this->tag->addAttribute('value', 1);
        $this->addIdAttribute($configuration);
        if ($configuration['value'] == 1) {
            $this->tag->addAttribute('checked', 'checked');
        }
        $hiddenField = $this->renderHiddenFieldForEmptyValue($configuration);
        return '<div class="checkbox">' . $hiddenField . '<label>' . $this->tag->render() . '</label></div>';
    }

    /**
     * Render field of type "userFunc"
     *
     * @param array $configuration
     * @return string
     */
    protected function renderUserFunction(array $configuration): string
    {
        $userFunction = $configuration['generic'];
        $userFunctionParams = [
            'fieldName' => $this->getName($configuration),
            'fieldValue' => $configuration['value'],
            'propertyName' => $configuration['name']
        ];
        return (string)GeneralUtility::callUserFunction($userFunction, $userFunctionParams, $this);
    }

    /**
     * Get Field Name
     *
     * @param array $configuration
     * @return string
     */
    protected function getName(array $configuration): string
    {
        return $configuration['name'];
    }

    /**
     * Render a hidden field for empty values
     *
     * @param array $configuration
     * @return string
     */
    protected function renderHiddenFieldForEmptyValue(array $configuration): string
    {
        $hiddenFieldNames = [];

        // check for already set hidden field within current extension
        $variableKey = 'renderedHiddenFields-' . $configuration['extensionKey'];
        if ($this->viewHelperVariableContainer->exists(FormViewHelper::class, $variableKey)) {
            $hiddenFieldNames = $this->viewHelperVariableContainer->get(FormViewHelper::class, $variableKey);
        }
        $fieldName = $this->getName($configuration);
        if (substr($fieldName, -2) === '[]') {
            $fieldName = substr($fieldName, 0, -2);
        }
        if (!in_array($fieldName, $hiddenFieldNames)) {
            $hiddenFieldNames[] = $fieldName;
            $this->viewHelperVariableContainer->addOrUpdate(FormViewHelper::class, $variableKey, $hiddenFieldNames);
            return '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="0" />';
        }
        return '';
    }

    /**
     * @return LanguageService|null Returns null if we are in the install tool standalone mode
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Build and add id-attribute from $configuration
     *
     * @param array $configuration
     */
    protected function addIdAttribute(array $configuration): void
    {
        $this->tag->addAttribute(
            'id',
            'em-' . $configuration['extensionKey'] . '-' . $this->getName($configuration)
        );
    }
}
