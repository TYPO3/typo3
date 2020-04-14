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

use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

/**
 * Event that is triggered after a package has imported the database file shipped within "ext_tables_static+adt.sql"
 */
final class AfterExtensionStaticDatabaseContentHasBeenImportedEvent
{
    /**
     * @var string
     */
    private $packageKey;

    /**
     * @var string
     */
    private $sqlFileName;

    /**
     * @var InstallUtility
     */
    private $emitter;

    public function __construct(string $packageKey, string $sqlFileName, InstallUtility $emitter)
    {
        $this->packageKey = $packageKey;
        $this->sqlFileName = $sqlFileName;
        $this->emitter = $emitter;
    }

    public function getPackageKey(): string
    {
        return $this->packageKey;
    }

    public function getSqlFileName(): string
    {
        return $this->sqlFileName;
    }

    public function getEmitter(): InstallUtility
    {
        return $this->emitter;
    }
}
