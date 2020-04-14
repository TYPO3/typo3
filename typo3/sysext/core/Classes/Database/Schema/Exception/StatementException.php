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

namespace TYPO3\CMS\Core\Database\Schema\Exception;

/**
 * Class StatementException
 */
class StatementException extends \Exception
{
    /**
     * @param string $sql
     *
     * @return StatementException
     */
    public static function sqlError(string $sql): StatementException
    {
        return new self($sql, 1471504820);
    }

    /**
     * @param string $message
     * @param \Exception|null $previous
     *
     * @return StatementException
     */
    public static function syntaxError(string $message, \Exception $previous = null): StatementException
    {
        return new self('[SQL Error] ' . $message, 1471504821, $previous);
    }

    /**
     * @param string $message
     * @param \Exception|null $previous
     *
     * @return StatementException
     */
    public static function semanticalError(string $message, \Exception $previous = null): StatementException
    {
        return new self('[Semantical Error] ' . $message, 1471504822, $previous);
    }
}
