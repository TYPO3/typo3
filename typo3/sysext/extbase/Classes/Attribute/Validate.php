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

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Validate
{
    public readonly string $validator;

    /**
     * @var array<string, mixed>
     */
    public readonly array $options;

    public readonly string $param;

    /**
     * @param string|array{value?: non-empty-string, validator?: non-empty-string, options?: array<string, mixed>, param?: string} $validator
     * @param array<string, mixed> $options
     */
    public function __construct(
        // @todo Convert to string and use CPP in TYPO3 v15.0
        string|array $validator,
        array $options = [],
        string $param = '',
    ) {
        // @todo Remove with TYPO3 v15.0
        if (\is_array($validator)) {
            trigger_error(
                'Passing an array of configuration values to Extbase attributes will be removed in TYPO3 v15.0. ' .
                'Use explicit constructor parameters instead.',
                E_USER_DEPRECATED,
            );

            $values = $validator;

            $this->validator = $values['validator'] ?? $values['value'] ?? '';
            $this->options = $values['options'] ?? $options;
            $this->param = $values['param'] ?? $param;
        } else {
            $this->validator = $validator;
            $this->options = $options;
            $this->param = $param;
        }
    }

    public function __toString(): string
    {
        $strings = [];

        if ($this->param !== '') {
            $strings[] = $this->param;
        }

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
