<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\ViewHelpers\Form;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It originated from the Neos.Form package (www.neos.io)
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

use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\ViewHelpers\RenderRenderableViewHelper;

/**
 * Displays two select-boxes for hour and minute selection.
 *
 * Scope: frontend
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
     * @internal
     */
    public function injectPropertyMapper(\TYPO3\CMS\Extbase\Property\PropertyMapper $propertyMapper)
    {
        $this->propertyMapper = $propertyMapper;
    }

    /**
     * Initialize the arguments.
     *
     * @internal
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerTagAttribute('size', 'int', 'The size of the select field');
        $this->registerTagAttribute('placeholder', 'string', 'Specifies a short hint that describes the expected value of an input element');
        $this->registerTagAttribute('disabled', 'string', 'Specifies that the select element should be disabled when the page loads');
        $this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this ViewHelper', false, 'f3-form-error');
        $this->registerArgument('initialDate', 'string', 'Initial time (@see http://www.php.net/manual/en/datetime.formats.php for supported formats)');
        $this->registerArgument('timeType', 'string', '"hour" or "minute"');
        $this->registerUniversalTagAttributes();
    }

    /**
     * Renders the select fields for hour & minute
     *
     * @return string
     */
    public function render()
    {
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);
        $this->tag->addAttribute('name', $name . '[hour]');

        $date = $this->getSelectedDate();
        $this->setErrorClassAttribute();

        $content = '';

        if ($this->arguments['timeType'] === 'hour') {
            $content .= $this->buildHourSelector($date);
        } else {
            $content .= $this->buildMinuteSelector($date);
        }

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
            $hour = str_pad((string)$hour, 2, '0', STR_PAD_LEFT);
            $selected = $hour === $value ? ' selected="selected"' : '';
            $options .= '<option value="' . htmlspecialchars($hour) . '" ' . $selected . '>' . htmlspecialchars($hour) . '</option>';
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
            $minute = str_pad((string)$minute, 2, '0', STR_PAD_LEFT);
            $selected = $minute === $value ? ' selected="selected"' : '';
            $options .= '<option value="' . htmlspecialchars($minute) . '"' . $selected . '>' . htmlspecialchars($minute) . '</option>';
        }
        $minuteSelector->setContent($options);
        return $minuteSelector->render();
    }
}
