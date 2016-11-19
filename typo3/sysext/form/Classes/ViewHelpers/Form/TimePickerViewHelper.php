<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\ViewHelpers\Form;

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

use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;

/**
 * Displays two select-boxes for hour and minute selection.
 *
 * Scope: frontend
 * @api
 */
class TimePickerViewHelper extends AbstractFormFieldViewHelper
{

    /**
     * @var string
     */
    protected $tagName = 'select';

    /**
     * @var \TYPO3\CMS\Extbase\Property\PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @param \TYPO3\CMS\Extbase\Property\PropertyMapper $propertyMapper
     * @return void
     * @internal
     */
    public function injectPropertyMapper(\TYPO3\CMS\Extbase\Property\PropertyMapper $propertyMapper)
    {
        $this->propertyMapper = $propertyMapper;
    }

    /**
     * Initialize the arguments.
     *
     * @return void
     * @internal
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerTagAttribute('size', 'int', 'The size of the select field');
        $this->registerTagAttribute('placeholder', 'string', 'Specifies a short hint that describes the expected value of an input element');
        $this->registerTagAttribute('disabled', 'string', 'Specifies that the select element should be disabled when the page loads');
        $this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this view helper', false, 'f3-form-error');
        $this->registerArgument('initialDate', 'string', 'Initial time (@see http://www.php.net/manual/en/datetime.formats.php for supported formats)');
        $this->registerUniversalTagAttributes();
    }

    /**
     * Renders the select fields for hour & minute
     *
     * @return string
     * @api
     */
    public function render()
    {
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);

        $this->tag->addAttribute('name', $name . '[hour]');
        $date = $this->getSelectedDate();
        $this->setErrorClassAttribute();

        $content = '';
        $content .= $this->buildHourSelector($date);
        $content .= $this->buildMinuteSelector($date);
        return $content;
    }

    /**
     * @return \DateTime
     */
    protected function getSelectedDate(): \DateTime
    {
        $fluidFormRenderer = $this->viewHelperVariableContainer->getView();
        $formRuntime = $fluidFormRenderer->getFormRuntime();

        $date = $formRuntime[$this->arguments['property']];
        if ($date instanceof \DateTime) {
            return $date;
        }
        if ($date !== null) {
            $date = $this->propertyMapper->convert($date, 'DateTime');
            if (!$date instanceof \DateTime) {
                return null;
            }
            return $date;
        }
        if ($this->hasArgument('initialDate')) {
            return new \DateTime($this->arguments['initialDate']);
        }
    }

    /**
     * @param \DateTime $date
     * @return string
     */
    protected function buildHourSelector(\DateTime $date = null): string
    {
        $value = $date !== null ? $date->format('H') : null;
        $hourSelector = clone $this->tag;
        $hourSelector->addAttribute('name', sprintf('%s[hour]', $this->getName()));
        $options = '';
        foreach (range(0, 23) as $hour) {
            $hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $selected = $hour === $value ? ' selected="selected"' : '';
            $options .= '<option value="' . $hour . '"' . $selected . '>' . $hour . '</option>';
        }
        $hourSelector->setContent($options);
        return $hourSelector->render();
    }

    /**
     * @param \DateTime $date
     * @return string
     */
    protected function buildMinuteSelector(\DateTime $date = null): string
    {
        $value = $date !== null ? $date->format('i') : null;
        $minuteSelector = clone $this->tag;
        if ($this->hasArgument('id')) {
            $minuteSelector->addAttribute('id', $this->arguments['id'] . '-minute');
        }
        $minuteSelector->addAttribute('name', sprintf('%s[minute]', $this->getName()));
        $options = '';
        foreach (range(0, 59) as $minute) {
            $minute = str_pad($minute, 2, '0', STR_PAD_LEFT);
            $selected = $minute === $value ? ' selected="selected"' : '';
            $options .= '<option value="' . $minute . '"' . $selected . '>' . $minute . '</option>';
        }
        $minuteSelector->setContent($options);
        return $minuteSelector->render();
    }
}
