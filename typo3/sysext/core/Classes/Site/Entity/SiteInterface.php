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

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
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
     * Returns an identifier for the site / configuration
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Returns the base URL
     *
     * @return UriInterface
     */
    public function getBase(): UriInterface;

    /**
     * Returns all available languages of this site visible in the frontend
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
     * Returns the first language that was configured. This is usually language=0
     *
     * @return SiteLanguage
     */
    public function getDefaultLanguage(): SiteLanguage;

    /**
     * Fetch the available languages for a specific backend user, used in various places in Backend and Frontend
     * when a Backend User is authenticated.
     *
     * @param BackendUserAuthentication $user the authenticated backend user to check access rights
     * @param bool $includeAllLanguagesFlag whether "-1" should be included in the values or not.
     * @param int $pageId usually used for resolving additional information from PageTS, only used for pseudo-sites. uid of the default language row!
     * @return SiteLanguage[]
     */
    public function getAvailableLanguages(BackendUserAuthentication $user, bool $includeAllLanguagesFlag = false, int $pageId = null): array;

    /**
     * Returns a ready-to-use error handler, to be used within the ErrorController
     *
     * @param int $statusCode
     * @return PageErrorHandlerInterface
     * @throws PageErrorHandlerNotConfiguredException
     */
    public function getErrorHandler(int $statusCode): PageErrorHandlerInterface;
}
