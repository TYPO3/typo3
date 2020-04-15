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

namespace TYPO3\CMS\Backend\Module;

use TYPO3\CMS\Backend\Domain\Model\Module\BackendModule;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Model for the module storage
 */
class ModuleStorage implements SingletonInterface
{
    /**
     * @var \SplObjectStorage
     */
    protected $entries;

    /**
     * construct
     */
    public function __construct()
    {
        $this->entries = new \SplObjectStorage();
    }

    /**
     * Set Entries
     *
     * @param \SplObjectStorage $entries
     */
    public function setEntries($entries)
    {
        $this->entries = $entries;
    }

    /**
     * Get Entries
     *
     * @return \SplObjectStorage
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * Attach Entry
     *
     * @param BackendModule $entry
     */
    public function attachEntry(BackendModule $entry)
    {
        $this->entries->attach($entry);
    }
}
