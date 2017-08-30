<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\Exception;

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

use Throwable;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Install\Exception;

/**
 * An exception if the authentication is needed
 */
class AuthenticationRequiredException extends Exception
{
    /**
     * @var FlashMessage
     */
    protected $messageObject;

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param FlashMessage|null $messageObject
     */
    public function __construct($message = '', $code = 0, Throwable $previous = null, FlashMessage $messageObject = null)
    {
        parent::__construct($message, $code, $previous);
        $this->messageObject = $messageObject;
    }

    /**
     * @return FlashMessage
     */
    public function getMessageObject(): FlashMessage
    {
        return $this->messageObject;
    }
}
