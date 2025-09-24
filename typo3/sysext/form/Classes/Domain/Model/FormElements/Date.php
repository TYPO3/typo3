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

namespace TYPO3\CMS\Form\Domain\Model\FormElements;

use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;

/**
 * A date form element
 *
 * Scope: frontend
 */
class Date extends AbstractFormElement implements StringableFormElementInterface
{
    /**
     * Initializes the Form Element by setting the data type to "DateTime"
     * @internal
     */
    public function initializeFormElement()
    {
        $this->setDataType(\DateTime::class);
        /** @var \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration $propertyMappingConfiguration */
        $propertyMappingConfiguration = $this->getRootForm()->getProcessingRule($this->getIdentifier())->getPropertyMappingConfiguration();
        // @see https://www.w3.org/TR/2011/WD-html-markup-20110405/input.date.html#input.date.attrs.value
        // 'Y-m-d' = https://tools.ietf.org/html/rfc3339#section-5.6 -> full-date
        $propertyMappingConfiguration->setTypeConverterOption(DateTimeConverter::class, DateTimeConverter::CONFIGURATION_DATE_FORMAT, 'Y-m-d');
    }

    /**
     * @param \DateTime $value
     */
    public function valueToString($value): string
    {
        $dateFormat = $this->properties['displayFormat'] ?? 'Y-m-d';

        return $value->format($dateFormat);
    }
}
