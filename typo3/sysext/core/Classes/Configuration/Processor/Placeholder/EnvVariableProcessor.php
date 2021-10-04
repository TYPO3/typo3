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
 * Return value from environment variable
 *
 * Environment variables may only contain word characters and underscores (a-zA-Z0-9_)
 * to be compatible to shell environments.
 */
class EnvVariableProcessor implements PlaceholderProcessorInterface
{
    public function canProcess(string $placeholder, array $referenceArray): bool
    {
        return is_string($placeholder) && (str_contains($placeholder, '%env('));
    }

    /**
     * @param string $value
     * @param array|null $referenceArray
     * @return mixed|string
     */
    public function process(string $value, array $referenceArray)
    {
        $envVar = getenv($value);
        if (!$envVar) {
            throw new \UnexpectedValueException('Value not found', 1581501124);
        }
        return $envVar;
    }
}
