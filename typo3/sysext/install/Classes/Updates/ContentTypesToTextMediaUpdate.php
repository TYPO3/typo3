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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Migrate CTypes 'text', 'image' and 'textpic' to 'textmedia' for extension 'frontend'
 */
class ContentTypesToTextMediaUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Migrate CTypes text, image and textpic to textmedia and move file relations from "image" to "asset_references"';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        $updateNeeded = true;

        if (
            !ExtensionManagementUtility::isLoaded('fluid_styled_content')
            || ExtensionManagementUtility::isLoaded('css_styled_content')
            || $this->isWizardDone()
        ) {
            $updateNeeded = false;
        } else {
            $nonTextmediaCount = $this->getDatabaseConnection()->exec_SELECTcountRows(
                'uid',
                'tt_content',
                'CType IN (\'text\', \'image\', \'textpic\')'
            );

            if ($nonTextmediaCount === 0) {
                $updateNeeded = false;
            }
        }

        $description = 'The extension "fluid_styled_content" is using a new CType, textmedia, ' .
            'which replaces the CTypes text, image and textpic. ' .
            'This update wizard migrates these old CTypes to the new one in the database. ' .
            'If backend groups have the explicit deny/allow flag set for any of the old CTypes, ' .
            'the according flag for the CType textmedia is set as well.';

        return $updateNeeded;
    }

    /**
     * Performs the database update if old CTypes are available
     *
     * @param array &$databaseQueries Queries done in this update
     * @param mixed &$customMessages Custom messages
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessages)
    {
        $databaseConnection = $this->getDatabaseConnection();
        $databaseConnection->store_lastBuiltQuery = true;

        // Update 'text' records
        $databaseConnection->exec_UPDATEquery(
            'tt_content',
            'tt_content.CType=' . $databaseConnection->fullQuoteStr('text', 'tt_content'),
            [
                'CType' => 'textmedia',
            ]
        );

        // Store last executed query
        $databaseQueries[] = str_replace(chr(10), ' ', $databaseConnection->debug_lastBuiltQuery);
        // Check for errors
        if ($databaseConnection->sql_error()) {
            $customMessages = 'SQL-ERROR: ' . htmlspecialchars($databaseConnection->sql_error());
            return false;
        }

        // Update 'textpic' and 'image' records
        $query = '
            UPDATE tt_content
            LEFT JOIN sys_file_reference
            ON sys_file_reference.uid_foreign=tt_content.uid
            AND sys_file_reference.tablenames=' . $databaseConnection->fullQuoteStr('tt_content', 'sys_file_reference')
            . ' AND sys_file_reference.fieldname=' . $databaseConnection->fullQuoteStr('image', 'sys_file_reference')
            . ' SET tt_content.CType=' . $databaseConnection->fullQuoteStr('textmedia', 'tt_content')
            . ', tt_content.assets=tt_content.image,
            tt_content.image=0,
            sys_file_reference.fieldname=' . $databaseConnection->fullQuoteStr('assets', 'tt_content')
            . ' WHERE
            tt_content.CType=' . $databaseConnection->fullQuoteStr('textpic', 'tt_content')
            . ' OR tt_content.CType=' . $databaseConnection->fullQuoteStr('image', 'tt_content');
        $databaseConnection->sql_query($query);

        // Store last executed query
        $databaseQueries[] = str_replace(chr(10), ' ', $query);
        // Check for errors
        if ($databaseConnection->sql_error()) {
            $customMessages = 'SQL-ERROR: ' . htmlspecialchars($databaseConnection->sql_error());
            return false;
        }

        // Update explicitDeny - ALLOW
        $databaseConnection->exec_UPDATEquery(
            'be_groups',
            '(explicit_allowdeny LIKE ' . $databaseConnection->fullQuoteStr('%' . $databaseConnection->escapeStrForLike('tt_content:CType:textpic:ALLOW', 'tt_content') . '%', 'tt_content')
                . ' OR explicit_allowdeny LIKE ' . $databaseConnection->fullQuoteStr('%' . $databaseConnection->escapeStrForLike('tt_content:CType:image:ALLOW', 'tt_content') . '%', 'tt_content')
                . ' OR explicit_allowdeny LIKE ' . $databaseConnection->fullQuoteStr('%' . $databaseConnection->escapeStrForLike('tt_content:CType:text:ALLOW', 'tt_content') . '%', 'tt_content')
                . ') AND explicit_allowdeny NOT LIKE ' . $databaseConnection->fullQuoteStr('%' . $databaseConnection->escapeStrForLike('tt_content:CType:textmedia:ALLOW', 'tt_content') . '%', 'tt_content'),
            [
                'explicit_allowdeny' => 'CONCAT(explicit_allowdeny,' . $databaseConnection->fullQuoteStr(',tt_content:CType:textmedia:ALLOW', 'tt_content') . ')',
            ],
            [
                'explicit_allowdeny',
            ]
        );

        // Store last executed query
        $databaseQueries[] = str_replace(chr(10), ' ', $databaseConnection->debug_lastBuiltQuery);

        // Update explicitDeny - DENY
        $databaseConnection->exec_UPDATEquery(
            'be_groups',
            '(explicit_allowdeny LIKE ' . $databaseConnection->fullQuoteStr('%' . $databaseConnection->escapeStrForLike('tt_content:CType:textpic:DENY', 'tt_content') . '%', 'tt_content')
                . ' OR explicit_allowdeny LIKE ' . $databaseConnection->fullQuoteStr('%' . $databaseConnection->escapeStrForLike('tt_content:CType:image:DENY', 'tt_content') . '%', 'tt_content')
                . ' OR explicit_allowdeny LIKE ' . $databaseConnection->fullQuoteStr('%' . $databaseConnection->escapeStrForLike('tt_content:CType:text:DENY', 'tt_content') . '%', 'tt_content')
                . ') AND explicit_allowdeny NOT LIKE ' . $databaseConnection->fullQuoteStr('%' . $databaseConnection->escapeStrForLike('tt_content:CType:textmedia:DENY', 'tt_content') . '%', 'tt_content'),
            [
                'explicit_allowdeny' => 'CONCAT(explicit_allowdeny,' . $databaseConnection->fullQuoteStr(',tt_content:CType:textmedia:DENY', 'tt_content') . ')',
            ],
            [
                'explicit_allowdeny',
            ]
        );

        // Store last executed query
        $databaseQueries[] = str_replace(chr(10), ' ', $databaseConnection->debug_lastBuiltQuery);

        $this->markWizardAsDone();

        return true;
    }
}
