<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Site\Entity;

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

use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerNotConfiguredException;

interface SiteInterface
{
    /**
     * Returns the root page ID of this site
     *
     * @return int
     */
    public function getRootPageId(): int;

    /**
     * Returns all available languages of this site
     *
     * @return SiteLanguage[]
     */
    public function getLanguages(): array;

    /**
     * Returns a language of this site, given by the sys_language_uid
     *
     * @param int $languageId
     * @return SiteLanguage
     * @throws \InvalidArgumentException
     */
    public function getLanguageById(int $languageId): SiteLanguage;

    /**
     * Returns a ready-to-use error handler, to be used within the ErrorController
     *
     * @param int $statusCode
     * @return PageErrorHandlerInterface
     * @throws PageErrorHandlerNotConfiguredException
     */
    public function getErrorHandler(int $statusCode): PageErrorHandlerInterface;
}
