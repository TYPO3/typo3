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

use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Update all pages which have set the shortcut mode "Parent of selected or current page" (PageRepository::SHORTCUT_MODE_PARENT_PAGE)
 * to remove a possibly selected page as this would cause a different behaviour of the shortcut now
 * since the selected page is now respected in this shortcut mode.
 */
class PageShortcutParentUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Update page shortcuts with shortcut type "Parent of selected or current page"';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        if ($this->isWizardDone()) {
            return false;
        }

        $pagesNeedingUpdate = $this->getUpdatablePages();
        if (empty($pagesNeedingUpdate)) {
            return false;
        }

        $description = 'There are some shortcut pages that need to updated in order to preserve their current behaviour.';

        return true;
    }

    /**
     * Get pages which need to be updated
     *
     * @return array|NULL
     */
    protected function getUpdatablePages()
    {
        return $this->getDatabaseConnection()->exec_SELECTgetRows('uid', 'pages', 'shortcut <> 0 AND shortcut_mode = ' . PageRepository::SHORTCUT_MODE_PARENT_PAGE);
    }

    /**
     * Performs the database update
     *
     * @param array &$databaseQueries Queries done in this update
     * @param mixed &$customMessages Custom messages
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessages)
    {
        $pagesNeedingUpdate = $this->getUpdatablePages();
        if (!empty($pagesNeedingUpdate)) {
            $uids = array_column($pagesNeedingUpdate, 'uid');
            $this->getDatabaseConnection()->exec_UPDATEquery(
                'pages',
                'uid IN (' . implode(',', $uids) . ')',
                [ 'shortcut' => 0 ]
            );
            $databaseQueries[] = $this->getDatabaseConnection()->debug_lastBuiltQuery;
        }

        $this->markWizardAsDone();
        return true;
    }
}
