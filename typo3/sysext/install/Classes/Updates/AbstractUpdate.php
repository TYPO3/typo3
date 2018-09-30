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

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generic class that every update wizard class inherits from.
 * Used by the update wizard in the install tool.
 *
 * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
 */
abstract class AbstractUpdate implements UpgradeWizardInterface, ChattyInterface
{
    public function __construct()
    {
        trigger_error('Deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, use UpgradeWizardInterface directly. affected class: ' . get_class($this), E_USER_DEPRECATED);
    }
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
     * User input, set from outside
     *
     * @var string
     */
    public $userInput;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Returns the title attribute
     *
     * @deprecated Deprecated since TYPO3 v9
     * @return string The title of this update wizard
     */
    public function getTitle(): string
    {
        trigger_error(
            'Deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, use UpgradeWizardInterface directly. affected class: ' . get_class($this),
            E_USER_DEPRECATED
        );
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
        trigger_error(
            'Deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, use UpgradeWizardInterface directly. affected class: ' . get_class($this),
            E_USER_DEPRECATED
        );
        $this->title = $title;
    }

    /**
     * Returns the identifier of this class
     *
     * @return string The identifier of this update wizard
     */
    public function getIdentifier(): string
    {
        trigger_error(
            'Deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, use UpgradeWizardInterface directly. affected class: ' . get_class($this),
            E_USER_DEPRECATED
        );
        return $this->identifier ?? static::class;
    }

    /**
     * Sets the identifier attribute
     *
     * @param string $identifier The identifier of this update wizard
     */
    public function setIdentifier($identifier)
    {
        trigger_error(
            'Deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, use UpgradeWizardInterface directly. affected class: ' . get_class($this),
            E_USER_DEPRECATED
        );
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
        trigger_error(
            'Deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, use UpgradeWizardInterface directly. affected class: ' . get_class($this),
            E_USER_DEPRECATED
        );
        $explanation = '';
        $result = $this->checkForUpdate($explanation);
        return (bool)$result === true;
    }

    /**
     * Check if given table exists
     *
     * @param string $table
     * @return bool
     */
    protected function checkIfTableExists($table)
    {
        trigger_error(
            'Deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, use UpgradeWizardInterface directly. affected class: ' . get_class($this),
            E_USER_DEPRECATED
        );
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
        trigger_error(
            'Deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, use UpgradeWizardInterface directly. affected class: ' . get_class($this),
            E_USER_DEPRECATED
        );
        /** @var \TYPO3\CMS\Extensionmanager\Utility\InstallUtility $installUtility */
        $installUtility = GeneralUtility::makeInstance(
            \TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class
        );
        $installUtility->install($extensionKeys);
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
        trigger_error(
            'Deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, use UpgradeWizardInterface directly. affected class: ' . get_class($this),
            E_USER_DEPRECATED
        );
        GeneralUtility::makeInstance(Registry::class)->set('installUpdate', static::class, $confValue);
    }

    /**
     * Checks if this wizard has been "done" before
     *
     * @return bool TRUE if wizard has been done before, FALSE otherwise
     */
    protected function isWizardDone()
    {
        trigger_error(
            'Deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, use UpgradeWizardInterface directly. affected class: ' . get_class($this),
            E_USER_DEPRECATED
        );
        $wizardClassName = static::class;
        return GeneralUtility::makeInstance(Registry::class)->get('installUpdate', $wizardClassName, false);
    }

    /**
     * Return the description for this wizard
     *
     * @return string
     */
    public function getDescription(): string
    {
        trigger_error(
            'Deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, use UpgradeWizardInterface directly. affected class: ' . get_class($this),
            E_USER_DEPRECATED
        );
        return '';
    }

    /**
     * Execute the update
     * Called when a wizard reports that an update is necessary
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        trigger_error(
            'Deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, use UpgradeWizardInterface directly. affected class: ' . get_class($this),
            E_USER_DEPRECATED
        );
        $queries = [];
        $message = '';
        $result = $this->performUpdate($queries, $message);
        $this->output->write($message);
        return $result;
    }

    /**
     * Is an update necessary?
     * Is used to determine whether a wizard needs to be run.
     * Check if data for migration exists.
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        trigger_error(
            'Deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, use UpgradeWizardInterface directly. affected class: ' . get_class($this),
            E_USER_DEPRECATED
        );
        $description = '';
        $result = $this->checkForUpdate($description);
        $this->output->write($description);
        return $result;
    }

    /**
     * Returns an array of class names of Prerequisite classes
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     *
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        trigger_error(
            'Deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, use UpgradeWizardInterface directly. affected class: ' . get_class($this),
            E_USER_DEPRECATED
        );
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
     * Setter injection for output into upgrade wizards
     *
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        trigger_error(
            'Deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, use UpgradeWizardInterface directly. affected class: ' . get_class($this),
            E_USER_DEPRECATED
        );
        $this->output = $output;
    }
}
