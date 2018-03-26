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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
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
            if (($input['action']['clearCache'] && $this->isAdminModuleEnabled('cache')) || isset($input['preview_showFluidDebug'])) {
                $beUser->extPageInTreeInfo = [];
                $theStartId = (int)$input['cache_clearCacheId'];
                $this->getTypoScriptFrontendController()
                    ->clearPageCacheContent_pidList(
                        $beUser->extGetTreeList(
                            $theStartId,
                            $this->extGetFeAdminValue(
                                'cache',
                                'clearCacheLevels'
                            ),
                            0,
                            $beUser->getPagePermsClause(1)
                        ) . $theStartId
                    );
            }
            // Saving
            $beUser->writeUC();
            // Flush fluid template cache
            $cacheManager = new CacheManager();
            $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
            $cacheManager->getCache('fluid_template')->flush();
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
        if (!$val && isset($beUser->extAdminConfig['override.'][$sectionName])) {
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
     * @param string $key
     * @param string $content
     *
     * @return string
     */
    protected function getModule($key, $content)
    {
        $output = [];

        if ($this->getBackendUser()->uc['TSFE_adminConfig']['display_top'] && $this->isAdminModuleEnabled($key)) {
            $output[] = '<div class="typo3-adminPanel-section typo3-adminPanel-section-' . ($this->isAdminModuleOpen($key) ? 'open' : 'closed') . '">';
            $output[] = '  <div class="typo3-adminPanel-section-title">';
            $output[] = '    ' . $this->linkSectionHeader($key, $this->extGetLL($key));
            $output[] = '  </div>';
            if ($this->isAdminModuleOpen($key)) {
                $output[] = '<div class="typo3-adminPanel-section-body">';
                $output[] = '  ' . $content;
                $output[] = '</div>';
            }
            $output[] = '</div>';
        }

        return implode('', $output);
    }

    /**
     * Creates and returns the HTML code for the Admin Panel in the TSFE frontend.
     *
     * @throws \UnexpectedValueException
     * @return string HTML for the Admin Panel
     */
    public function display()
    {
        $this->getLanguageService()->includeLLFile('EXT:lang/Resources/Private/Language/locallang_tsfe.xlf');

        $moduleContent = '';
        $moduleContent .= $this->getModule('preview', $this->getPreviewModule());
        $moduleContent .= $this->getModule('cache', $this->getCacheModule());
        $moduleContent .= $this->getModule('edit', $this->getEditModule());
        $moduleContent .= $this->getModule('tsdebug', $this->getTSDebugModule());
        $moduleContent .= $this->getModule('info', $this->getInfoModule());

        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_adminpanel.php']['extendAdminPanel'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_adminpanel.php']['extendAdminPanel'] as $classRef) {
                $hookObject = GeneralUtility::getUserObj($classRef);
                if (!$hookObject instanceof AdminPanelViewHookInterface) {
                    throw new \UnexpectedValueException($classRef . ' must implement interface ' . AdminPanelViewHookInterface::class, 1311942539);
                }
                $content = $hookObject->extendAdminPanel($moduleContent, $this);
                if ($content) {
                    $moduleContent .= '<div class="typo3-adminPanel-section typo3-adminPanel-section-open">';
                    $moduleContent .= '  <div class="typo3-adminPanel-section-body">';
                    $moduleContent .= '    ' . $content;
                    $moduleContent .= '  </div>';
                    $moduleContent .= '</div>';
                }
            }
        }

        $output = [];
        $output[] = '<!-- TYPO3 Admin panel start -->';
        $output[] = '<a id="TSFE_ADMIN_PANEL"></a>';
        $output[] = '<form id="TSFE_ADMIN_PANEL_FORM" name="TSFE_ADMIN_PANEL_FORM" style="display: none;" action="' . htmlspecialchars(GeneralUtility::getIndpEnv('TYPO3_REQUEST_SCRIPT')) . '#TSFE_ADMIN_PANEL" method="get" onsubmit="document.forms.TSFE_ADMIN_PANEL_FORM[\'TSFE_ADMIN_PANEL[DUMMY]\'].value=Math.random().toString().substring(2,8)">';
        if (!GeneralUtility::_GET('id')) {
            $output[] = '<input type="hidden" name="id" value="' . $this->getTypoScriptFrontendController()->id . '" />';
        }
        // The dummy field is needed for Firefox: to force a page reload on submit
        // which must change the form value with JavaScript (see "onsubmit" attribute of the "form" element")
        $output[] = '  <input type="hidden" name="TSFE_ADMIN_PANEL[DUMMY]" value="" />';
        foreach (GeneralUtility::_GET() as $key => $value) {
            if ($key !== 'TSFE_ADMIN_PANEL') {
                if (is_array($value)) {
                    $output[] = $this->getHiddenFields($key, $value);
                } else {
                    $output[] = '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '" />';
                }
            }
        }
        $output[] = '  <input type="hidden" name="TSFE_ADMIN_PANEL[display_top]" value="0" />';
        $output[] = '  <input id="typo3AdminPanelEnable" type="checkbox" onchange="document.TSFE_ADMIN_PANEL_FORM.submit();" name="TSFE_ADMIN_PANEL[display_top]" value="1"' . ($this->getBackendUser()->uc['TSFE_adminConfig']['display_top'] ? ' checked="checked"' : '') . '/>';
        $output[] = '  <input id="typo3AdminPanelCollapse" type="checkbox" value="1" />';
        $output[] = '  <div class="typo3-adminPanel typo3-adminPanel-state-' . ($this->getBackendUser()->uc['TSFE_adminConfig']['display_top'] ? 'open' : 'closed') . '">';
        $output[] = '    <div class="typo3-adminPanel-header">';
        $output[] = '      <span class="typo3-adminPanel-header-title">' . $this->extGetLL('adminPanelTitle') . '</span>';
        $output[] = '      <span class="typo3-adminPanel-header-user">' . htmlspecialchars($this->getBackendUser()->user['username']) . '</span>';
        $output[] = '      <label for="typo3AdminPanelEnable" class="typo3-adminPanel-header-enable">';
        $output[] = '        <span class="typo3-adminPanel-header-enable-enabled">';
        $output[] = '          ' . $this->iconFactory->getIcon('actions-edit-hide', Icon::SIZE_SMALL)->render('inline');
        $output[] = '        </span>';
        $output[] = '        <span class="typo3-adminPanel-header-enable-disabled">';
        $output[] = '          ' . $this->iconFactory->getIcon('actions-edit-unhide', Icon::SIZE_SMALL)->render('inline');
        $output[] = '        </span>';
        $output[] = '      </label>';
        $output[] = '      <label for="typo3AdminPanelCollapse" class="typo3-adminPanel-header-collapse">';
        $output[] = '        <span class="typo3-adminPanel-header-collapse-enabled">';
        $output[] = '          ' . $this->iconFactory->getIcon('actions-view-list-collapse', Icon::SIZE_SMALL)->render('inline');
        $output[] = '        </span>';
        $output[] = '        <span class="typo3-adminPanel-header-collapse-disabled">';
        $output[] = '          ' . $this->iconFactory->getIcon('actions-view-list-expand', Icon::SIZE_SMALL)->render('inline');
        $output[] = '        </span>';
        $output[] = '      </label>';
        $output[] = '    </div>';
        if ($moduleContent && $this->extNeedUpdate) {
            $output[] = '<div class="typo3-adminPanel-actions">';
            $output[] = '  <input class="typo3-adminPanel-btn typo3-adminPanel-btn-dark" type="submit" value="' . $this->extGetLL('update') . '" />';
            $output[] = '</div>';
        }
        $output[] = '    <div class="typo3-adminPanel-body">';
        $output[] = '      ' . $moduleContent;
        $output[] = '    </div>';
        $output[] = '  </div>';
        $output[] = '</form>';
        if ($this->getBackendUser()->uc['TSFE_adminConfig']['display_top']) {
            $frontendPathExtBackend = htmlspecialchars($this->getTypoScriptFrontendController()->absRefPrefix) . ExtensionManagementUtility::siteRelPath('backend');
            $output[] = '<script type="text/javascript" src="' . $frontendPathExtBackend . 'Resources/Public/JavaScript/jsfunc.evalfield.js"></script>';
            $output[] = '<script type="text/javascript">/*<![CDATA[*/' . GeneralUtility::minifyJavaScript('
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
				}') . '/*]]>*/</script>';
            $output[] = '<script language="javascript" type="text/javascript">' . $this->extJSCODE . '</script>';
        }
        $frontendPathExtFrontend = htmlspecialchars($this->getTypoScriptFrontendController()->absRefPrefix) . ExtensionManagementUtility::siteRelPath('frontend');
        $output[] = '<link type="text/css" rel="stylesheet" href="' . $frontendPathExtFrontend . 'Resources/Public/Css/adminpanel.css" media="all" />';
        $output[] = $this->getAdminPanelHeaderData();
        $output[] = '<!-- TYPO3 admin panel end -->';

        return implode('', $output);
    }

    /**
     * Fetches recursively all GET parameters as hidden fields.
     * Called from display()
     *
     * @param string $key Current key
     * @param array $val Current value
     * @return string Hidden fields HTML-code
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
        $output = [];
        if ($this->getBackendUser()->uc['TSFE_adminConfig']['display_preview']) {
            $this->extNeedUpdate = true;

            $output[] = '<div class="typo3-adminPanel-form-group">';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[preview_showHiddenPages]" value="0" />';
            $output[] = '    <label for="preview_showHiddenPages">';
            $output[] = '      <input type="checkbox" id="preview_showHiddenPages" name="TSFE_ADMIN_PANEL[preview_showHiddenPages]" value="1"' . ($this->getBackendUser()->uc['TSFE_adminConfig']['preview_showHiddenPages'] ? ' checked="checked"' : '') . ' />';
            $output[] = '      ' . $this->extGetLL('preview_showHiddenPages');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[preview_showHiddenRecords]" value="0" />';
            $output[] = '    <label for="preview_showHiddenRecords">';
            $output[] = '      <input type="checkbox" id="preview_showHiddenRecords" name="TSFE_ADMIN_PANEL[preview_showHiddenRecords]" value="1"' . ($this->getBackendUser()->uc['TSFE_adminConfig']['preview_showHiddenRecords'] ? ' checked="checked"' : '') . ' />';
            $output[] = '      ' . $this->extGetLL('preview_showHiddenRecords');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[preview_showFluidDebug]" value="0" />';
            $output[] = '    <label for="preview_showFluidDebug">';
            $output[] = '      <input type="checkbox" id="preview_showFluidDebug" name="TSFE_ADMIN_PANEL[preview_showFluidDebug]" value="1"' . ($this->getBackendUser()->uc['TSFE_adminConfig']['preview_showFluidDebug'] ? ' checked="checked"' : '') . ' />';
            $output[] = '      ' . $this->extGetLL('preview_showFluidDebug');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '</div>';

            // Simulate date
            $output[] = '<div class="typo3-adminPanel-form-group">';
            $output[] = '  <label for="preview_simulateDate">';
            $output[] = '    ' . $this->extGetLL('preview_simulateDate');
            $output[] = '  </label>';
            $output[] = '  <input type="text" id="preview_simulateDate" name="TSFE_ADMIN_PANEL[preview_simulateDate]_hr" onchange="TSFEtypo3FormFieldGet(' . GeneralUtility::quoteJSvalue('TSFE_ADMIN_PANEL[preview_simulateDate]') . ', \'datetime\', \'\', 1,0);" />';
            // the hidden field must be placed after the _hr field to avoid the timestamp being overridden by the date string
            $output[] = '  <input type="hidden" name="TSFE_ADMIN_PANEL[preview_simulateDate]" value="' . htmlspecialchars($this->getBackendUser()->uc['TSFE_adminConfig']['preview_simulateDate']) . '" />';
            $output[] = '</div>';
            $this->extJSCODE .= 'TSFEtypo3FormFieldSet("TSFE_ADMIN_PANEL[preview_simulateDate]", "datetime", "", 0, 0);';

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
                   $this->getBackendUser()->getPagePermsClause(1)
               )
               ->execute()
               ->fetchColumn(0);
            if ($optionCount > 0) {
                $result = $queryBuilder->select('fe_groups.uid', 'fe_groups.title')
                    ->from('fe_groups')
                    ->from('pages')
                    ->where(
                        $queryBuilder->expr()->eq('pages.uid', $queryBuilder->quoteIdentifier('fe_groups.pid')),
                        $this->getBackendUser()->getPagePermsClause(1)
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
                    $output[] = '<option value="' . $row['uid'] . '" ' . ($this->getBackendUser()->uc['TSFE_adminConfig']['preview_simulateUserGroup'] == $row['uid'] ? ' selected="selected"' : '') . '>';
                    $output[] = htmlspecialchars(($row['title'] . ' [' . $row['uid'] . ']'));
                    $output[] = '</option>';
                }
                $output[] = '  </select>';
                $output[] = '</div>';
            }
        }
        return implode('', $output);
    }

    /**
     * Creates the content for the "cache" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     * @see display()
     */
    protected function getCacheModule()
    {
        $output = [];
        $beUser = $this->getBackendUser();
        if ($beUser->uc['TSFE_adminConfig']['display_cache']) {
            $this->extNeedUpdate = true;

            $output[] = '<div class="typo3-adminPanel-form-group">';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[cache_noCache]" value="0" />';
            $output[] = '    <label for="cache_noCache">';
            $output[] = '      <input type="checkbox" id="cache_noCache" name="TSFE_ADMIN_PANEL[cache_noCache]" value="1"' . ($this->getBackendUser()->uc['TSFE_adminConfig']['cache_noCache'] ? ' checked="checked"' : '') . ' />';
            $output[] = '      ' . $this->extGetLL('cache_noCache');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '</div>';

            $levels = $beUser->uc['TSFE_adminConfig']['cache_clearCacheLevels'];
            $output[] = '<div class="typo3-adminPanel-form-group">';
            $output[] = '  <label for="cache_clearCacheLevels">';
            $output[] = '    ' . $this->extGetLL('cache_clearLevels');
            $output[] = '  </label>';
            $output[] = '  <select id="cache_clearCacheLevels" name="TSFE_ADMIN_PANEL[cache_clearCacheLevels]">';
            $output[] = '    <option value="0"' . ($levels == 0 ? ' selected="selected"' : '') . '>';
            $output[] = '      ' . $this->extGetLL('div_Levels_0');
            $output[] = '    </option>';
            $output[] = '    <option value="1"' . ($levels == 1 ? ' selected="selected"' : '') . '>';
            $output[] = '      ' . $this->extGetLL('div_Levels_1');
            $output[] = '    </option>';
            $output[] = '    <option value="2"' . ($levels == 2 ? ' selected="selected"' : '') . '>';
            $output[] = '      ' . $this->extGetLL('div_Levels_2');
            $output[] = '    </option>';
            $output[] = '  </select>';
            $output[] = '</div>';

            $output[] = '<div class="typo3-adminPanel-form-group">';
            $output[] = '  <input type="hidden" name="TSFE_ADMIN_PANEL[cache_clearCacheId]" value="' . (int)$GLOBALS['TSFE']->id . '" />';
            $output[] = '  <input class="typo3-adminPanel-btn typo3-adminPanel-btn-default" type="submit" value="' . $this->extGetLL('update') . '" />';
            $output[] = '</div>';

            // Generating tree:
            $depth = (int)$this->extGetFeAdminValue('cache', 'clearCacheLevels');
            $outTable = '';
            $tsfe = $this->getTypoScriptFrontendController();
            $beUser->extPageInTreeInfo = [];
            $beUser->extPageInTreeInfo[] = [
                $tsfe->page['uid'],
                htmlspecialchars($tsfe->page['title']),
                $depth + 1
            ];
            $beUser->extGetTreeList(
                $tsfe->id,
                $depth,
                0,
                $beUser->getPagePermsClause(1)
            );
            $output[] = '<div class="typo3-adminPanel-table-overflow">';
            $output[] = '<table class="typo3-adminPanel-table">';
            $output[] = '  <thead>';
            $output[] = '    <tr>';
            $output[] = '      <th colspan="2">' . $this->extGetLL('cache_cacheEntries') . '</th>';
            $output[] = '    </tr>';
            $output[] = '  </thead>';
            $output[] = '  <tbody>';
            foreach ($beUser->extPageInTreeInfo as $key => $row) {
                $output[] = '<tr>';
                $output[] = '  <td>';
                $output[] = '    <span style="width: ' . ($depth + 1 - $row[2]) * 5 . 'px; height: 1px; display: inline-block;"></span>';
                $output[] = '    ' . $this->iconFactory->getIcon('apps-pagetree-page-default', Icon::SIZE_SMALL)->render() . htmlspecialchars($row[1]);
                $output[] = '  </td>';
                $output[] = '  <td>' . $beUser->extGetNumberOfCachedPages($row[0]) . '</td>';
                $output[] = '</tr>';
            }
            $output[] = '  <tbody>';
            $output[] = '</table>';
            $output[] = '</div>';

            $output[] = '<div class="typo3-adminPanel-form-group">';
            $output[] = '  <input class="typo3-adminPanel-btn typo3-adminPanel-btn-default" type="submit" name="TSFE_ADMIN_PANEL[action][clearCache]" value="' . $this->extGetLL('cache_doit') . '" />';
            $output[] = '</div>';
        }
        return implode('', $output);
    }

    /**
     * Creates the content for the "edit" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     * @see display()
     */
    protected function getEditModule()
    {
        $output = [];
        if ($this->getBackendUser()->uc['TSFE_adminConfig']['display_edit']) {
            $this->extNeedUpdate = true;

            // If another page module was specified, replace the default Page module with the new one
            $newPageModule = trim($this->getBackendUser()->getTSConfigVal('options.overridePageModule'));
            $pageModule = BackendUtility::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';

            if ($this->extFeEditLoaded) {
                $output[] = '<div class="typo3-adminPanel-form-group">';
                $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
                $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[edit_displayFieldIcons]" value="0" />';
                $output[] = '    <label for="edit_displayFieldIcons">';
                $output[] = '      <input type="checkbox" id="edit_displayFieldIcons" name="TSFE_ADMIN_PANEL[edit_displayFieldIcons]" value="1"' . ($this->getBackendUser()->uc['TSFE_adminConfig']['edit_displayFieldIcons'] ? ' checked="checked"' : '') . ' />';
                $output[] = '      ' . $this->extGetLL('edit_displayFieldIcons');
                $output[] = '    </label>';
                $output[] = '  </div>';
                $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
                $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[edit_displayIcons]" value="0" />';
                $output[] = '    <label for="edit_displayIcons">';
                $output[] = '      <input type="checkbox" id="edit_displayIcons" name="TSFE_ADMIN_PANEL[edit_displayIcons]" value="1"' . ($this->getBackendUser()->uc['TSFE_adminConfig']['edit_displayIcons'] ? ' checked="checked"' : '') . ' />';
                $output[] = '      ' . $this->extGetLL('edit_displayIcons');
                $output[] = '    </label>';
                $output[] = '  </div>';
                $output[] = '</div>';
            }

            $output[] = $this->ext_makeToolBar();

            if (!GeneralUtility::_GP('ADMCMD_view')) {
                $onClick = '
                    if (parent.opener && parent.opener.top && parent.opener.top.TS) {
                        parent.opener.top.fsMod.recentIds["web"]=' . (int)$this->getTypoScriptFrontendController()->page['uid'] . ';
                        if (parent.opener.top && parent.opener.top.nav_frame && parent.opener.top.nav_frame.refresh_nav) {
                            parent.opener.top.nav_frame.refresh_nav();
                        }
                        parent.opener.top.goToModule("' . $pageModule . '");
                        parent.opener.top.focus();
                    } else {
                        vHWin=window.open(' . GeneralUtility::quoteJSvalue(BackendUtility::getBackendScript()) . ',' . GeneralUtility::quoteJSvalue(md5('Typo3Backend-' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'])) . ');
                        vHWin.focus();
                    }
                    return false;
                ';
                $output[] = '<div class="typo3-adminPanel-form-group">';
                $output[] = '  <a class="typo3-adminPanel-btn typo3-adminPanel-btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '">';
                $output[] = '    ' . $this->extGetLL('edit_openAB');
                $output[] = '  </a>';
                $output[] = '</div>';
            }
        }
        return implode('', $output);
    }

    /**
     * Creates the content for the "tsdebug" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     * @see display()
     */
    protected function getTSDebugModule()
    {
        $output = [];
        $beuser = $this->getBackendUser();
        if ($beuser->uc['TSFE_adminConfig']['display_tsdebug']) {
            $this->extNeedUpdate = true;

            $output[] = '<div class="typo3-adminPanel-form-group">';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_tree]" value="0" />';
            $output[] = '    <label for="tsdebug_tree">';
            $output[] = '      <input type="checkbox" id="tsdebug_tree" name="TSFE_ADMIN_PANEL[tsdebug_tree]" value="1"' . ($this->getBackendUser()->uc['TSFE_adminConfig']['tsdebug_tree'] ? ' checked="checked"' : '') . ' />';
            $output[] = '      ' . $this->extGetLL('tsdebug_tree');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayTimes]" value="0" />';
            $output[] = '    <label for="tsdebug_displayTimes">';
            $output[] = '      <input type="checkbox" id="tsdebug_displayTimes" name="TSFE_ADMIN_PANEL[tsdebug_displayTimes]" value="1"' . ($this->getBackendUser()->uc['TSFE_adminConfig']['tsdebug_displayTimes'] ? ' checked="checked"' : '') . ' />';
            $output[] = '      ' . $this->extGetLL('tsdebug_displayTimes');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayMessages]" value="0" />';
            $output[] = '    <label for="tsdebug_displayMessages">';
            $output[] = '      <input type="checkbox" id="tsdebug_displayMessages" name="TSFE_ADMIN_PANEL[tsdebug_displayMessages]" value="1"' . ($this->getBackendUser()->uc['TSFE_adminConfig']['tsdebug_displayMessages'] ? ' checked="checked"' : '') . ' />';
            $output[] = '      ' . $this->extGetLL('tsdebug_displayMessages');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_LR]" value="0" />';
            $output[] = '    <label for="tsdebug_LR">';
            $output[] = '      <input type="checkbox" id="tsdebug_LR" name="TSFE_ADMIN_PANEL[tsdebug_LR]" value="1"' . ($this->getBackendUser()->uc['TSFE_adminConfig']['tsdebug_LR'] ? ' checked="checked"' : '') . ' />';
            $output[] = '      ' . $this->extGetLL('tsdebug_LR');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayContent]" value="0" />';
            $output[] = '    <label for="tsdebug_displayContent">';
            $output[] = '      <input type="checkbox" id="tsdebug_displayContent" name="TSFE_ADMIN_PANEL[tsdebug_displayContent]" value="1"' . ($this->getBackendUser()->uc['TSFE_adminConfig']['tsdebug_displayContent'] ? ' checked="checked"' : '') . ' />';
            $output[] = '      ' . $this->extGetLL('tsdebug_displayContent');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '  <div class="typo3-adminPanel-form-group-checkbox">';
            $output[] = '    <input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_forceTemplateParsing]" value="0" />';
            $output[] = '    <label for="tsdebug_forceTemplateParsing">';
            $output[] = '      <input type="checkbox" id="tsdebug_forceTemplateParsing" name="TSFE_ADMIN_PANEL[tsdebug_forceTemplateParsing]" value="1"' . ($this->getBackendUser()->uc['TSFE_adminConfig']['tsdebug_forceTemplateParsing'] ? ' checked="checked"' : '') . ' />';
            $output[] = '      ' . $this->extGetLL('tsdebug_forceTemplateParsing');
            $output[] = '    </label>';
            $output[] = '  </div>';
            $output[] = '</div>';

            $timeTracker = $this->getTimeTracker();
            $timeTracker->printConf['flag_tree'] = $this->extGetFeAdminValue('tsdebug', 'tree');
            $timeTracker->printConf['allTime'] = $this->extGetFeAdminValue('tsdebug', 'displayTimes');
            $timeTracker->printConf['flag_messages'] = $this->extGetFeAdminValue('tsdebug', 'displayMessages');
            $timeTracker->printConf['flag_content'] = $this->extGetFeAdminValue('tsdebug', 'displayContent');
            $output[] = $timeTracker->printTSlog();
        }
        return implode('', $output);
    }

    /**
     * Creates the content for the "info" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with two columns.
     * @see display()
     */
    protected function getInfoModule()
    {
        $output = [];
        $tsfe = $this->getTypoScriptFrontendController();
        if ($this->getBackendUser()->uc['TSFE_adminConfig']['display_info']) {
            // rows stored in tableArr consist of these columns:
            // 0: label (already html-escaped!)
            // 1: value (already html-escaped!)
            // 2: bool (default: false) whether to show column 0 in <strong>-tags
            $tableArr = [];
            if ($this->extGetFeAdminValue('cache', 'noCache')) {
                $theBytes = 0;
                $count = 0;
                if (!empty($tsfe->imagesOnPage)) {
                    $tableArr[] = [$this->extGetLL('info_imagesOnPage'), count($tsfe->imagesOnPage), true];
                    foreach ($GLOBALS['TSFE']->imagesOnPage as $file) {
                        $fs = @filesize($file);
                        $tableArr[] = [TAB . htmlspecialchars($file), GeneralUtility::formatSize($fs)];
                        $theBytes += $fs;
                        $count++;
                    }
                }
                // Add an empty line
                $tableArr[] = [$this->extGetLL('info_imagesSize'), GeneralUtility::formatSize($theBytes), true];
                $tableArr[] = [$this->extGetLL('info_DocumentSize'), GeneralUtility::formatSize(strlen($tsfe->content)), true];
                $tableArr[] = ['', ''];
            }
            $tableArr[] = [$this->extGetLL('info_id'), (int)$tsfe->id];
            $tableArr[] = [$this->extGetLL('info_type'), (int)$tsfe->type];
            $tableArr[] = [$this->extGetLL('info_groupList'), htmlspecialchars($tsfe->gr_list)];
            $tableArr[] = [$this->extGetLL('info_noCache'), $this->extGetLL('info_noCache_' . ($tsfe->no_cache ? 'no' : 'yes'))];
            $tableArr[] = [$this->extGetLL('info_countUserInt'), count($tsfe->config['INTincScript'] ?? [])];

            if (!empty($tsfe->fe_user->user['uid'])) {
                $tableArr[] = [$this->extGetLL('info_feuserName'), htmlspecialchars($tsfe->fe_user->user['username'])];
                $tableArr[] = [$this->extGetLL('info_feuserId'), htmlspecialchars($tsfe->fe_user->user['uid'])];
            }

            $tableArr[] = [$this->extGetLL('info_totalParsetime'), htmlspecialchars($this->getTimeTracker()->getParseTime() . ' ms'), true];
            $table = '';
            foreach ($tableArr as $key => $arr) {
                $label = !empty($arr[2]) ? '<strong>' . $arr[0] . '</strong>' : $arr[0];
                $value = (string)$arr[1] !== '' ? $arr[1] : '';
                // the "weird" construct here is intentional.
                // reasoning: we ALWAYS encode when giving things to the view.
                // But in this case $label and $value come in encoded, hence the double function call.
                $table .= '
                    <tr>
                        <td>' . htmlspecialchars(htmlspecialchars_decode($label)) . '</td>
                        <td>' . htmlspecialchars(htmlspecialchars_decode($value)) . '</td>
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
        return  $this->linkSectionHeader($sectionSuffix, $this->extGetLL($sectionSuffix));
    }

    /**
     * Wraps a string in a link which will open/close a certain part of the Admin Panel
     *
     * @param string $sectionSuffix The code for the display_ label/key
     * @param string $sectionTitle Title (HTML-escaped)
     * @param string $className The classname for the <a> tag
     * @return string $className Linked input string
     * @see extGetHead()
     */
    public function linkSectionHeader($sectionSuffix, $sectionTitle, $className = '')
    {
        $onclick = 'document.TSFE_ADMIN_PANEL_FORM[' . GeneralUtility::quoteJSvalue('TSFE_ADMIN_PANEL[display_' . $sectionSuffix . ']') . '].value=' . ($this->getBackendUser()->uc['TSFE_adminConfig']['display_' . $sectionSuffix] ? '0' : '1') . ';document.TSFE_ADMIN_PANEL_FORM.submit();return false;';

        $output = [];
        $output[] = '<span class="typo3-adminPanel-section-title-identifier"></span>';
        $output[] = '<a href="javascript:void(0)" onclick="' . htmlspecialchars($onclick) . '">';
        $output[] = '  ' . $sectionTitle;
        $output[] = '</a>';
        $output[] = '<input type="hidden" name="TSFE_ADMIN_PANEL[display_' . $sectionSuffix . ']" value="' . (int)$this->isAdminModuleOpen($sectionSuffix) . '" />';

        return  implode('', $output);
    }

    /**
     * Returns a row (with 4 columns) for content in a section of the Admin Panel.
     * It will take $pre as a key to a label to display and $element as the content to put into the forth cell.
     *
     * @param string $title Key to label
     * @param string $content The HTML content for the forth table cell.
     * @param string $checkbox The HTML for a checkbox or hidden fields.
     * @param string $innerDivClass The Class attribute for the td element.
     * @param string $outerDivClass The Class attribute for the tr element.
     * @return string HTML table row.
     * @see extGetHead()
     */
    public function extGetItem($title, $content = '', $checkbox = '', $outerDivClass = null, $innerDivClass = null)
    {
        $title = $title ? '<label for="' . htmlspecialchars($title) . '">' . $this->extGetLL($title) . '</label>' : '';
        $out = '';
        $out .= (string)$outerDivClass ? '<div class="' . htmlspecialchars($outerDivClass) . '">' : '<div>';
        $out .= (string)$innerDivClass ? '<div class="' . htmlspecialchars($innerDivClass) . '">' : '<div>';
        $out .= $checkbox . $title . $content . '</div></div>';
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
        $perms = $this->getBackendUser()->calcPerms($tsfe->page);
        $langAllowed = $this->getBackendUser()->checkLanguageAccess($tsfe->sys_language_uid);
        $id = $tsfe->id;
        $returnUrl = GeneralUtility::getIndpEnv('REQUEST_URI');
        $classes = 'typo3-adminPanel-btn typo3-adminPanel-btn-default';
        $output = [];
        $output[] = '<div class="typo3-adminPanel-form-group">';
        $output[] = '  <div class="typo3-adminPanel-btn-group" role="group">';

        // History
        $link = BackendUtility::getModuleUrl(
            'record_history',
            [
                'element' => 'pages:' . $id,
                'returnUrl' => $returnUrl
            ]
        );
        $title = $this->extGetLL('edit_recordHistory');
        $output[] = '<a class="' . $classes . '" href="' . htmlspecialchars($link) . '#latest" title="' . $title . '">';
        $output[] = '  ' . $this->iconFactory->getIcon('actions-document-history-open', Icon::SIZE_SMALL)->render();
        $output[] = '</a>';

        // New Content
        if ($perms & Permission::CONTENT_EDIT && $langAllowed) {
            $linkParameters = [
                'id' => $id,
                'returnUrl' => $returnUrl,
            ];
            if (!empty($tsfe->sys_language_uid)) {
                $linkParameters['sys_language_uid'] = $tsfe->sys_language_uid;
            }
            $link = BackendUtility::getModuleUrl($moduleName, $linkParameters);
            $icon = $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render();
            $title = $this->extGetLL('edit_newContentElement');
            $output[] = '<a class="' . $classes . '" href="' . htmlspecialchars($link) . '" title="' . $title . '">';
            $output[] = '  ' . $icon;
            $output[] = '</a>';
        }

        // Move Page
        if ($perms & Permission::PAGE_EDIT) {
            $link = BackendUtility::getModuleUrl(
                'move_element',
                [
                    'table' => 'pages',
                    'uid' => $id,
                    'returnUrl' => $returnUrl
                ]
            );
            $icon = $this->iconFactory->getIcon('actions-document-move', Icon::SIZE_SMALL)->render();
            $title = $this->extGetLL('edit_move_page');
            $output[] = '<a class="' . $classes . '" href="' . htmlspecialchars($link) . '" title="' . $title . '">';
            $output[] = '  ' . $icon;
            $output[] = '</a>';
        }

        // New Page
        if ($perms & Permission::PAGE_NEW) {
            $link = BackendUtility::getModuleUrl(
                'db_new',
                [
                    'id' => $id,
                    'pagesOnly' => 1,
                    'returnUrl' => $returnUrl
                ]
            );
            $icon = $this->iconFactory->getIcon('actions-page-new', Icon::SIZE_SMALL)->render();
            $title = $this->extGetLL('edit_newPage');
            $output[] = '<a class="' . $classes . '" href="' . htmlspecialchars($link) . '" title="' . $title . '">';
            $output[] = '  ' . $icon;
            $output[] = '</a>';
        }

        // Edit Page
        if ($perms & Permission::PAGE_EDIT) {
            $link = BackendUtility::getModuleUrl(
                'record_edit',
                [
                    'edit[pages][' . $id . ']' => 'edit',
                    'noView' => 1,
                    'returnUrl' => $returnUrl
                ]
            );
            $icon = $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render();
            $title = $this->extGetLL('edit_editPageProperties');
            $output[] = '<a class="' . $classes . '" href="' . htmlspecialchars($link) . '" title="' . $title . '">';
            $output[] = '  ' . $icon;
            $output[] = '</a>';
        }

        // Edit Page Overlay
        if ($perms & Permission::PAGE_EDIT && $tsfe->sys_language_uid && $langAllowed) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages_language_overlay');
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
            $row = $queryBuilder
                ->select('uid', 'pid', 't3ver_state')
                ->from('pages_language_overlay')
                ->where(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter($tsfe->sys_language_uid, \PDO::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->execute()
                ->fetch();
            $tsfe->sys_page->versionOL('pages_language_overlay', $row);
            if (is_array($row)) {
                $link = BackendUtility::getModuleUrl(
                    'record_edit',
                    [
                        'edit[pages_language_overlay][' . $row['uid'] . ']' => 'edit',
                        'noView' => 1,
                        'returnUrl' => $returnUrl
                    ]
                );
                $icon = $this->iconFactory->getIcon('mimetypes-x-content-page-language-overlay', Icon::SIZE_SMALL)->render();
                $title = $this->extGetLL('edit_editPageOverlay');
                $output[] = '<a class="' . $classes . '" href="' . htmlspecialchars($link) . '" title="' . $title . '">';
                $output[] = '  ' . $icon;
                $output[] = '</a>';
            }
        }

        // Open list view
        if ($this->getBackendUser()->check('modules', 'web_list')) {
            $link = BackendUtility::getModuleUrl(
                'web_list',
                [
                    'id' => $id,
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                ]
            );
            $icon = $this->iconFactory->getIcon('actions-system-list-open', Icon::SIZE_SMALL)->render();
            $title = $this->extGetLL('edit_db_list');
            $output[] = '<a class="' . $classes . '" href="' . htmlspecialchars($link) . '" title="' . $title . '">';
            $output[] = '  ' . $icon;
            $output[] = '</a>';
        }

        $output[] = '  </div>';
        $output[] = '</div>';
        return implode('', $output);
    }

    /**
     * Translate given key
     *
     * @param string $key Key for a label in the $LOCAL_LANG array of "sysext/lang/Resources/Private/Language/locallang_tsfe.xlf
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
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker()
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }
}
