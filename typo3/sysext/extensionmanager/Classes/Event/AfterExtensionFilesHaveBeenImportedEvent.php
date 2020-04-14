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
 * Event that is triggered after a package has imported all extension files (from Initialisation/Files)
 */
final class AfterExtensionFilesHaveBeenImportedEvent
{
    /**
     * @var string
     */
    private $packageKey;

    /**
     * @var string
     */
    private $destinationAbsolutePath;

    /**
     * @var InstallUtility
     */
    private $emitter;

    public function __construct(string $packageKey, string $destinationAbsolutePath, InstallUtility $emitter)
    {
        $this->packageKey = $packageKey;
        $this->destinationAbsolutePath = $destinationAbsolutePath;
        $this->emitter = $emitter;
    }

    public function getPackageKey(): string
    {
        return $this->packageKey;
    }

    public function getDestinationAbsolutePath(): string
    {
        return $this->destinationAbsolutePath;
    }

    public function getEmitter(): InstallUtility
    {
        return $this->emitter;
    }
}
