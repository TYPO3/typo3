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

namespace TYPO3\CMS\Core\Database\Driver;

use Doctrine\DBAL\Driver\PDO\Exception as PDOException;

if (PHP_VERSION_ID >= 80000) {
    trait PDOStatementImplementation
    {
        /**
         * {@inheritdoc}
         */
        public function fetchAll($mode = null, ...$args)
        {
            try {
                $records = parent::fetchAll($mode, ...$args);

                if ($records !== false) {
                    $records = array_map([$this, 'mapResourceToString'], $records);
                }

                return $records;
            } catch (\PDOException $exception) {
                throw new PDOException($exception);
            }
        }
    }
} else {
    trait PDOStatementImplementation
    {
        /**
         * {@inheritdoc}
         */
        public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null)
        {
            try {
                $records = parent::fetchAll($fetchMode, $fetchArgument, $ctorArgs);

                if ($records !== false) {
                    $records = array_map([$this, 'mapResourceToString'], $records);
                }

                return $records;
            } catch (\PDOException $exception) {
                throw new PDOException($exception);
            }
        }
    }
}
