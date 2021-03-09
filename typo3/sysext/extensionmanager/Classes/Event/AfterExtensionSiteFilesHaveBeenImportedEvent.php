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

namespace TYPO3\CMS\Extensionmanager\Event;

/**
 * Event that is triggered after a package has imported its site configurations (from Initialisation/Site)
 */
final class AfterExtensionSiteFilesHaveBeenImportedEvent
{

    /**
     * @var string
     *
     * extension that imported the site configuration
     */
    private string $packageKey;

    /**
     * @var array list of site identifiers that were imported
     *
     * use as
     * foreach ($siteIdentifierList as $siteIdentifier) {
     *   $configuration = $siteConfiguration->load($siteIdentifier);
     *   // do things
     * }
     */
    private array $siteIdentifierList;

    public function __construct(string $packageKey, array $newSiteIdentifierList)
    {
        $this->packageKey = $packageKey;
        $this->siteIdentifierList = $newSiteIdentifierList;
    }

    public function getPackageKey(): string
    {
        return $this->packageKey;
    }

    /**
     * @return array
     */
    public function getSiteIdentifierList(): array
    {
        return $this->siteIdentifierList;
    }
}
