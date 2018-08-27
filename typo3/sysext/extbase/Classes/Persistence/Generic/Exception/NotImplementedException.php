<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic\Exception;

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
 * An "NotImplementedException" exception
 */
class NotImplementedException extends \TYPO3\CMS\Extbase\Persistence\Exception
{
    /**
     * @param string $method
     * @param int $exceptionCode
     */
    public function __construct($method, int $exceptionCode = null)
    {
        parent::__construct(sprintf('Method %s is not supported by generic persistence"', $method), $exceptionCode ?? 1350213237);
    }
}
