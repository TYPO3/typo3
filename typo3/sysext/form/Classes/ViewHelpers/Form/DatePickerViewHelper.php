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

/*
 * Inspired by and partially taken from the Neos.Form package (www.neos.io)
 */

namespace TYPO3\CMS\Form\ViewHelpers\Form;

use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\ViewHelpers\RenderRenderableViewHelper;

/**
 * Display a jQuery date picker.
 *
 * Note: Requires jQuery UI to be included on the page.
 *
 * Scope: frontend
 */
class DatePickerViewHelper extends AbstractFormFieldViewHelper
{

    /**
     * @var string
     */
    protected $tagName = 'input';

    /**
     * @var \TYPO3\CMS\Extbase\Property\PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @param \TYPO3\CMS\Extbase\Property\PropertyMapper $propertyMapper
     * @internal
     */
    public function injectPropertyMapper(PropertyMapper $propertyMapper)
    {
        $this->propertyMapper = $propertyMapper;
    }

    /**
     * Initialize the arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerTagAttribute('size', 'int', 'The size of the input field');
        $this->registerTagAttribute('placeholder', 'string', 'Specifies a short hint that describes the expected value of an input element');
        $this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this ViewHelper', false, 'f3-form-error');
        $this->registerArgument('initialDate', 'string', 'Initial date (@see http://www.php.net/manual/en/datetime.formats.php for supported formats)');
        $this->registerArgument('enableDatePicker', 'bool', 'Enable the Datepicker', false, true);
        $this->registerArgument('previewMode', 'bool', 'Preview mde flag', true, false);
        $this->registerArgument('dateFormat', 'string', 'The date format', false, 'Y-m-d');
        // use the default value if custom templates have not yet adapted this property
        $this->registerArgument('datePickerInitializationJavaScriptFile', 'string', 'The JavaScript file to initialize the date picker', false, 'EXT:form/Resources/Public/JavaScript/Frontend/DatePicker.js');
        $this->registerUniversalTagAttributes();
    }

    /**
     * Renders the text field, hidden field and required javascript
     *
     * @return string
     */
    public function render()
    {
        $enableDatePicker = $this->arguments['enableDatePicker'];
        $dateFormat = $this->arguments['dateFormat'];
        $previewMode = (bool)$this->arguments['previewMode'];
        $placeholder = $this->arguments['additionalAttributes']['placeholder'] ?? $this->arguments['placeholder'];

        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);

        $this->tag->addAttribute('type', 'text');
        $this->tag->addAttribute('name', $name . '[date]');

        if ($this->hasArgument('id')) {
            $id = $this->arguments['id'];
        } else {
            $id = 'field' . md5(StringUtility::getUniqueId());
        }

        if (empty($placeholder)) {
            $this->tag->addAttribute('placeholder', $dateFormat);
        }

        if ($enableDatePicker) {
            $this->tag->addAttribute('readonly', 'readonly');
            if (!$previewMode) {
                $datePickerDateFormat = $this->convertDateFormatToDatePickerFormat($dateFormat);
                $this->tag->addAttribute('data-format', $datePickerDateFormat);
                $this->tag->addAttribute('data-t3-form-datepicker', '');
                if (!empty($this->arguments['datePickerInitializationJavaScriptFile'])) {
                    GeneralUtility::makeInstance(AssetCollector::class)
                        ->addJavaScript(
                            't3-form-datepicker',
                            $this->arguments['datePickerInitializationJavaScriptFile'],
                            []
                        );
                }
            }
        }
        $date = $this->getSelectedDate();
        if ($date !== null) {
            $this->tag->addAttribute('value', $date->format($dateFormat));
        }

        $this->tag->addAttribute('id', $id);

        $this->setErrorClassAttribute();
        $content = '';
        $content .= $this->tag->render();
        $content .= '<input type="hidden" name="' . htmlspecialchars($name) . '[dateFormat]" value="' . htmlspecialchars($dateFormat) . '" />';

        return $content;
    }

    /**
     * @return \DateTime|null
     */
    protected function getSelectedDate()
    {
        /** @var FormRuntime $formRuntime */
        $formRuntime = $this->renderingContext
            ->getViewHelperVariableContainer()
            ->get(RenderRenderableViewHelper::class, 'formRuntime');

        $date = $formRuntime[$this->arguments['property']];
        if ($date instanceof \DateTime) {
            return $date;
        }
        if ($date !== null) {
            $date = $this->propertyMapper->convert($date, \DateTime::class);
            if (!$date instanceof \DateTime) {
                return null;
            }
            return $date;
        }
        if ($this->hasArgument('initialDate')) {
            return new \DateTime($this->arguments['initialDate']);
        }

        return null;
    }

    /**
     * @param string $dateFormat
     * @return string
     */
    protected function convertDateFormatToDatePickerFormat(string $dateFormat): string
    {
        $replacements = [
            'd' => 'dd',
            'D' => 'D',
            'j' => 'o',
            'l' => 'DD',

            'F' => 'MM',
            'm' => 'mm',
            'M' => 'M',
            'n' => 'm',

            'Y' => 'yy',
            'y' => 'y',
        ];
        return strtr($dateFormat, $replacements);
    }
}
