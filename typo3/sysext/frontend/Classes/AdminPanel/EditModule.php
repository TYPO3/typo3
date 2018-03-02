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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Admin Panel Edit Module
 */
class EditModule extends AbstractModule
{
    /**
     * Creates the content for the "edit" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     */
    public function getContent(): string
    {
        $output = [];
        if ($this->getBackendUser()->uc['TSFE_adminConfig']['display_edit']) {

            // If another page module was specified, replace the default Page module with the new one
            $newPageModule = trim((string)$this->getBackendUser()->getTSConfigVal('options.overridePageModule'));
            $pageModule = BackendUtility::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';

            if (ExtensionManagementUtility::isLoaded('feedit')) {
                $output[] = '<div class="typo3-adminPanel-form-group">';
                $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
                $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[edit_displayFieldIcons]" value="0" />';
                $output[] = '    <label for="edit_displayFieldIcons">';
                $output[] = '      <input type="checkbox" id="edit_displayFieldIcons" name="TSFE_ADMIN_PANEL[edit_displayFieldIcons]" value="1"' .
                            ($this->getBackendUser(
                            )->uc['TSFE_adminConfig']['edit_displayFieldIcons'] ? ' checked="checked"' : '') .
                            ' />';
                $output[] = '      ' . $this->extGetLL('edit_displayFieldIcons');
                $output[] = '    </label>';
                $output[] = '  </div>';
                $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
                $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[edit_displayIcons]" value="0" />';
                $output[] = '    <label for="edit_displayIcons">';
                $output[] = '      <input type="checkbox" id="edit_displayIcons" name="TSFE_ADMIN_PANEL[edit_displayIcons]" value="1"' .
                            ($this->getBackendUser(
                            )->uc['TSFE_adminConfig']['edit_displayIcons'] ? ' checked="checked"' : '') .
                            ' />';
                $output[] = '      ' . $this->extGetLL('edit_displayIcons');
                $output[] = '    </label>';
                $output[] = '  </div>';
                $output[] = '</div>';
            }

            $output[] = $this->getBackendUser()->adminPanel->ext_makeToolBar();

            $onClick = '
                if (parent.opener && parent.opener.top && parent.opener.top.TS) {
                    parent.opener.top.fsMod.recentIds["web"]=' .
                       (int)$this->getTypoScriptFrontendController()->page['uid'] .
                       ';
                    if (parent.opener.top && parent.opener.top.nav_frame && parent.opener.top.nav_frame.refresh_nav) {
                        parent.opener.top.nav_frame.refresh_nav();
                    }
                    parent.opener.top.goToModule("' .
                       $pageModule .
                       '");
                    parent.opener.top.focus();
                } else {
                    vHWin=window.open(' .
                       GeneralUtility::quoteJSvalue(BackendUtility::getBackendScript()) .
                       ',\'' .
                       md5('Typo3Backend-' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']) .
                       '\');
                    vHWin.focus();
                }
                return false;
            ';
            $output[] = '<div class="typo3-adminPanel-form-group">';
            $output[] = '  <a class="typo3-adminPanel-btn typo3-adminPanel-btn-default" href="#" onclick="' .
                        htmlspecialchars($onClick) .
                        '">';
            $output[] = '    ' . $this->extGetLL('edit_openAB');
            $output[] = '  </a>';
            $output[] = '</div>';
        }
        return implode('', $output);
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return 'edit';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return $this->extGetLL('edit');
    }

    /**
     * Initialize the edit module
     * Includes the frontend edit initialization
     *
     * @todo move into fe_edit (including the module)
     */
    public function initializeModule(): void
    {
        $extFeEditLoaded = ExtensionManagementUtility::isLoaded('feedit');
        $typoScriptFrontend = $this->getTypoScriptFrontendController();
        $typoScriptFrontend->displayEditIcons = $this->getConfigurationOption('displayIcons');
        $typoScriptFrontend->displayFieldEditIcons = $this->getConfigurationOption('displayFieldIcons');

        if (GeneralUtility::_GP('ADMCMD_editIcons')) {
            $typoScriptFrontend->displayFieldEditIcons = '1';
        }
        if ($extFeEditLoaded && $typoScriptFrontend->displayEditIcons) {
            $typoScriptFrontend->set_no_cache('Admin Panel: Display edit icons', true);
        }
        if ($extFeEditLoaded && $typoScriptFrontend->displayFieldEditIcons) {
            $typoScriptFrontend->set_no_cache('Admin Panel: Display field edit icons', true);
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
}
