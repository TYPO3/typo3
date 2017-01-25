<?php
namespace TYPO3\CMS\Frontend\View;

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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * View class for the admin panel in frontend editing.
 */
class AdminPanelView
{
    /**
     * Determines whether the update button should be shown.
     *
     * @var bool
     */
    protected $extNeedUpdate = false;

    /**
     * Force preview
     *
     * @var bool
     */
    protected $ext_forcePreview = false;

    /**
     * @var string
     */
    protected $extJSCODE = '';

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Determines whether EXT:feedit is loaded
     *
     * @var bool
     */
    protected $extFeEditLoaded = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Initializes settings for the admin panel.
     *
     * @return void
     */
    public function initialize()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->saveConfigOptions();
        $typoScriptFrontend = $this->getTypoScriptFrontendController();
        // Setting some values based on the admin panel
        $this->extFeEditLoaded = ExtensionManagementUtility::isLoaded('feedit');
        $typoScriptFrontend->forceTemplateParsing = $this->extGetFeAdminValue('tsdebug', 'forceTemplateParsing');
        $typoScriptFrontend->displayEditIcons = $this->extGetFeAdminValue('edit', 'displayIcons');
        $typoScriptFrontend->displayFieldEditIcons = $this->extGetFeAdminValue('edit', 'displayFieldIcons');
        if ($this->extGetFeAdminValue('tsdebug', 'displayQueries')) {
            // Do not override if the value is already set in \TYPO3\CMS\Core\Database\DatabaseConnection
            if ($this->getDatabaseConnection()->explainOutput == 0) {
                // Enable execution of EXPLAIN SELECT queries
                $this->getDatabaseConnection()->explainOutput = 3;
            }
        }
        if (GeneralUtility::_GP('ADMCMD_editIcons')) {
            $typoScriptFrontend->displayFieldEditIcons = 1;
        }
        if (GeneralUtility::_GP('ADMCMD_simUser')) {
            $this->getBackendUser()->uc['TSFE_adminConfig']['preview_simulateUserGroup'] = (int)GeneralUtility::_GP('ADMCMD_simUser');
            $this->ext_forcePreview = true;
        }
        if (GeneralUtility::_GP('ADMCMD_simTime')) {
            $this->getBackendUser()->uc['TSFE_adminConfig']['preview_simulateDate'] = (int)GeneralUtility::_GP('ADMCMD_simTime');
            $this->ext_forcePreview = true;
        }
        if ($typoScriptFrontend->forceTemplateParsing) {
            $typoScriptFrontend->set_no_cache('Admin Panel: Force template parsing', true);
        } elseif ($this->extFeEditLoaded && $typoScriptFrontend->displayEditIcons) {
            $typoScriptFrontend->set_no_cache('Admin Panel: Display edit icons', true);
        } elseif ($this->extFeEditLoaded && $typoScriptFrontend->displayFieldEditIcons) {
            $typoScriptFrontend->set_no_cache('Admin Panel: Display field edit icons', true);
        } elseif (GeneralUtility::_GP('ADMCMD_view')) {
            $typoScriptFrontend->set_no_cache('Admin Panel: Display preview', true);
        }
    }

    /**
     * Add an additional stylesheet
     *
     * @return string
     */
    public function getAdminPanelHeaderData()
    {
        $result = '';
        if (!empty($GLOBALS['TBE_STYLES']['stylesheets']['admPanel'])) {
            $stylesheet = GeneralUtility::locationHeaderUrl($GLOBALS['TBE_STYLES']['stylesheets']['admPanel']);
            $result = '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($stylesheet) . '" />';
        }
        return $result;
    }

    /**
     * Checks if an Admin Panel section ("module") is available for the user. If so, TRUE is returned.
     *
     * @param string $key The module key, eg. "edit", "preview", "info" etc.
     * @return bool
     */
    public function isAdminModuleEnabled($key)
    {
        $result = false;
        // Returns TRUE if the module checked is "preview" and the forcePreview flag is set.
        if ($key === 'preview' && $this->ext_forcePreview) {
            $result = true;
        } elseif (!empty($this->getBackendUser()->extAdminConfig['enable.']['all'])) {
            $result = true;
        } elseif (!empty($this->getBackendUser()->extAdminConfig['enable.'][$key])) {
            $result = true;
        }
        return $result;
    }

    /**
     * Saves any change in settings made in the Admin Panel.
     * Called from \TYPO3\CMS\Frontend\Http\RequestHandler right after access check for the Admin Panel
     *
     * @return void
     */
    public function saveConfigOptions()
    {
        $input = GeneralUtility::_GP('TSFE_ADMIN_PANEL');
        $beUser = $this->getBackendUser();
        if (is_array($input)) {
            // Setting
            $beUser->uc['TSFE_adminConfig'] = array_merge(!is_array($beUser->uc['TSFE_adminConfig']) ? [] : $beUser->uc['TSFE_adminConfig'], $input);
            unset($beUser->uc['TSFE_adminConfig']['action']);
            // Actions:
            if ($input['action']['clearCache'] && $this->isAdminModuleEnabled('cache')) {
                $beUser->extPageInTreeInfo = [];
                $theStartId = (int)$input['cache_clearCacheId'];
                $this->getTypoScriptFrontendController()->clearPageCacheContent_pidList($beUser->extGetTreeList($theStartId, $this->extGetFeAdminValue('cache', 'clearCacheLevels'), 0, $beUser->getPagePermsClause(1)) . $theStartId);
            }
            // Saving
            $beUser->writeUC();
        }
        $this->getTimeTracker()->LR = $this->extGetFeAdminValue('tsdebug', 'LR');
        if ($this->extGetFeAdminValue('cache', 'noCache')) {
            $this->getTypoScriptFrontendController()->set_no_cache('Admin Panel: No Caching', true);
        }
    }

    /**
     * Returns the value for an Admin Panel setting.
     *
     * @param string $sectionName Module key
     * @param string $val Setting key
     * @return mixed The setting value
     */
    public function extGetFeAdminValue($sectionName, $val = '')
    {
        if (!$this->isAdminModuleEnabled($sectionName)) {
            return null;
        }

        $beUser = $this->getBackendUser();
        // Exceptions where the values can be overridden (forced) from backend:
        // deprecated
        if (
            $sectionName === 'edit' && (
                $val === 'displayIcons' && $beUser->extAdminConfig['module.']['edit.']['forceDisplayIcons'] ||
                $val === 'displayFieldIcons' && $beUser->extAdminConfig['module.']['edit.']['forceDisplayFieldIcons'] ||
                $val === 'editNoPopup' && $beUser->extAdminConfig['module.']['edit.']['forceNoPopup']
            )
        ) {
            return true;
        }

        // Override all settings with user TSconfig
        if ($val && isset($beUser->extAdminConfig['override.'][$sectionName . '.'][$val])) {
            return $beUser->extAdminConfig['override.'][$sectionName . '.'][$val];
        }
        if (isset($beUser->extAdminConfig['override.'][$sectionName])) {
            return $beUser->extAdminConfig['override.'][$sectionName];
        }

        $returnValue = $val ? $beUser->uc['TSFE_adminConfig'][$sectionName . '_' . $val] : 1;

        // Exception for preview
        if ($sectionName === 'preview' && $this->ext_forcePreview) {
            return !$val ? true : $returnValue;
        }

        // See if the menu is expanded!
        return $this->isAdminModuleOpen($sectionName) ? $returnValue : null;
    }

    /**
     * Enables the force preview option.
     *
     * @return void
     */
    public function forcePreview()
    {
        $this->ext_forcePreview = true;
    }

    /**
     * Returns TRUE if admin panel module is open
     *
     * @param string $key Module key
     * @return bool TRUE, if the admin panel is open for the specified admin panel module key.
     */
    public function isAdminModuleOpen($key)
    {
        return $this->getBackendUser()->uc['TSFE_adminConfig']['display_top'] && $this->getBackendUser()->uc['TSFE_adminConfig']['display_' . $key];
    }

    /**
     * Creates and returns the HTML code for the Admin Panel in the TSFE frontend.
     *
     * @throws \UnexpectedValueException
     * @return string HTML for the Admin Panel
     */
    public function display()
    {
        $this->getLanguageService()->includeLLFile('EXT:lang/locallang_tsfe.xlf');
        $moduleContent = $updateButton = '';

        if ($this->getBackendUser()->uc['TSFE_adminConfig']['display_top']) {
            if ($this->isAdminModuleEnabled('preview')) {
                $moduleContent .= $this->getPreviewModule();
            }
            if ($this->isAdminModuleEnabled('cache')) {
                $moduleContent .= $this->getCacheModule();
            }
            if ($this->isAdminModuleEnabled('edit')) {
                $moduleContent .= $this->getEditModule();
            }
            if ($this->isAdminModuleEnabled('tsdebug')) {
                $moduleContent .= $this->getTSDebugModule();
            }
            if ($this->isAdminModuleEnabled('info')) {
                $moduleContent .= $this->getInfoModule();
            }
        }
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_adminpanel.php']['extendAdminPanel'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_adminpanel.php']['extendAdminPanel'] as $classRef) {
                $hookObject = GeneralUtility::getUserObj($classRef);
                if (!$hookObject instanceof AdminPanelViewHookInterface) {
                    throw new \UnexpectedValueException($classRef . ' must implement interface ' . AdminPanelViewHookInterface::class, 1311942539);
                }
                $moduleContent .= $hookObject->extendAdminPanel($moduleContent, $this);
            }
        }
        $row = $this->extGetLL('adminPanelTitle') . ': <span class="typo3-adminPanel-beuser">' . htmlspecialchars($this->getBackendUser()->user['username']) . '</span>';
        $isVisible = $this->getBackendUser()->uc['TSFE_adminConfig']['display_top'];
        $cssClassName = 'typo3-adminPanel-panel-' . ($isVisible ? 'open' : 'closed');
        $header = '<div class="typo3-adminPanel-header">' . '<div id="typo3-adminPanel-header" class="' . $cssClassName . '">' . '<span class="typo3-adminPanel-header-title">' . $row . '</span>' . $this->linkSectionHeader('top', '<span class="typo3-adminPanel-header-button fa"></span>', 'typo3-adminPanel-header-buttonWrapper') . '<input type="hidden" name="TSFE_ADMIN_PANEL[display_top]" value="' . $this->getBackendUser()->uc['TSFE_adminConfig']['display_top'] . '" /></div>' . '</div>';
        if ($moduleContent && $this->extNeedUpdate) {
            $updateButton = '<div class="typo3-adminPanel-itemRow updatebutton"><div class="typo3-adminPanel-section-content">
							<input class="btn btn-default" type="submit" value="' . $this->extGetLL('update') . '" />
					</div></div>';
        }
        $query = !GeneralUtility::_GET('id') ? '<input type="hidden" name="id" value="' . $this->getTypoScriptFrontendController()->id . '" />' : '';

        // The dummy field is needed for Firefox: to force a page reload on submit
        // which must change the form value with JavaScript (see "onsubmit" attribute of the "form" element")
        $query .= '<input type="hidden" name="TSFE_ADMIN_PANEL[DUMMY]" value="" />';
        foreach (GeneralUtility::_GET() as $key => $value) {
            if ($key != 'TSFE_ADMIN_PANEL') {
                if (is_array($value)) {
                    $query .= $this->getHiddenFields($key, $value);
                } else {
                    $query .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '" />';
                }
            }
        }
        $out = '
<!--
	TYPO3 Admin panel start
-->
<a id="TSFE_ADMIN_PANEL"></a>
<form id="TSFE_ADMIN_PANEL_FORM" name="TSFE_ADMIN_PANEL_FORM" action="' . htmlspecialchars(GeneralUtility::getIndpEnv('TYPO3_REQUEST_SCRIPT')) . '#TSFE_ADMIN_PANEL" method="get" onsubmit="document.forms.TSFE_ADMIN_PANEL_FORM[\'TSFE_ADMIN_PANEL[DUMMY]\'].value=Math.random().toString().substring(2,8)">' . $query . '<div class="typo3-adminPanel">' . $header . $updateButton . $moduleContent . '</div></form>';
        if ($this->getBackendUser()->uc['TSFE_adminConfig']['display_top']) {
            $out .= '<script type="text/javascript" src="' . htmlspecialchars($this->getTypoScriptFrontendController()->absRefPrefix) . ExtensionManagementUtility::siteRelPath('backend') . 'Resources/Public/JavaScript/jsfunc.evalfield.js"></script>';
            $out .= '<script type="text/javascript">/*<![CDATA[*/' . GeneralUtility::minifyJavaScript('
				var evalFunc = new evalFunc();
					// TSFEtypo3FormFieldSet()
				function TSFEtypo3FormFieldSet(theField, evallist, is_in, checkbox, checkboxValue) {	//
					var theFObj = new evalFunc_dummy (evallist,is_in, checkbox, checkboxValue);
					var theValue = document.TSFE_ADMIN_PANEL_FORM[theField].value;
					if (checkbox && theValue==checkboxValue) {
						document.TSFE_ADMIN_PANEL_FORM[theField+"_hr"].value="";
						alert(theField);
						document.TSFE_ADMIN_PANEL_FORM[theField+"_cb"].checked = "";
					} else {
						document.TSFE_ADMIN_PANEL_FORM[theField+"_hr"].value = evalFunc.outputObjValue(theFObj, theValue);
						if (document.TSFE_ADMIN_PANEL_FORM[theField+"_cb"]) {
							document.TSFE_ADMIN_PANEL_FORM[theField+"_cb"].checked = "on";
						}
					}
				}
					// TSFEtypo3FormFieldGet()
				function TSFEtypo3FormFieldGet(theField, evallist, is_in, checkbox, checkboxValue, checkbox_off) {	//
					var theFObj = new evalFunc_dummy (evallist,is_in, checkbox, checkboxValue);
					if (checkbox_off) {
						document.TSFE_ADMIN_PANEL_FORM[theField].value=checkboxValue;
					}else{
						document.TSFE_ADMIN_PANEL_FORM[theField].value = evalFunc.evalObjValue(theFObj, document.TSFE_ADMIN_PANEL_FORM[theField+"_hr"].value);
					}
					TSFEtypo3FormFieldSet(theField, evallist, is_in, checkbox, checkboxValue);
				}') . '/*]]>*/</script><script language="javascript" type="text/javascript">' . $this->extJSCODE . '</script>';
        }
        $cssPath = htmlspecialchars($this->getTypoScriptFrontendController()->absRefPrefix . ExtensionManagementUtility::siteRelPath('t3skin')) . 'stylesheets/standalone/admin_panel.css';
        $out .= '<script src="' . GeneralUtility::locationHeaderUrl(ExtensionManagementUtility::siteRelPath('frontend') . 'Resources/Public/JavaScript/AdminPanel.js') . '" type="text/javascript"></script><script type="text/javascript">/*<![CDATA[*/' . 'typo3AdminPanel = new TYPO3AdminPanel();typo3AdminPanel.init("typo3-adminPanel-header", "TSFE_ADMIN_PANEL_FORM");' . '/*]]>*/</script>
<link type="text/css" rel="stylesheet" href="' . $cssPath . '" media="all" />';
        $out .= $this->getAdminPanelHeaderData();
        $out .='
<!--
	TYPO3 admin panel end
-->
';

        return $out;
    }

    /**
     * Fetches recursively all GET parameters as hidden fields.
     * Called from display()
     *
     * @param string $key Current key
     * @param array $val Current value
     * @return string Hidden fields
     * @see display()
     */
    protected function getHiddenFields($key, array $val)
    {
        $out = '';
        foreach ($val as $k => $v) {
            if (is_array($v)) {
                $out .= $this->getHiddenFields($key . '[' . $k . ']', $v);
            } else {
                $out .= '<input type="hidden" name="' . htmlspecialchars($key) . '[' . htmlspecialchars($k) . ']" value="' . htmlspecialchars($v) . '">' . LF;
            }
        }
        return $out;
    }

    /*****************************************************
     * Creating sections of the Admin Panel
     ****************************************************/
    /**
     * Creates the content for the "preview" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     * @see display()
     */
    protected function getPreviewModule()
    {
        $out = $this->extGetHead('preview');
        if ($this->getBackendUser()->uc['TSFE_adminConfig']['display_preview']) {
            $this->extNeedUpdate = true;
            $out .= $this->extGetItem('preview_showHiddenPages', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[preview_showHiddenPages]" value="0" /><input type="checkbox" id="preview_showHiddenPages" name="TSFE_ADMIN_PANEL[preview_showHiddenPages]" value="1"' . ($this->getBackendUser()->uc['TSFE_adminConfig']['preview_showHiddenPages'] ? ' checked="checked"' : '') . ' />');
            $out .= $this->extGetItem('preview_showHiddenRecords', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[preview_showHiddenRecords]" value="0" /><input type="checkbox" id="preview_showHiddenRecords" name="TSFE_ADMIN_PANEL[preview_showHiddenRecords]" value="1"' . ($this->getBackendUser()->uc['TSFE_adminConfig']['preview_showHiddenRecords'] ? ' checked="checked"' : '') . ' />');
            // Simulate date
            $out .= $this->extGetItem('preview_simulateDate', '<input type="text" id="preview_simulateDate" name="TSFE_ADMIN_PANEL[preview_simulateDate]_hr" onchange="TSFEtypo3FormFieldGet(\'TSFE_ADMIN_PANEL[preview_simulateDate]\', \'datetime\', \'\', 1,0);" /><input type="hidden" name="TSFE_ADMIN_PANEL[preview_simulateDate]" value="' . $this->getBackendUser()->uc['TSFE_adminConfig']['preview_simulateDate'] . '" />');
            $this->extJSCODE .= 'TSFEtypo3FormFieldSet("TSFE_ADMIN_PANEL[preview_simulateDate]", "datetime", "", 0, 0);';
            // Simulate fe_user:

            $options = '';

            $res = $this->getDatabaseConnection()->exec_SELECTquery(
                'fe_groups.uid, fe_groups.title',
                'fe_groups,pages',
                'pages.uid=fe_groups.pid AND pages.deleted=0 ' . BackendUtility::deleteClause('fe_groups') . ' AND ' . $this->getBackendUser()->getPagePermsClause(1),
                '',
                'fe_groups.title ASC'
            );
            while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                $options .= '<option value="' . $row['uid'] . '"' . ($this->getBackendUser()->uc['TSFE_adminConfig']['preview_simulateUserGroup'] == $row['uid'] ? ' selected="selected"' : '') . '>' . htmlspecialchars(($row['title'] . ' [' . $row['uid'] . ']')) . '</option>';
            }
            $this->getDatabaseConnection()->sql_free_result($res);
            if ($options) {
                $options = '<option value="0">&nbsp;</option>' . $options;
                $out .= $this->extGetItem('preview_simulateUserGroup', '<select id="preview_simulateUserGroup" name="TSFE_ADMIN_PANEL[preview_simulateUserGroup]">' . $options . '</select>');
            }
        }
        return $out;
    }

    /**
     * Creates the content for the "cache" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     * @see display()
     */
    protected function getCacheModule()
    {
        $out = $this->extGetHead('cache');
        $beUser = $this->getBackendUser();
        if ($beUser->uc['TSFE_adminConfig']['display_cache']) {
            $this->extNeedUpdate = true;
            $out .= $this->extGetItem('cache_noCache', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[cache_noCache]" value="0" /><input id="cache_noCache" type="checkbox" name="TSFE_ADMIN_PANEL[cache_noCache]" value="1"' . ($beUser->uc['TSFE_adminConfig']['cache_noCache'] ? ' checked="checked"' : '') . ' />');
            $levels = $beUser->uc['TSFE_adminConfig']['cache_clearCacheLevels'];
            $options = '';
            $options .= '<option value="0"' . ($levels == 0 ? ' selected="selected"' : '') . '>' . $this->extGetLL('div_Levels_0') . '</option>';
            $options .= '<option value="1"' . ($levels == 1 ? ' selected="selected"' : '') . '>' . $this->extGetLL('div_Levels_1') . '</option>';
            $options .= '<option value="2"' . ($levels == 2 ? ' selected="selected"' : '') . '>' . $this->extGetLL('div_Levels_2') . '</option>';
            $out .= $this->extGetItem('cache_clearLevels', '<select id="cache_clearLevels" name="TSFE_ADMIN_PANEL[cache_clearCacheLevels]">' . $options . '</select>' . '<input type="hidden" name="TSFE_ADMIN_PANEL[cache_clearCacheId]" value="' . $GLOBALS['TSFE']->id . '" /> <input class="btn btn-default" type="submit" value="' . $this->extGetLL('update') . '" />');
            // Generating tree:
            $depth = (int)$this->extGetFeAdminValue('cache', 'clearCacheLevels');
            $outTable = '';
            $tsfe = $this->getTypoScriptFrontendController();
            $beUser->extPageInTreeInfo = [];
            $beUser->extPageInTreeInfo[] = [$tsfe->page['uid'], htmlspecialchars($tsfe->page['title']), $depth + 1];
            $beUser->extGetTreeList($tsfe->id, $depth, 0, $beUser->getPagePermsClause(1));
            foreach ($beUser->extPageInTreeInfo as $key => $row) {
                $outTable .= '<tr class="typo3-adminPanel-itemRow ' . ($key % 2 == 0 ? 'line-even' : 'line-odd') . '">' . '<td><span style="width: ' . ($depth + 1 - $row[2]) * 18 . 'px; height: 1px; display: inline-block;"></span>' . $this->iconFactory->getIcon('apps-pagetree-page-default', Icon::SIZE_SMALL)->render() . htmlspecialchars($row[1]) . '</td><td>' . $beUser->extGetNumberOfCachedPages($row[0]) . '</td></tr>';
            }
            $outTable = '<table class="typo3-adminPanel-table"><thead><tr><th colspan="2">' . $this->extGetLL('cache_cacheEntries') . '</th></tr></thead>' . $outTable . '</table>';
            $outTable .= '<span class="fa fa-bolt clear-cache-icon"><!-- --></span><input class="btn btn-default clear-cache" type="submit" name="TSFE_ADMIN_PANEL[action][clearCache]" value="' . $this->extGetLL('cache_doit') . '" />';

            $out .= $this->extGetItem('', $outTable, '', 'typo3-adminPanel-tableRow', 'typo3-adminPanel-table-wrapper');
        }
        return $out;
    }

    /**
     * Creates the content for the "edit" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     * @see display()
     */
    protected function getEditModule()
    {
        $out = $this->extGetHead('edit');
        if ($this->getBackendUser()->uc['TSFE_adminConfig']['display_edit']) {
            // If another page module was specified, replace the default Page module with the new one
            $newPageModule = trim($this->getBackendUser()->getTSConfigVal('options.overridePageModule'));
            $pageModule = BackendUtility::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';
            $this->extNeedUpdate = true;
            if ($this->extFeEditLoaded) {
                $out .= $this->extGetItem('edit_displayFieldIcons', '',
                    '<input type="hidden" name="TSFE_ADMIN_PANEL[edit_displayFieldIcons]" value="0" /><input type="checkbox" id="edit_displayFieldIcons" name="TSFE_ADMIN_PANEL[edit_displayFieldIcons]" value="1"' . ($this->getBackendUser()->uc['TSFE_adminConfig']['edit_displayFieldIcons'] ? ' checked="checked"' : '') . ' />');
                $out .= $this->extGetItem('edit_displayIcons', '',
                    '<input type="hidden" name="TSFE_ADMIN_PANEL[edit_displayIcons]" value="0" /><input type="checkbox" id="edit_displayIcons" name="TSFE_ADMIN_PANEL[edit_displayIcons]" value="1"' . ($this->getBackendUser()->uc['TSFE_adminConfig']['edit_displayIcons'] ? ' checked="checked"' : '') . ' />');
            }
            $out .= $this->extGetItem('', $this->ext_makeToolBar());
            if (!GeneralUtility::_GP('ADMCMD_view')) {
                $out .= $this->extGetItem('', '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(('
						if (parent.opener && parent.opener.top && parent.opener.top.TS) {
							parent.opener.top.fsMod.recentIds["web"]=' . (int)$this->getTypoScriptFrontendController()->page['uid'] . ';
							if (parent.opener.top.content && parent.opener.top.content.nav_frame && parent.opener.top.content.nav_frame.refresh_nav) {
								parent.opener.top.content.nav_frame.refresh_nav();
							}
							parent.opener.top.goToModule("' . $pageModule . '");
							parent.opener.top.focus();
						} else {
							vHWin=window.open(' . GeneralUtility::quoteJSvalue(BackendUtility::getBackendScript()) . ',\'' . md5('Typo3Backend-' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']) . '\');
							vHWin.focus();
						}
						return false;
						')) . '">' . $this->extGetLL('edit_openAB') . '</a>');
            }
        }
        return $out;
    }

    /**
     * Creates the content for the "tsdebug" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     * @see display()
     */
    protected function getTSDebugModule()
    {
        $out = $this->extGetHead('tsdebug');
        $beuser = $this->getBackendUser();
        if ($beuser->uc['TSFE_adminConfig']['display_tsdebug']) {
            $this->extNeedUpdate = true;
            $out .= $this->extGetItem('tsdebug_tree', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_tree]" value="0" /><input type="checkbox" id="tsdebug_tree" name="TSFE_ADMIN_PANEL[tsdebug_tree]" value="1"' . ($beuser->uc['TSFE_adminConfig']['tsdebug_tree'] ? ' checked="checked"' : '') . ' />');
            $out .= $this->extGetItem('tsdebug_displayTimes', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayTimes]" value="0" /><input id="tsdebug_displayTimes" type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_displayTimes]" value="1"' . ($beuser->uc['TSFE_adminConfig']['tsdebug_displayTimes'] ? ' checked="checked"' : '') . ' />');
            $out .= $this->extGetItem('tsdebug_displayMessages', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayMessages]" value="0" /><input type="checkbox" id="tsdebug_displayMessages" name="TSFE_ADMIN_PANEL[tsdebug_displayMessages]" value="1"' . ($beuser->uc['TSFE_adminConfig']['tsdebug_displayMessages'] ? ' checked="checked"' : '') . ' />');
            $out .= $this->extGetItem('tsdebug_LR', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_LR]" value="0" /><input type="checkbox" id="tsdebug_LR" name="TSFE_ADMIN_PANEL[tsdebug_LR]" value="1"' . ($beuser->uc['TSFE_adminConfig']['tsdebug_LR'] ? ' checked="checked"' : '') . ' />');
            $out .= $this->extGetItem('tsdebug_displayContent', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayContent]" value="0" /><input type="checkbox" id="tsdebug_displayContent" name="TSFE_ADMIN_PANEL[tsdebug_displayContent]" value="1"' . ($beuser->uc['TSFE_adminConfig']['tsdebug_displayContent'] ? ' checked="checked"' : '') . ' />');
            $out .= $this->extGetItem('tsdebug_displayQueries', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayQueries]" value="0" /><input type="checkbox" id="tsdebug_displayQueries" name="TSFE_ADMIN_PANEL[tsdebug_displayQueries]" value="1"' . ($beuser->uc['TSFE_adminConfig']['tsdebug_displayQueries'] ? ' checked="checked"' : '') . ' />');
            $out .= $this->extGetItem('tsdebug_forceTemplateParsing', '', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_forceTemplateParsing]" value="0" /><input type="checkbox" id="tsdebug_forceTemplateParsing" name="TSFE_ADMIN_PANEL[tsdebug_forceTemplateParsing]" value="1"' . ($beuser->uc['TSFE_adminConfig']['tsdebug_forceTemplateParsing'] ? ' checked="checked"' : '') . ' />');
            $timeTracker = $this->getTimeTracker();
            $timeTracker->printConf['flag_tree'] = $this->extGetFeAdminValue('tsdebug', 'tree');
            $timeTracker->printConf['allTime'] = $this->extGetFeAdminValue('tsdebug', 'displayTimes');
            $timeTracker->printConf['flag_messages'] = $this->extGetFeAdminValue('tsdebug', 'displayMessages');
            $timeTracker->printConf['flag_content'] = $this->extGetFeAdminValue('tsdebug', 'displayContent');
            $timeTracker->printConf['flag_queries'] = $this->extGetFeAdminValue('tsdebug', 'displayQueries');
            $out .= $this->extGetItem('', $timeTracker->printTSlog(), '', 'typo3-adminPanel-tableRow', 'typo3-adminPanel-table-wrapper scroll-table');
        }
        return $out;
    }

    /**
     * Creates the content for the "info" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     * @see display()
     */
    protected function getInfoModule()
    {
        $head = $this->extGetHead('info');
        $out = '';
        $tsfe = $this->getTypoScriptFrontendController();
        if ($this->getBackendUser()->uc['TSFE_adminConfig']['display_info']) {
            $tableArr = [];
            if ($this->extGetFeAdminValue('cache', 'noCache')) {
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
                $tableArr[] = [$this->extGetLL('info_DocumentSize'), GeneralUtility::formatSize(strlen($tsfe->content)), true];
                $tableArr[] = ['', ''];
            }
            $tableArr[] = [$this->extGetLL('info_id'), $tsfe->id];
            $tableArr[] = [$this->extGetLL('info_type'), $tsfe->type];
            $tableArr[] = [$this->extGetLL('info_groupList'), $tsfe->gr_list];
            $tableArr[] = [$this->extGetLL('info_noCache'), $this->extGetLL('info_noCache_' . ($tsfe->no_cache ? 'no' : 'yes'))];
            $tableArr[] = [$this->extGetLL('info_countUserInt'), count($tsfe->config['INTincScript'])];

            if (!empty($tsfe->fe_user->user['uid'])) {
                $tableArr[] = [$this->extGetLL('info_feuserName'), htmlspecialchars($tsfe->fe_user->user['username'])];
                $tableArr[] = [$this->extGetLL('info_feuserId'), htmlspecialchars($tsfe->fe_user->user['uid'])];
            }
            $tableArr[] = [$this->extGetLL('info_totalParsetime'), $tsfe->scriptParseTime . ' ms', true];
            $table = '';
            foreach ($tableArr as $key => $arr) {
                $label = (isset($arr[2]) ? '<strong>' . $arr[0] . '</strong>' : $arr[0]);
                $value = (string)$arr[1] !== '' ? $arr[1] : '';
                $table .=
                    '<tr class="typo3-adminPanel-itemRow ' . ($key % 2 == 0 ? 'line-even' : 'line-odd') . '">
							<td>' . $label . '</td>
							<td>' . htmlspecialchars($value) . '</td>
						</tr>';
            }
            $out .= $table;
            $out = '<table class="typo3-adminPanel-table">' . $out . '</table>';
            $out = $this->extGetItem('', $out, '', 'typo3-adminPanel-tableRow', 'typo3-adminPanel-table-wrapper');
        }

        $out = $head . $out;
        return $out;
    }

    /*****************************************************
     * Admin Panel Layout Helper functions
     ****************************************************/
    /**
     * Returns a row (with colspan=4) which is a header for a section in the Admin Panel.
     * It will have a plus/minus icon and a label which is linked so that it submits the form which surrounds the whole Admin Panel when clicked, alterting the TSFE_ADMIN_PANEL[display_' . $pre . '] value
     * See the functions get*Module
     *
     * @param string $sectionSuffix The suffix to the display_ label. Also selects the label from the LOCAL_LANG array.
     * @return string HTML table row.
     * @see extGetItem()
     */
    public function extGetHead($sectionSuffix)
    {
        $settingName = 'display_' . $sectionSuffix;
        $isVisible = $this->getBackendUser()->uc['TSFE_adminConfig'][$settingName];
        $cssClassName = 'typo3-adminPanel-section-' . ($isVisible ? 'open' : 'closed');
        return '<div class="typo3-adminPanel-section-title"><div class="wrapper">' . $this->linkSectionHeader($sectionSuffix, $this->extGetLL($sectionSuffix), $cssClassName) . '<input type="hidden" name="TSFE_ADMIN_PANEL[' . $settingName . ']" value="' . $isVisible . '" /></div></div>';
    }

    /**
     * Wraps a string in a link which will open/close a certain part of the Admin Panel
     *
     * @param string $sectionSuffix The code for the display_ label/key
     * @param string $sectionTitle Title (in HTML-format)
     * @param string $className The classname for the <a> tag
     * @return string $className Linked input string
     * @see extGetHead()
     */
    public function linkSectionHeader($sectionSuffix, $sectionTitle, $className = '')
    {
        $onclick = 'document.TSFE_ADMIN_PANEL_FORM[' . GeneralUtility::quoteJSvalue('TSFE_ADMIN_PANEL[display_' . $sectionSuffix . ']') . '].value=' . ($this->getBackendUser()->uc['TSFE_adminConfig']['display_' . $sectionSuffix] ? '0' : '1') . ';document.TSFE_ADMIN_PANEL_FORM.submit();return false;';
        $content = '<div class="typo3-adminPanel-label">
						<a href="javascript:void(0)" onclick="' . htmlspecialchars($onclick) . '"' . ($className ? ' class="fa ' . htmlspecialchars($className) . '"' : '') . '>'
            . $sectionTitle .
            '</a>
		</div>';
        return $content;
    }

    /**
     * Returns a row (with 4 columns) for content in a section of the Admin Panel.
     * It will take $pre as a key to a label to display and $element as the content to put into the forth cell.
     *
     * @param string $title Key to label
     * @param string $content The HTML content for the forth table cell.
     * @param string $checkbox The HTML for a checkbox or hidden fields.
     * @param string  $innerDivClass The Class attribute for the td element.
     * @param string  $outerDivClass The Class attribute for the tr element.
     * @return string HTML table row.
     * @see extGetHead()
     */
    public function extGetItem($title, $content = '', $checkbox = '', $outerDivClass = null, $innerDivClass = null)
    {
        $title = $title ? '<label for="' . htmlspecialchars($title) . '">' . $this->extGetLL($title) . '</label>' : '';
        $outerDivClass === null ? $out = '<div class="typo3-adminPanel-itemRow">' : $out = '<div class="' . $outerDivClass . '">';
        $innerDivClass === null ? $out .= '<div class="typo3-adminPanel-section-content">' : $out .= '<div class="' . $innerDivClass . '">';
        $out .= $checkbox . $title . $content . '</div>
				</div>';
        return $out;
    }

    /**
     * Creates the tool bar links for the "edit" section of the Admin Panel.
     *
     * @return string A string containing images wrapped in <a>-tags linking them to proper functions.
     */
    public function ext_makeToolBar()
    {
        $tsfe = $this->getTypoScriptFrontendController();
        //  If mod.newContentElementWizard.override is set, use that extension's create new content wizard instead:
        $tsConfig = BackendUtility::getModTSconfig($tsfe->page['uid'], 'mod');
        $moduleName = isset($tsConfig['properties']['newContentElementWizard.']['override'])
            ? $tsConfig['properties']['newContentElementWizard.']['override']
            : 'new_content_element';
        $newContentWizScriptPath = BackendUtility::getModuleUrl($moduleName);
        $perms = $this->getBackendUser()->calcPerms($tsfe->page);
        $langAllowed = $this->getBackendUser()->checkLanguageAccess($tsfe->sys_language_uid);
        $id = $tsfe->id;
        $returnUrl = GeneralUtility::getIndpEnv('REQUEST_URI');

        $icon = $this->iconFactory->getIcon('actions-document-history-open', Icon::SIZE_SMALL)->render();
        $link = BackendUtility::getModuleUrl('record_history', ['element' => 'pages:' . $id, 'returnUrl' => $returnUrl]);
        $toolBar = '<a class="t3-icon btn btn-default" href="' . htmlspecialchars($link) . '#latest" title="' . $this->extGetLL('edit_recordHistory') . '">' . $icon . '</a>';
        if ($perms & Permission::CONTENT_EDIT && $langAllowed) {
            $params = '';
            if ($tsfe->sys_language_uid) {
                $params = '&sys_language_uid=' . $tsfe->sys_language_uid;
            }
            $icon = $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render();
            $link = $newContentWizScriptPath . 'id=' . $id . $params . '&returnUrl=' . rawurlencode($returnUrl);
            $toolBar .= '<a class="t3-icon btn btn-default" href="' . htmlspecialchars($link) . '" title="' . $this->extGetLL('edit_newContentElement') . '"">' . $icon . '</a>';
        }
        if ($perms & Permission::PAGE_EDIT) {
            $icon = $this->iconFactory->getIcon('actions-document-move', Icon::SIZE_SMALL)->render();
            $link = BackendUtility::getModuleUrl('move_element', ['table' => 'pages', 'uid' => $id, 'returnUrl' => $returnUrl]);
            $toolBar .= '<a class="t3-icon btn btn-default" href="' . htmlspecialchars($link) . '" title="' . $this->extGetLL('edit_move_page') . '">' . $icon . '</a>';
        }
        if ($perms & Permission::PAGE_NEW) {
            $toolBar .= '<a class="t3-icon btn btn-default" href="' . htmlspecialchars(BackendUtility::getModuleUrl('db_new', ['id' => $id, 'pagesOnly' => 1, 'returnUrl' => $returnUrl])) . '" title="' . $this->extGetLL('edit_newPage') . '">'
                . $this->iconFactory->getIcon('actions-page-new', Icon::SIZE_SMALL)->render()
                . '</a>';
        }
        if ($perms & Permission::PAGE_EDIT) {
            $icon = $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render();
            $url = BackendUtility::getModuleUrl('record_edit', [
                'edit[pages][' . $id . ']' => 'edit',
                'noView' => 1,
                'returnUrl' => $returnUrl
            ]);
            $toolBar .= '<a class="t3-icon btn btn-default" href="' . htmlspecialchars($url) . '">' . $icon . '</a>';
            if ($tsfe->sys_language_uid && $langAllowed) {
                $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
                    'uid,pid,t3ver_state',
                    'pages_language_overlay',
                    'pid=' . (int)$id .
                    ' AND sys_language_uid=' . $tsfe->sys_language_uid .
                    $tsfe->sys_page->enableFields('pages_language_overlay')
                );
                $tsfe->sys_page->versionOL('pages_language_overlay', $row);
                if (is_array($row)) {
                    $icon = '<span title="' . $this->extGetLL('edit_editPageOverlay', true) . '">'
                        . $this->iconFactory->getIcon('mimetypes-x-content-page-language-overlay', Icon::SIZE_SMALL)->render() . '</span>';
                    $url = BackendUtility::getModuleUrl('record_edit', [
                        'edit[pages_language_overlay][' . $row['uid'] . ']' => 'edit',
                        'noView' => 1,
                        'returnUrl' => $returnUrl
                    ]);
                    $toolBar .= '<a href="' . htmlspecialchars($url) . '">' . $icon . '</a>';
                }
            }
        }
        if ($this->getBackendUser()->check('modules', 'web_list')) {
            $urlParams = [
                'id' => $id,
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
            ];
            $icon = '<span title="' . $this->extGetLL('edit_db_list', false) . '">' . $this->iconFactory->getIcon('actions-system-list-open', Icon::SIZE_SMALL)->render() . '</span>';
            $toolBar .= '<a class="t3-icon btn btn-default" href="' . htmlspecialchars(BackendUtility::getModuleUrl('web_list', $urlParams)) . '">' . $icon . '</a>';
        }

        $toolBar = '<div class="toolbar btn-group" role="group">' . $toolBar . '</div>';
        return $toolBar;
    }

    /**
     * Translate given key
     *
     * @param string $key Key for a label in the $LOCAL_LANG array of "sysext/lang/locallang_tsfe.xlf
     * @param bool $convertWithHtmlspecialchars If TRUE the language-label will be sent through htmlspecialchars
     * @return string The value for the $key
     */
    protected function extGetLL($key, $convertWithHtmlspecialchars = true)
    {
        $labelStr = $this->getLanguageService()->getLL($key);
        if ($convertWithHtmlspecialchars) {
            $labelStr = htmlspecialchars($labelStr);
        }
        return $labelStr;
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Backend\FrontendBackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns the database connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return \TYPO3\CMS\Core\TimeTracker\TimeTracker
     */
    protected function getTimeTracker()
    {
        return $GLOBALS['TT'];
    }
}
