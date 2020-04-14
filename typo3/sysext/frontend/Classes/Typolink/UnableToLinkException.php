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

namespace TYPO3\CMS\Frontend\Typolink;

use TYPO3\CMS\Frontend\Exception;

/**
 * Exception which is thrown when a link could not be set
 */
class UnableToLinkException extends Exception
{
    /**
     * @var string the text which should have gone inside the
     */
    protected $linkText;

    /**
     * Constructor the exception. With an additional parameter for the link text
     *
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code.
     * @param \Throwable $previous [optional] The previous throwable used for the exception chaining.
     * @param string $linkText [optional]
     */
    public function __construct($message = '', $code = 0, \Throwable $previous = null, $linkText = '')
    {
        parent::__construct($message, $code, $previous);
        $this->linkText = $linkText;
    }

    /**
     * Returns the link text when the link could not been set
     *
     * @return string
     */
    public function getLinkText(): string
    {
        return $this->linkText;
    }
}
