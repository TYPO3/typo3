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

namespace TYPO3\CMS\Core\Tests\Unit\DataHandling\Fixtures;

use TYPO3\CMS\Core\DataHandling\DataHandler;

class UserOddNumberFilter
{
    public function filter(array $parameters, DataHandler $dataHandler)
    {
        if ($parameters['break'] ?? false) {
            return null;
        }
        $values = $parameters['values'];
        $values =  array_filter($values, static fn ($number) => $number % 2 !== 0);
        if (isset($parameters['exclude'])) {
            $values = array_filter($values, static fn ($number) => $number !== $parameters['exclude']);
        }
        return $values;
    }
}
