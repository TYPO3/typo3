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

/**
 * Migrate CTypes 'textmedia' to use 'assets' field instead of 'media'
 */
class MigrateMediaToAssetsForTextMediaCe extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Migrate CTypes textmedia database field "media" to "assets"';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        $updateNeeded = true;

        if ($this->isWizardDone()) {
            $updateNeeded = false;
        } else {
            // No need to join the sys_file_references table here as we can rely on the reference
            // counter to check if the wizards has any textmedia content elements to upgrade.
            $textmediaCount = $this->getDatabaseConnection()->exec_SELECTcountRows(
                'uid',
                'tt_content',
                'CType = \'textmedia\' AND media > 0'
            );

            if ($textmediaCount === 0) {
                $updateNeeded = false;
                $this->markWizardAsDone();
            }
        }

        $description = 'The extension "fluid_styled_content" is using a new database field for mediafile references. ' .
            'This update wizard migrates these old references to use the new database field.';

        return $updateNeeded;
    }

    /**
     * Performs the database update if old mediafile references are available
     *
     * @param array &$databaseQueries Queries done in this update
     * @param mixed &$customMessages Custom messages
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessages)
    {
        $databaseConnection = $this->getDatabaseConnection();

        // Update 'textmedia'
        $query = '
			UPDATE sys_file_reference
			LEFT JOIN tt_content
			ON sys_file_reference.uid_foreign = tt_content.uid
			AND sys_file_reference.tablenames =\'tt_content\'
			AND sys_file_reference.fieldname = \'media\'
			SET tt_content.assets = tt_content.media,
			tt_content.media = 0,
			sys_file_reference.fieldname = \'assets\'
			WHERE
			tt_content.CType = \'textmedia\'
			AND tt_content.media > 0
		';
        $databaseConnection->sql_query($query);

        // Store last executed query
        $databaseQueries[] = str_replace(chr(10), ' ', $query);
        // Check for errors
        if ($databaseConnection->sql_error()) {
            $customMessages = 'SQL-ERROR: ' . htmlspecialchars($databaseConnection->sql_error());
            return false;
        }

        $this->markWizardAsDone();

        return true;
    }
}
