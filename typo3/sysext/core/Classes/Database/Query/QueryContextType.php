<?php
declare (strict_types = 1);
namespace TYPO3\CMS\Core\Database\Query;

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

/**
 * Enumeration object for query context type
 *
 */
class QueryContextType extends \TYPO3\CMS\Core\Type\Enumeration
{
    const __default = self::AUTO;

    /**
     * Constants reflecting the query context type
     */
    const AUTO = 'AUTO';
    const NONE = 'NONE';
    const FRONTEND = 'FRONTEND';
    const BACKEND = 'BACKEND';

    /**
     * @param mixed $type
     */
    public function __construct($type = null)
    {
        if ($type !== null) {
            $type = strtoupper((string)$type);
        }

        parent::__construct($type);
    }
}
