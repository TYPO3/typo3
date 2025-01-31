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

namespace TYPO3\CMS\Core\Schema\Field;

/**
 * @internal This is an experimental implementation and might change until TYPO3 v13 LTS
 */
final readonly class JsonFieldType extends AbstractFieldType
{
    public function getType(): string
    {
        return 'json';
    }

    public function isSearchable(): true
    {
        return true;
    }

}
