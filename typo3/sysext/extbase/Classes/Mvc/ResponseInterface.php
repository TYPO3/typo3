<?php
namespace TYPO3\CMS\Extbase\Mvc;

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
 * A generic and very basic response implementation
 *
 * @api
 */
interface ResponseInterface
{
    /**
     * Overrides and sets the content of the response
     *
     * @param string $content The response content
     * @return void
     * @api
     */
    public function setContent($content);

    /**
     * Appends content to the already existing content.
     *
     * @param string $content More response content
     * @return void
     * @api
     */
    public function appendContent($content);

    /**
     * Returns the response content without sending it.
     *
     * @return string The response content
     * @api
     */
    public function getContent();

    /**
     * Returns the response content without sending it.
     *
     * @return string The response content
     * @api
     */
    public function shutdown();
}
