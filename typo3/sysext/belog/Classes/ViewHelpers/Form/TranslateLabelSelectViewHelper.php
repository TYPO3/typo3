<?php
namespace TYPO3\CMS\Belog\ViewHelpers\Form;

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
 * Extends the usual select view helper, but additionally translates
 * the select option labels
 *
 * Example:
 * <belog:form.translateLabelSelect property="number" options="{settings.selectableNumberOfLogEntries}" optionLabelPrefix="numbers"
 *
 * Will lookup number.200 (or whatever optionValue is given) in locallang database
 * @internal
 */
class TranslateLabelSelectViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper
{
    /**
     * Initialize arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('optionLabelPrefix', 'string', 'Prefix for locallang lookup');
    }

    /**
     * Render the option tags.
     *
     * Extend the default handling by iterating over calculated options array and
     * try to translate the value
     *
     * @return array An associative array of options, key will be the value of the option tag
     */
    protected function getOptions()
    {
        $options = parent::getOptions();
        foreach ($options as $value => $label) {
            $options[$value] = $this->translateLabel($label);
        }
        return $options;
    }

    /**
     * Fetches the translation for a given label. If no translation is found, the label is returned unchanged.
     *
     * @param string $label The label to translate
     * @return string
     */
    protected function translateLabel($label)
    {
        if ($label === '') {
            return '';
        }
        $labelKey = $this->hasArgument('optionLabelPrefix') ? $this->arguments['optionLabelPrefix'] . $label : $label;
        $translatedLabel = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($labelKey, $this->controllerContext->getRequest()->getControllerExtensionName());
        return $translatedLabel ?: $label;
    }
}
