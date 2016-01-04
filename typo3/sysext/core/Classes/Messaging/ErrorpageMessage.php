<?php
namespace TYPO3\CMS\Core\Messaging;

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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * A class representing error messages shown on a page.
 * Classic Example: "No pages are found on rootlevel"
 */
class ErrorpageMessage extends AbstractStandaloneMessage
{
    /**
     * Constructor for an Error message
     *
     * @param string $message The error message
     * @param string $title Title of the message, can be empty
     * @param int $severity Optional severity, must be either of AbstractMessage::INFO or related constants
     */
    public function __construct($message = '', $title = '', $severity = AbstractMessage::ERROR)
    {
        $this->setHtmlTemplate(ExtensionManagementUtility::siteRelPath('t3skin') . 'templates/errorpage-message.html');
        parent::__construct($message, $title, $severity);
    }
}
