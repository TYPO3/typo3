<?php
namespace TYPO3\CMS\Form\ViewHelpers;

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
 * The form wizard controller
 */
class SelectViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper
{
    /**
     * Render the option tags.
     *
     * @return array an associative array of options, key will be the value of the option tag
     */
    protected function getOptions()
    {
        if (!is_array($this->arguments['options']) && !$this->arguments['options'] instanceof \Traversable) {
            return [];
        }
        $options = [];
        $optionsArgument = $this->arguments['options'];
        foreach ($optionsArgument as $key => $value) {
            if (is_string($key)) {
                $options[$key]['disabled'] = $value['disabled'];
                $options[$key]['isOptgroup'] = true;
                $optGroupOptions = $value['options'];
                foreach ($optGroupOptions as $optionKey => $optionValue) {
                    $option = $this->getOption($optionKey, $optionValue);
                    $options[$key]['options'][key($option)] = current($option);
                }
            } else {
                $option = $this->getOption($key, $value);
                $options[key($option)] = current($option);
            }
        }

        if ($this->arguments['sortByOptionLabel']) {
            asort($options, SORT_LOCALE_STRING);
        }
        return $options;
    }

    /**
     * Build a option array.
     *
     * @param string $key
     * @param string $value
     * @return array an associative array of an option, key will be the value of the option tag
     */
    protected function getOption($key, $value)
    {
        $option = [];
        if (is_object($value) || is_array($value)) {
            if ($this->hasArgument('optionValueField')) {
                $key = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($value, $this->arguments['optionValueField']);
                if (is_object($key)) {
                    if (method_exists($key, '__toString')) {
                        $key = (string)$key;
                    } else {
                        throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('Identifying value for object of class "' . get_class($value) . '" was an object.', 1247827428);
                    }
                }
            // @todo use $this->persistenceManager->isNewObject() once it is implemented
            } elseif ($this->persistenceManager->getIdentifierByObject($value) !== null) {
                $key = $this->persistenceManager->getIdentifierByObject($value);
            } elseif (method_exists($value, '__toString')) {
                $key = (string)$value;
            } else {
                throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('No identifying value for object of class "' . get_class($value) . '" found.', 1247826696);
            }
            if ($this->hasArgument('optionLabelField')) {
                $value = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($value, $this->arguments['optionLabelField']);
                if (is_object($value)) {
                    if (method_exists($value, '__toString')) {
                        $value = (string)$value;
                    } else {
                        throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('Label value for object of class "' . get_class($value) . '" was an object without a __toString() method.', 1247827553);
                    }
                }
            } elseif (method_exists($value, '__toString')) {
                $value = (string)$value;
            // @todo use $this->persistenceManager->isNewObject() once it is implemented
            } elseif ($this->persistenceManager->getIdentifierByObject($value) !== null) {
                $value = $this->persistenceManager->getIdentifierByObject($value);
            }
        }
        $option[$key] = $value;
        return $option;
    }

    /**
     * Render the option tags.
     *
     * @param array $options the options for the form.
     * @return string rendered tags.
     */
    protected function renderOptionTags($options)
    {
        $output = '';
        if ($this->hasArgument('prependOptionLabel')) {
            $value = $this->hasArgument('prependOptionValue') ? $this->arguments['prependOptionValue'] : '';
            $label = $this->arguments['prependOptionLabel'];
            $output .= $this->renderOptionTag($value, $label, false) . LF;
        }
        foreach ($options as $value => $label) {
            if (
                isset($label['isOptgroup'])
                && $label['isOptgroup'] === true
            ) {
                $output .= '<optgroup label="' . htmlspecialchars($value) . '"';
                if ($label['disabled'] !== null) {
                    $output .= ' disabled="disabled"';
                }
                $output .= '>' . LF;
                foreach ($label['options'] as $optionValue => $optionLabel) {
                    $isSelected = $this->isSelected($optionValue);
                    $output .= $this->renderOptionTag($optionValue, $optionLabel, $isSelected) . LF;
                }
                $output .= ' </optgroup>' . LF;
            } else {
                $isSelected = $this->isSelected($value);
                $output .= $this->renderOptionTag($value, $label, $isSelected) . LF;
            }
        }
        return $output;
    }
}
