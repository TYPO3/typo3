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

namespace {
    die('Access denied');
}

namespace TYPO3\CMS\Core\Database\Schema\Types {

    use Doctrine\DBAL\Platforms\AbstractPlatform;
    use Doctrine\DBAL\Types\Type;

    /**
     * Replaced with {@see \Doctrine\DBAL\Types\EnumType}.
     *
     * @deprecated since TYPO3 v13, will be removed in TYPO3 v14
     */
    class EnumType extends Type
    {
        public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
        {
            return '';
        }
    }
}
