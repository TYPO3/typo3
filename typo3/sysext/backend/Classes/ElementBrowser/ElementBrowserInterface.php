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

namespace TYPO3\CMS\Backend\ElementBrowser;

/**
 * Element browsers are modals rendered when records are attached to FormEngine elements.
 * Core usages:
 * * Managing TCA type=file relations
 * * Managing FAL folder relations a TCA type=folder
 * * Managing various target relations of a TCA type=group
 */
interface ElementBrowserInterface
{
    /**
     * Returns the unique identifier of the element browser
     */
    public function getIdentifier(): string;

    /**
     * @return string HTML content
     */
    public function render();

    /**
     * Session data for this class can be set from outside with this method.
     *
     * @param mixed[] $data Session data array
     * @return array[] Session data and boolean which indicates that data needs to be stored in session because it's changed
     */
    public function processSessionData($data);
}
