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

namespace TYPO3\CMS\Extbase\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class Validate
{
    /**
      * @param array<string, mixed> $options
      */
    public function __construct(
        public readonly string $validator,
        public readonly array $options = []
    ) {}

    public function __toString(): string
    {
        $strings = [];
        $strings[] = $this->validator;

        if (count($this->options) > 0) {
            $validatorOptionsStrings = [];
            foreach ($this->options as $optionKey => $optionValue) {
                $validatorOptionsStrings[] = $optionKey . '=' . $optionValue;
            }

            $strings[] = '(' . implode(', ', $validatorOptionsStrings) . ')';
        }

        return trim(implode(' ', $strings));
    }
}
