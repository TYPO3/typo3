<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Configuration;

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Convenience wrapper for backend user configuration
 *
 * @internal
 */
class BackendUserConfiguration
{
    /**
     * @var BackendUserAuthentication
     */
    protected $backendUser;

    /**
     * @param BackendUserAuthentication|null $backendUser
     */
    public function __construct(BackendUserAuthentication $backendUser = null)
    {
        $this->backendUser = $backendUser ?: $GLOBALS['BE_USER'];
    }

    /**
     * Returns a specific user setting
     *
     * @param string $key Identifier, allows also dotted notation for subarrays
     * @return mixed Value associated
     */
    public function get(string $key)
    {
        return (strpos($key, '.') !== false) ? $this->getFromDottedNotation($key) : $this->backendUser->uc[$key];
    }

    /**
     * Get all user settings
     *
     * @return mixed all values, usually a multi-dimensional array
     */
    public function getAll()
    {
        return $this->backendUser->uc;
    }

    /**
     * Sets user settings by key/value pair
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value): void
    {
        if (strpos($key, '.') !== false) {
            $this->setFromDottedNotation($key, $value);
        } else {
            $this->backendUser->uc[$key] = $value;
        }

        $this->backendUser->writeUC($this->backendUser->uc);
    }

    /**
     * Adds an value to an Comma-separated list
     * stored in $key of user settings
     *
     * @param string $key
     * @param mixed $value
     */
    public function addToList(string $key, $value): void
    {
        $list = $this->get($key);

        if (!isset($list)) {
            $list = $value;
        } elseif (!GeneralUtility::inList($list, $value)) {
            $list .= ',' . $value;
        }

        $this->set($key, $list);
    }

    /**
     * Removes an value from an Comma-separated list
     * stored $key of user settings
     *
     * @param string $key
     * @param mixed $value
     */
    public function removeFromList(string $key, $value): void
    {
        $list = $this->get($key);

        if (GeneralUtility::inList($list, $value)) {
            $list = GeneralUtility::trimExplode(',', $list, true);
            $list = ArrayUtility::removeArrayEntryByValue($list, $value);
            $this->set($key, implode(',', $list));
        }
    }

    /**
     * Resets the user settings to the default
     */
    public function clear(): void
    {
        $this->backendUser->resetUC();
    }

    /**
     * Unsets a key in user settings
     *
     * @param string $key
     */
    public function unsetOption(string $key): void
    {
        if (isset($this->backendUser->uc[$key])) {
            unset($this->backendUser->uc[$key]);
            $this->backendUser->writeUC($this->backendUser->uc);
        }
    }

    /**
     * Computes the subarray from dotted notation
     *
     * @param string $key Dotted notation of subkeys like moduleData.module1.general.checked
     * @return mixed value of the settings
     */
    protected function getFromDottedNotation(string $key)
    {
        $subkeys = GeneralUtility::trimExplode('.', $key);
        $configuration = $this->backendUser->uc;

        foreach ($subkeys as $subkey) {
            if (isset($configuration[$subkey])) {
                $configuration = &$configuration[$subkey];
            } else {
                $configuration = [];
                break;
            }
        }

        return $configuration;
    }

    /**
     * Sets the value of a key written in dotted notation
     *
     * @param string $key
     * @param mixed $value
     */
    protected function setFromDottedNotation(string $key, $value): void
    {
        $subkeys = GeneralUtility::trimExplode('.', $key, true);
        $lastKey = $subkeys[count($subkeys) - 1];
        $configuration = &$this->backendUser->uc;

        foreach ($subkeys as $subkey) {
            if ($subkey === $lastKey) {
                $configuration[$subkey] = $value;
            } else {
                $configuration = &$configuration[$subkey];
            }
        }
    }
}
