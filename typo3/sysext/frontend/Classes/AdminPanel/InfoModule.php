<?php
declare(strict_types=1);

namespace TYPO3\CMS\Frontend\AdminPanel;

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

use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Admin Panel Info Module
 */
class InfoModule extends AbstractModule implements AdminPanelModuleInterface
{
    /**
     * Creates the content for the "info" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     * @see display()
     */
    public function getContent(): string
    {
        $output = [];
        $tsfe = $this->getTypoScriptFrontendController();
        if ($this->getBackendUser()->uc['TSFE_adminConfig']['display_info']) {
            $tableArr = [];
            if ($this->getBackendUser()->adminPanel->extGetFeAdminValue('cache', 'noCache')) {
                $theBytes = 0;
                $count = 0;
                if (!empty($tsfe->imagesOnPage)) {
                    $tableArr[] = [$this->extGetLL('info_imagesOnPage'), count($tsfe->imagesOnPage), true];
                    foreach ($GLOBALS['TSFE']->imagesOnPage as $file) {
                        $fs = @filesize($file);
                        $tableArr[] = [TAB . $file, GeneralUtility::formatSize($fs)];
                        $theBytes += $fs;
                        $count++;
                    }
                }
                // Add an empty line
                $tableArr[] = [$this->extGetLL('info_imagesSize'), GeneralUtility::formatSize($theBytes), true];
                $tableArr[] = [
                    $this->extGetLL('info_DocumentSize'),
                    GeneralUtility::formatSize(strlen($tsfe->content)),
                    true,
                ];
                $tableArr[] = ['', ''];
            }
            $tableArr[] = [$this->extGetLL('info_id'), $tsfe->id];
            $tableArr[] = [$this->extGetLL('info_type'), $tsfe->type];
            $tableArr[] = [$this->extGetLL('info_groupList'), $tsfe->gr_list];
            $tableArr[] = [
                $this->extGetLL('info_noCache'),
                $this->extGetLL('info_noCache_' . ($tsfe->no_cache ? 'no' : 'yes')),
            ];
            $tableArr[] = [$this->extGetLL('info_countUserInt'), count($tsfe->config['INTincScript'] ?? [])];

            if (!empty($tsfe->fe_user->user['uid'])) {
                $tableArr[] = [$this->extGetLL('info_feuserName'), htmlspecialchars($tsfe->fe_user->user['username'])];
                $tableArr[] = [$this->extGetLL('info_feuserId'), htmlspecialchars($tsfe->fe_user->user['uid'])];
            }

            $tableArr[] = [
                $this->extGetLL('info_totalParsetime'),
                $this->getTimeTracker()->getParseTime() . ' ms',
                true,
            ];
            $table = '';
            foreach ($tableArr as $key => $arr) {
                $label = (isset($arr[2]) ? '<strong>' . $arr[0] . '</strong>' : $arr[0]);
                $value = (string)$arr[1] !== '' ? $arr[1] : '';
                $table .= '
                    <tr>
                        <td>' . $label . '</td>
                        <td>' . htmlspecialchars((string)$value) . '</td>
                    </tr>';
            }

            $output[] = '<div class="typo3-adminPanel-table-overflow">';
            $output[] = '  <table class="typo3-adminPanel-table">';
            $output[] = '    ' . $table;
            $output[] = '  </table>';
            $output[] = '</div>';
        }

        return implode('', $output);
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return 'info';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return $this->extGetLL('info');
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker(): TimeTracker
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }
}
