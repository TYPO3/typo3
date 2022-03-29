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

namespace TYPO3\CMS\Core\ViewHelpers\Form;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * ViewHelper for rendering
 * - extension configuration forms in install tool (Settings -> Extension Configuration
 * - site settings in Sites Module (site settings per site configuration)
 *
 * @internal
 */
final class TypoScriptConstantsViewHelper extends AbstractTagBasedViewHelper
{
    protected array $viewHelperMapping = [
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
        'default' => 'renderTextField', // only for backwards compatibility, many extensions depend on that
    ];

    /**
     * @var string
     */
    public $tagName = 'input';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('name', 'string', 'Name of input tag');
        $this->registerArgument('value', 'mixed', 'Value of input tag');
        $this->registerArgument(
            'configuration',
            'array',
            'The TypoScript constant configuration, e.g. labels, category, type and value.',
            true
        );
        $this->registerUniversalTagAttributes();
    }

    /**
     * Create a fresh instance of $this->tag each time this VH is called.
     */
    public function initialize(): void
    {
        $this->setTagBuilder(new TagBuilder($this->tagName));
        parent::initialize();
    }

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
     */
    protected function renderColorPicker(array $configuration): string
    {
        $elementName = $this->getFieldName($configuration);

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
                <input class="form-control t3js-color-input" type="text"
                  name="' . htmlspecialchars($elementName) . '" value="' . $this->tag->getAttribute('value') . '"/>
            </div>';

        return $output;
    }

    /**
     * Render field of type "offset"
     */
    protected function renderOffsetField(array $configuration): string
    {
        $this->tag->setTagName('input');
        $this->tag->addAttribute('type', 'text');
        $this->addIdAttribute($configuration);
        $this->tag->addAttribute('name', $this->getFieldName($configuration));
        $this->tag->addAttribute('class', 'form-control t3js-emconf-offset');
        if ($configuration['value'] !== null) {
            $this->tag->addAttribute('value', $configuration['value']);
        }
        return $this->tag->render();
    }

    /**
     * Render field of type "wrap"
     */
    protected function renderWrapField(array $configuration): string
    {
        $this->tag->setTagName('input');
        $this->tag->addAttribute('type', 'text');
        $this->addIdAttribute($configuration);
        $this->tag->addAttribute('name', $this->getFieldName($configuration));
        $this->tag->addAttribute('class', 'form-control t3js-emconf-wrap');
        if ($configuration['value'] !== null) {
            $this->tag->addAttribute('value', $configuration['value']);
        }
        return $this->tag->render();
    }

    /**
     * Render field of type "option"
     */
    protected function renderOptionSelect(array $configuration): string
    {
        $this->tag->setTagName('select');
        $this->addIdAttribute($configuration);
        $this->tag->addAttribute('name', $this->getFieldName($configuration));
        $this->tag->addAttribute('class', 'form-select');
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
     */
    protected function renderPositiveIntegerField(array $configuration): string
    {
        $this->tag->setTagName('input');
        $this->tag->addAttribute('type', 'number');
        $this->addIdAttribute($configuration);
        $this->tag->addAttribute('name', $this->getFieldName($configuration));
        $this->tag->addAttribute('class', 'form-control');
        $this->tag->addAttribute('min', '0');
        if ($configuration['value'] !== null) {
            $this->tag->addAttribute('value', $configuration['value']);
        }
        return $this->tag->render();
    }

    /**
     * Render field of type "integer"
     */
    protected function renderIntegerField(array $configuration): string
    {
        $this->tag->setTagName('input');
        $this->tag->addAttribute('type', 'number');
        $this->addIdAttribute($configuration);
        $this->tag->addAttribute('name', $this->getFieldName($configuration));
        $this->tag->addAttribute('class', 'form-control');
        if ($configuration['value'] !== null) {
            $this->tag->addAttribute('value', $configuration['value']);
        }
        return $this->tag->render();
    }

    /**
     * Render field of type "text"
     */
    protected function renderTextField(array $configuration): string
    {
        $this->tag->setTagName('input');
        $this->tag->addAttribute('type', 'text');
        $this->addIdAttribute($configuration);
        $this->tag->addAttribute('name', $this->getFieldName($configuration));
        $this->tag->addAttribute('class', 'form-control');
        if ($configuration['value'] !== null) {
            $this->tag->addAttribute('value', $configuration['value']);
        }
        return $this->tag->render();
    }

    /**
     * Render field of type "small text"
     */
    protected function renderSmallTextField(array $configuration): string
    {
        return $this->renderTextField($configuration);
    }

    /**
     * Render field of type "checkbox"
     */
    protected function renderCheckbox(array $configuration): string
    {
        $this->tag->addAttribute('type', 'checkbox');
        $this->tag->addAttribute('class', 'form-check-input');
        $this->tag->addAttribute('name', $this->getFieldName($configuration));
        $this->tag->addAttribute('value', 1);
        $this->addIdAttribute($configuration);
        if ($configuration['value'] == 1) {
            $this->tag->addAttribute('checked', 'checked');
        }
        $hiddenField = $this->renderHiddenFieldForEmptyValue($configuration);
        return '<div class="form-check">' . $hiddenField . $this->tag->render() . '</div>';
    }

    /**
     * Render field of type "userFunc"
     */
    protected function renderUserFunction(array $configuration): string
    {
        $userFunction = $configuration['generic'];
        $userFunctionParams = [
            'fieldName' => $this->getFieldName($configuration),
            'fieldValue' => $configuration['value'],
            'propertyName' => $configuration['name'],
        ];
        return (string)GeneralUtility::callUserFunction($userFunction, $userFunctionParams, $this);
    }

    /**
     * Get Field Name
     */
    protected function getFieldName(array $configuration): string
    {
        return $configuration['name'];
    }

    /**
     * Render a hidden field for empty values
     */
    protected function renderHiddenFieldForEmptyValue(array $configuration): string
    {
        $hiddenFieldNames = [];

        // check for already set hidden field within current extension
        $variableKey = 'renderedHiddenFields-' . $configuration['extensionKey'];
        if ($this->renderingContext->getViewHelperVariableContainer()->exists(FormViewHelper::class, $variableKey)) {
            $hiddenFieldNames = $this->renderingContext->getViewHelperVariableContainer()->get(FormViewHelper::class, $variableKey);
        }
        $fieldName = $this->getFieldName($configuration);
        if (substr($fieldName, -2) === '[]') {
            $fieldName = substr($fieldName, 0, -2);
        }
        if (!in_array($fieldName, $hiddenFieldNames)) {
            $hiddenFieldNames[] = $fieldName;
            $this->renderingContext->getViewHelperVariableContainer()->addOrUpdate(FormViewHelper::class, $variableKey, $hiddenFieldNames);
            return '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="0" />';
        }
        return '';
    }

    /**
     * @return LanguageService|null Null if we are in the install tool standalone mode @todo: still valid?
     */
    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Build and add id-attribute from $configuration
     */
    protected function addIdAttribute(array $configuration): void
    {
        $this->tag->addAttribute(
            'id',
            'em-' . $configuration['extensionKey'] . '-' . $this->getFieldName($configuration)
        );
    }
}
