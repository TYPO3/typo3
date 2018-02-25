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

/**
 * Admin Panel TypoScript Debug Module
 */
class TsDebugModule extends AbstractModule implements AdminPanelModuleInterface
{

    /**
     * Creates the content for the "tsdebug" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     */
    public function getContent(): string
    {
        $output = [];
        $beuser = $this->getBackendUser();
        if ($beuser->uc['TSFE_adminConfig']['display_tsdebug']) {
            $output[] = '<div class="typo3-adminPanel-form-group">';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_tree]" value="0" />';
            $output[] = '    <label for="tsdebug_tree">';
            $output[] = '      <input type="checkbox" id="tsdebug_tree" name="TSFE_ADMIN_PANEL[tsdebug_tree]" value="1"' .
                        ($this->getBackendUser()->uc['TSFE_adminConfig']['tsdebug_tree'] ? ' checked="checked"' : '') .
                        ' />';
            $output[] = '      ' . $this->extGetLL('tsdebug_tree');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayTimes]" value="0" />';
            $output[] = '    <label for="tsdebug_displayTimes">';
            $output[] = '      <input type="checkbox" id="tsdebug_displayTimes" name="TSFE_ADMIN_PANEL[tsdebug_displayTimes]" value="1"' .
                        ($this->getBackendUser(
                        )->uc['TSFE_adminConfig']['tsdebug_displayTimes'] ? ' checked="checked"' : '') .
                        ' />';
            $output[] = '      ' . $this->extGetLL('tsdebug_displayTimes');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayMessages]" value="0" />';
            $output[] = '    <label for="tsdebug_displayMessages">';
            $output[] = '      <input type="checkbox" id="tsdebug_displayMessages" name="TSFE_ADMIN_PANEL[tsdebug_displayMessages]" value="1"' .
                        ($this->getBackendUser(
                        )->uc['TSFE_adminConfig']['tsdebug_displayMessages'] ? ' checked="checked"' : '') .
                        ' />';
            $output[] = '      ' . $this->extGetLL('tsdebug_displayMessages');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_LR]" value="0" />';
            $output[] = '    <label for="tsdebug_LR">';
            $output[] = '      <input type="checkbox" id="tsdebug_LR" name="TSFE_ADMIN_PANEL[tsdebug_LR]" value="1"' .
                        ($this->getBackendUser()->uc['TSFE_adminConfig']['tsdebug_LR'] ? ' checked="checked"' : '') .
                        ' />';
            $output[] = '      ' . $this->extGetLL('tsdebug_LR');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayContent]" value="0" />';
            $output[] = '    <label for="tsdebug_displayContent">';
            $output[] = '      <input type="checkbox" id="tsdebug_displayContent" name="TSFE_ADMIN_PANEL[tsdebug_displayContent]" value="1"' .
                        ($this->getBackendUser(
                        )->uc['TSFE_adminConfig']['tsdebug_displayContent'] ? ' checked="checked"' : '') .
                        ' />';
            $output[] = '      ' . $this->extGetLL('tsdebug_displayContent');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_forceTemplateParsing]" value="0" />';
            $output[] = '    <label for="tsdebug_forceTemplateParsing">';
            $output[] = '      <input type="checkbox" id="tsdebug_forceTemplateParsing" name="TSFE_ADMIN_PANEL[tsdebug_forceTemplateParsing]" value="1"' .
                        ($this->getBackendUser(
                        )->uc['TSFE_adminConfig']['tsdebug_forceTemplateParsing'] ? ' checked="checked"' : '') .
                        ' />';
            $output[] = '      ' . $this->extGetLL('tsdebug_forceTemplateParsing');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '</div>';

            $timeTracker = $this->getTimeTracker();
            $timeTracker->printConf['flag_tree'] = $this->getBackendUser()->adminPanel->extGetFeAdminValue('tsdebug', 'tree');
            $timeTracker->printConf['allTime'] = $this->getBackendUser()->adminPanel->extGetFeAdminValue('tsdebug', 'displayTimes');
            $timeTracker->printConf['flag_messages'] = $this->getBackendUser()->adminPanel->extGetFeAdminValue('tsdebug', 'displayMessages');
            $timeTracker->printConf['flag_content'] = $this->getBackendUser()->adminPanel->extGetFeAdminValue('tsdebug', 'displayContent');
            $output[] = $timeTracker->printTSlog();
        }
        return implode('', $output);
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return 'tsdebug';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return $this->extGetLL('tsdebug');
    }

    /**
     * @inheritdoc
     */
    public function showFormSubmitButton(): bool
    {
        return true;
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker(): TimeTracker
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }
}
