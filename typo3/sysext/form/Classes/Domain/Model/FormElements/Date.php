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
        parent::initializeFormElement();
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
