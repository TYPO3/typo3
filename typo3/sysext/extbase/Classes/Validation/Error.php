<?php
namespace TYPO3\CMS\Extbase\Validation;

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
 * This object holds a validation error.
 */
class Error extends \TYPO3\CMS\Extbase\Error\Error
{
    /**
     * @var string
     */
    protected $message = 'Unknown validation error';

    /**
     * @var string
     */
    protected $code = 1201447005;
}
