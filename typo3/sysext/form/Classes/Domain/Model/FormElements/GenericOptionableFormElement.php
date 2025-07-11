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

namespace TYPO3\CMS\Form\Domain\Model\FormElements;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Service\TranslationService;

class GenericOptionableFormElement extends GenericFormElement implements ProcessableValueFormElementInterface
{
    public function processElementValue(mixed $value, FormRuntime $formRuntime): mixed
    {
        $properties = $this->getProperties();
        $options = $properties['options'] ?? null;
        if (is_array($options)) {
            $options = GeneralUtility::makeInstance(TranslationService::class)->translateFormElementValue($this, ['options'], $formRuntime);
            if (is_array($value)) {
                return $this->mapValuesToOptions($value, $options);
            }
            return $this->mapValueToOption($value, $options);
        }
        return $value;
    }

    protected function mapValuesToOptions(array $value, array $options): array
    {
        $result = [];
        foreach ($value as $key) {
            $result[] = $this->mapValueToOption($key, $options);
        }
        return $result;
    }

    protected function mapValueToOption(mixed $value, array $options): mixed
    {
        return $options[$value] ?? $value;
    }
}
