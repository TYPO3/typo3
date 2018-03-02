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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Admin Panel Preview Module
 */
class PreviewModule extends AbstractModule
{
    /**
     * Force the preview panel to be opened
     *
     * @var bool
     */
    protected $forceOpen = false;

    /**
     * @inheritdoc
     */
    public function getAdditionalJavaScriptCode(): string
    {
        return 'TSFEtypo3FormFieldSet("TSFE_ADMIN_PANEL[preview_simulateDate]", "datetime", "", 0, 0);';
    }

    /**
     * Creates the content for the "preview" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     * @see display()
     * @throws \InvalidArgumentException
     */
    public function getContent(): string
    {
        $output = [];
        if ($this->getBackendUser()->uc['TSFE_adminConfig']['display_preview']) {
            $output[] = '<div class="typo3-adminPanel-form-group">';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[preview_showHiddenPages]" value="0" />';
            $output[] = '    <label for="preview_showHiddenPages">';
            $output[] = '      <input type="checkbox" id="preview_showHiddenPages" name="TSFE_ADMIN_PANEL[preview_showHiddenPages]" value="1"' .
                        ($this->getBackendUser(
                        )->uc['TSFE_adminConfig']['preview_showHiddenPages'] ? ' checked="checked"' : '') .
                        ' />';
            $output[] = '      ' . $this->extGetLL('preview_showHiddenPages');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[preview_showHiddenRecords]" value="0" />';
            $output[] = '    <label for="preview_showHiddenRecords">';
            $output[] = '      <input type="checkbox" id="preview_showHiddenRecords" name="TSFE_ADMIN_PANEL[preview_showHiddenRecords]" value="1"' .
                        ($this->getBackendUser(
                        )->uc['TSFE_adminConfig']['preview_showHiddenRecords'] ? ' checked="checked"' : '') .
                        ' />';
            $output[] = '      ' . $this->extGetLL('preview_showHiddenRecords');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[preview_showFluidDebug]" value="0" />';
            $output[] = '    <label for="preview_showFluidDebug">';
            $output[] = '      <input type="checkbox" id="preview_showFluidDebug" name="TSFE_ADMIN_PANEL[preview_showFluidDebug]" value="1"' .
                        ($this->getBackendUser(
                        )->uc['TSFE_adminConfig']['preview_showFluidDebug'] ? ' checked="checked"' : '') .
                        ' />';
            $output[] = '      ' . $this->extGetLL('preview_showFluidDebug');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '</div>';

            // Simulate date
            $output[] = '<div class="typo3-adminPanel-form-group">';
            $output[] = '  <label for="preview_simulateDate">';
            $output[] = '    ' . $this->extGetLL('preview_simulateDate');
            $output[] = '  </label>';
            $output[] = '  <input type="text" id="preview_simulateDate" name="TSFE_ADMIN_PANEL[preview_simulateDate]_hr" onchange="TSFEtypo3FormFieldGet(\'TSFE_ADMIN_PANEL[preview_simulateDate]\', \'datetime\', \'\', 1,0);" />';
            // the hidden field must be placed after the _hr field to avoid the timestamp being overridden by the date string
            $output[] = '  <input type="hidden" name="TSFE_ADMIN_PANEL[preview_simulateDate]" value="' .
                        $this->getBackendUser()->uc['TSFE_adminConfig']['preview_simulateDate'] .
                        '" />';
            $output[] = '</div>';

            // Frontend Usergroups
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('fe_groups');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $optionCount = $queryBuilder->count('fe_groups.uid')
                ->from('fe_groups')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pages.uid', $queryBuilder->quoteIdentifier('fe_groups.pid')),
                    $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)
                )
                ->execute()
                ->fetchColumn(0);
            if ($optionCount > 0) {
                $result = $queryBuilder->select('fe_groups.uid', 'fe_groups.title')
                    ->from('fe_groups')
                    ->from('pages')
                    ->where(
                        $queryBuilder->expr()->eq('pages.uid', $queryBuilder->quoteIdentifier('fe_groups.pid')),
                        $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)
                    )
                    ->orderBy('fe_groups.title')
                    ->execute();
                $output[] = '<div class="typo3-adminPanel-form-group">';
                $output[] = '  <label for="preview_simulateUserGroup">';
                $output[] = '    ' . $this->extGetLL('preview_simulateUserGroup');
                $output[] = '  </label>';
                $output[] = '  <select id="preview_simulateUserGroup" name="TSFE_ADMIN_PANEL[preview_simulateUserGroup]">';
                $output[] = '    <option value="0">&nbsp;</option>';
                while ($row = $result->fetch()) {
                    $output[] = '<option value="' .
                                (int)$row['uid'] .
                                '" ' .
                                ($this->getBackendUser()->uc['TSFE_adminConfig']['preview_simulateUserGroup'] ===
                                 $row['uid'] ? ' selected="selected"' : '') .
                                '>';
                    $output[] = htmlspecialchars($row['title'] . ' [' . $row['uid'] . ']');
                    $output[] = '</option>';
                }
                $output[] = '  </select>';
                $output[] = '</div>';
            }
        }
        return implode('', $output);
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return 'preview';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return $this->extGetLL('preview');
    }

    public function initializeModule(): void
    {
        $this->initializeFrontendPreview();
        if (GeneralUtility::_GP('ADMCMD_simUser')) {
            $this->getBackendUser()->uc['TSFE_adminConfig']['preview_simulateUserGroup'] = (int)GeneralUtility::_GP(
                'ADMCMD_simUser'
            );
            $this->forceOpen = true;
        }
        if (GeneralUtility::_GP('ADMCMD_simTime')) {
            $this->getBackendUser()->uc['TSFE_adminConfig']['preview_simulateDate'] = (int)GeneralUtility::_GP(
                'ADMCMD_simTime'
            );
            $this->forceOpen = true;
        }
    }

    /**
     * Force module to be shown if either time or users/groups are simulated
     *
     * @return bool
     */
    public function isShown(): bool
    {
        if ($this->forceOpen) {
            return true;
        }
        return parent::isShown();
    }

    /**
     * Clear page cache if fluid debug output is enabled
     *
     * @param array $input
     */
    public function onSubmit(array $input): void
    {
        if ($input['preview_showFluidDebug'] ?? false) {
            $theStartId = (int)$this->getTypoScriptFrontendController()->id;
            $this->getTypoScriptFrontendController()
                ->clearPageCacheContent_pidList(
                    $this->getBackendUser()->extGetTreeList(
                        $theStartId,
                        0,
                        0,
                        $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)
                    ) . $theStartId
                );
        }
    }

    /**
     * @inheritdoc
     */
    public function showFormSubmitButton(): bool
    {
        return true;
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * Initialize frontend preview functionality incl.
     * simulation of users or time
     */
    protected function initializeFrontendPreview()
    {
        $tsfe = $this->getTypoScriptFrontendController();
        $tsfe->clear_preview();
        $tsfe->fePreview = 1;
        $tsfe->showHiddenPage = (bool)$this->getConfigurationOption('showHiddenPages');
        $tsfe->showHiddenRecords = (bool)$this->getConfigurationOption('showHiddenRecords');
        // Simulate date
        $simTime = $this->getConfigurationOption('simulateDate');
        if ($simTime) {
            $GLOBALS['SIM_EXEC_TIME'] = $simTime;
            $GLOBALS['SIM_ACCESS_TIME'] = $simTime - $simTime % 60;
        }
        // simulate user
        $tsfe->simUserGroup = $this->getConfigurationOption('simulateUserGroup');
        if ($tsfe->simUserGroup) {
            if ($tsfe->fe_user->user) {
                $tsfe->fe_user->user[$tsfe->fe_user->usergroup_column] = $tsfe->simUserGroup;
            } else {
                $tsfe->fe_user = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
                $tsfe->fe_user->user = [
                    $tsfe->fe_user->usergroup_column => $tsfe->simUserGroup,
                ];
            }
        }
        if (!$tsfe->simUserGroup && !$simTime && !$tsfe->showHiddenPage && !$tsfe->showHiddenRecords) {
            $tsfe->fePreview = 0;
        }
    }
}
