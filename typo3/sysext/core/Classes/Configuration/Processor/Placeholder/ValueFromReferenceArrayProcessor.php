<?php

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

namespace TYPO3\CMS\Core\Configuration\Processor\Placeholder;

/**
 * Returns the value for a placeholder as fetched from the referenceArray
 *
 * Class ValueFromReferenceArrayProcessor
 */
class ValueFromReferenceArrayProcessor implements PlaceholderProcessorInterface
{
    /**
     * @param string $placeholder
     * @param array $referenceArray
     * @return bool
     */
    public function canProcess(string $placeholder, array $referenceArray): bool
    {
        return !str_contains($placeholder, '(');
    }

    /**
     * Returns the value for a placeholder as fetched from the referenceArray
     *
     * @param string $value the string to search for
     * @param array $referenceArray the main configuration array where to look up the data
     *
     * @return array|mixed|string
     */
    public function process(string $value, array $referenceArray)
    {
        $parts = explode('.', $value);
        $referenceData = $referenceArray;
        foreach ($parts as $part) {
            if (isset($referenceData[$part])) {
                $referenceData = $referenceData[$part];
            } else {
                // return unsubstituted placeholder
                throw new \UnexpectedValueException('Value not found', 1581501216);
            }
        }
        return $referenceData;
    }
}
