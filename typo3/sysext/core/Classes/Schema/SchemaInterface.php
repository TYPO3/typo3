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

namespace TYPO3\CMS\Core\Schema;

/**
 * A generic interface for any kind of schema
 *
 * @internal This is an experimental implementation and might change until TYPO3 v13 LTS
 */
interface SchemaInterface
{
    public function getName(): string;
    public static function __set_state(array $state): SchemaInterface;

}
