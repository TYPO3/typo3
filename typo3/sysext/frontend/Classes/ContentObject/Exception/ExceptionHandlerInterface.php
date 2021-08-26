<?php

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

namespace TYPO3\CMS\Frontend\ContentObject\Exception;

use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;

/**
 * Interface ExceptionHandlerInterface
 */
interface ExceptionHandlerInterface
{
    /**
     * Handles exceptions thrown during rendering of content objects
     * The handler can decide whether to re-throw the exception or
     * return a nice error message for production context.
     *
     * @param \Exception $exception
     * @param AbstractContentObject $contentObject
     * @param array $contentObjectConfiguration
     * @return string
     */
    public function handle(\Exception $exception, AbstractContentObject $contentObject = null, $contentObjectConfiguration = []);

    /**
     * @todo Will be activated in TYPO3 v12
     *
     * Used to pass the TypoScript configuration to the exception handler
     */
    //public function setConfiguration(array $configuration): void;
}
