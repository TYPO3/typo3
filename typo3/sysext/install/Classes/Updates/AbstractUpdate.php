<?php
namespace TYPO3\CMS\Install\Updates;

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

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Controller\Action\Tool\UpgradeWizard;

/**
 * Generic class that every update wizard class inherits from.
 * Used by the update wizard in the install tool.
 */
abstract class AbstractUpdate
{
    /**
     * The human-readable title of the upgrade wizard
     *
     * @var string
     */
    protected $title;

    /**
     * The update wizard identifier
     *
     * @var string
     */
    protected $identifier;

    /**
     * Parent object
     *
     * @var UpgradeWizard
     */
    public $pObj;

    /**
     * User input, set from outside
     *
     * @var string
     */
    public $userInput;

    /**
     * Current TYPO3 version number, set from outside
     * Version number coming from \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger()
     *
     * @var int
     */
    public $versionNumber;

    /**
     * Returns the title attribute
     *
     * @return string The title of this update wizard
     */
    public function getTitle()
    {
        if ($this->title) {
            return $this->title;
        }
        return $this->identifier;
    }

    /**
     * Sets the title attribute
     *
     * @param string $title The title of this update wizard
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns the identifier of this class
     *
     * @return string The identifier of this update wizard
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Sets the identifier attribute
     *
     * @param string $identifier The identifier of this update wizard
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Simple wrapper function that helps dealing with the compatibility
     * layer that some update wizards don't have a second parameter
     * thus, it evaluates everything already
     *
     * @return bool If the wizard should be shown at all on the overview page
     * @see checkForUpdate()
     */
    public function shouldRenderWizard()
    {
        $explanation = '';
        $result = $this->checkForUpdate($explanation);
        return (bool)$result === true;
    }

    /**
     * Simple wrapper function that helps to check whether (if)
     * this feature is cool if you want to tell the user that the update wizard
     * is working fine, just as output (useful for the character set / utf8 wizard)
     *
     * @return bool If the wizard should render the Next() button on the overview page
     * @see checkForUpdate()
     */
    public function shouldRenderNextButton()
    {
        $showUpdate = 0;
        $explanation = '';
        $result = $this->checkForUpdate($explanation, $showUpdate);
        return $showUpdate != 2 || $result;
    }

    /**
     * Check if given table exists
     *
     * @param string $table
     * @return bool
     */
    public function checkIfTableExists($table)
    {
        $tableExists = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table)
            ->getSchemaManager()
            ->tablesExist([$table]);

        return $tableExists;
    }

    /**
     * Checks whether updates are required.
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is required (TRUE) or not (FALSE)
     */
    abstract public function checkForUpdate(&$description);

    /**
     * Performs the accordant updates.
     *
     * @param array &$dbQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool Whether everything went smoothly or not
     */
    abstract public function performUpdate(array &$dbQueries, &$customMessage);

    /**
     * This method can be called to install extensions following all proper processes
     * (e.g. installing in extList, respecting priority, etc.)
     *
     * @param array $extensionKeys List of keys of extensions to install
     */
    protected function installExtensions(array $extensionKeys)
    {
        /** @var $installUtility \TYPO3\CMS\Extensionmanager\Utility\InstallUtility */
        $installUtility = GeneralUtility::makeInstance(
            \TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class
        );
        foreach ($extensionKeys as $extension) {
            $installUtility->install($extension);
        }
    }

    /**
     * Marks some wizard as being "seen" so that it not shown again.
     *
     * Writes the info in LocalConfiguration.php
     *
     * @param mixed $confValue The configuration is set to this value
     */
    protected function markWizardAsDone($confValue = 1)
    {
        GeneralUtility::makeInstance(Registry::class)->set('installUpdate', get_class($this), $confValue);
    }

    /**
     * Checks if this wizard has been "done" before
     *
     * @return bool TRUE if wizard has been done before, FALSE otherwise
     */
    protected function isWizardDone()
    {
        $wizardClassName = get_class($this);
        $done = GeneralUtility::makeInstance(Registry::class)->get('installUpdate', $wizardClassName, false);

        // Fall back in case the wizard for migration of "wizard done" flags to system registry was not run yet
        if (!$done) {
            try {
                GeneralUtility::makeInstance(ConfigurationManager::class)
                    ->getLocalConfigurationValueByPath('INSTALL/wizardDone/' . $wizardClassName);
                $done = true;
            } catch (\RuntimeException $e) {
            }
        }

        return $done;
    }
}
