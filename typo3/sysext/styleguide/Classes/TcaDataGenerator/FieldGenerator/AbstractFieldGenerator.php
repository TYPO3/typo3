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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGenerator;

/**
 * Implement some standard stuff
 *
 * @internal
 */
abstract class AbstractFieldGenerator
{
    /**
     * @var array If all of these settings are identical to given values, match is true
     */
    protected array $matchArray = [];

    /**
     * General match implementation checks input array against $this->matchArray.
     * If all keys and values of matchArray exist in $data and are identical, this generator matches.
     */
    public function match(array $data): bool
    {
        return $this->checkMatchArray($data, $this->matchArray);
    }

    /**
     * Recursive compare of $data with $matchArray.
     *
     * @param array $data Given data
     * @param array $matchArray Part to mach against
     */
    protected function checkMatchArray(array $data, array $matchArray): bool
    {
        $result = true;
        foreach ($matchArray as $name => $value) {
            if (isset($data[$name])) {
                if (is_array($value)) {
                    $result = $this->checkMatchArray($data[$name], $value);
                    if ($result === false) {
                        return false;
                    }
                } elseif ($data[$name] === $value) {
                    $result = (bool)($result & true);
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
        return $result;
    }
}
