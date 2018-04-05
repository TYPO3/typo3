<?php

namespace TYPO3\CMS\Adminpanel\View;

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

use TYPO3\CMS\Adminpanel\Modules\AdminPanelModuleInterface;
use TYPO3\CMS\Adminpanel\Service\EditToolbarService;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * View class for the admin panel in frontend editing.
 *
 * @internal
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
     * Array of adminPanel modules
     *
     * @var AdminPanelModuleInterface[]
     */
    protected $modules = [];

    /**
     * @var array
     */
    protected $configuration;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Setter for injecting new-style modules
     *
     * @see \TYPO3\CMS\Adminpanel\Controller\MainController::render()
     * @param array $modules
     */
    public function setModules(array $modules): void
    {
        $this->modules = $modules;
    }

    /**
     * Returns a link tag with the admin panel stylesheet
     * defined using TBE_STYLES
     *
     * @return string
     */
    protected function getAdminPanelStylesheet(): string
    {
        $result = '';
        if (!empty($GLOBALS['TBE_STYLES']['stylesheets']['admPanel'])) {
            $stylesheet = GeneralUtility::locationHeaderUrl($GLOBALS['TBE_STYLES']['stylesheets']['admPanel']);
            $result = '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($stylesheet) . '" />';
        }
        return $result;
    }

    /**
     * Render a single module with header panel
     *
     * @param \TYPO3\CMS\Adminpanel\Modules\AdminPanelModuleInterface $module
     * @return string
     */
    protected function getModule(AdminPanelModuleInterface $module): string
    {
        $output = [];

        if ($module->isEnabled()) {
            $output[] = '<div class="typo3-adminPanel-section typo3-adminPanel-section-' .
                        ($module->isOpen() ? 'open' : 'closed') .
                        '">';
            $output[] = '  <div class="typo3-adminPanel-section-title">';
            $output[] = '    ' . $this->getSectionOpenerLink($module);
            $output[] = '  </div>';
            if ($module->isOpen()) {
                $output[] = '<div class="typo3-adminPanel-section-body">';
                $output[] = '  ' . $module->getContent();
                $output[] = '</div>';
            }
            $output[] = '</div>';
        }

        foreach ($module->getJavaScriptFiles() as $javaScriptFile) {
            $output[] =
                '<script src="' .
                PathUtility::getAbsoluteWebPath(GeneralUtility::getFileAbsFileName($javaScriptFile)) .
                '"></script>';
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

        if ($this->isAdminPanelActivated()) {
            foreach ($this->modules as $module) {
                if ($module->isOpen()) {
                    $this->extNeedUpdate = !$this->extNeedUpdate ? $module->showFormSubmitButton() : true;
                    $this->extJSCODE .= $module->getAdditionalJavaScriptCode();
                }
                $moduleContent .= $this->getModule($module);
            }

            foreach (
                $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_adminpanel.php']['extendAdminPanel']
                ??
                [] as $className
            ) {
                trigger_error(
                    'The hook $GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'tslib/class.tslib_adminpanel.php\'][\'extendAdminPanel\'] is deprecated, register an AdminPanelModule instead.',
                    E_USER_DEPRECATED
                );
                $hookObject = GeneralUtility::makeInstance($className);
                if (!$hookObject instanceof AdminPanelViewHookInterface) {
                    throw new \UnexpectedValueException(
                        $className . ' must implement interface ' . AdminPanelViewHookInterface::class,
                        1311942539
                    );
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
        $output[] = '  <input id="typo3AdminPanelEnable" type="checkbox" onchange="document.TSFE_ADMIN_PANEL_FORM.submit();" name="TSFE_ADMIN_PANEL[display_top]" value="1"' .
                    ($this->isAdminPanelActivated() ? ' checked="checked"' : '') .
                    '/>';
        $output[] = '  <input id="typo3AdminPanelCollapse" type="checkbox" value="1" />';
        $output[] = '  <div class="typo3-adminPanel typo3-adminPanel-state-' .
                    ($this->isAdminPanelActivated() ? 'open' : 'closed') .
                    '">';
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
            $evalFieldJavaScriptFile = GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Public/JavaScript/jsfunc.evalfield.js');
            $output[] = '<script type="text/javascript" src="' . htmlspecialchars(PathUtility::getAbsoluteWebPath($evalFieldJavaScriptFile)) . '"></script>';
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
        $cssFileLocation = GeneralUtility::getFileAbsFileName('EXT:adminpanel/Resources/Public/Css/adminpanel.css');
        $output[] = '<link type="text/css" rel="stylesheet" href="' . htmlspecialchars(PathUtility::getAbsoluteWebPath($cssFileLocation)) . '" media="all" />';
        $output[] = $this->getAdminPanelStylesheet();
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

    /**
     * Returns true if admin panel was activated
     * (switched "on" via GUI)
     *
     * @return bool
     */
    protected function isAdminPanelActivated(): bool
    {
        return $this->getBackendUser()->uc['TSFE_adminConfig']['display_top'] ?? false;
    }

    /*****************************************************
     * Admin Panel Layout Helper functions
     ****************************************************/

    /**
     * Wraps a string in a link which will open/close a certain part of the Admin Panel
     *
     * @param AdminPanelModuleInterface $module
     * @return string
     */
    protected function getSectionOpenerLink(AdminPanelModuleInterface $module): string
    {
        $identifier = $module->getIdentifier();
        $onclick = 'document.TSFE_ADMIN_PANEL_FORM[' .
                   GeneralUtility::quoteJSvalue('TSFE_ADMIN_PANEL[display_' . $identifier . ']') .
                   '].value=' .
                   ($this->getBackendUser()->uc['TSFE_adminConfig']['display_' . $identifier] ? '0' : '1') .
                   ';document.TSFE_ADMIN_PANEL_FORM.submit();return false;';

        $output = [];
        $output[] = '<span class="typo3-adminPanel-section-title-identifier"></span>';
        $output[] = '<a href="javascript:void(0)" onclick="' . htmlspecialchars($onclick) . '">';
        $output[] = '  ' . htmlspecialchars($module->getLabel());
        $output[] = '</a>';
        $output[] = '<input type="hidden" name="TSFE_ADMIN_PANEL[display_' .
                    $identifier .
                    ']" value="' .
                    (int)$module->isOpen() .
                    '" />';

        return implode('', $output);
    }

    /**
     * Creates the tool bar links for the "edit" section of the Admin Panel.
     *
     * @deprecated
     * @return string A string containing images wrapped in <a>-tags linking them to proper functions.
     */
    public function ext_makeToolBar()
    {
        trigger_error('', E_USER_DEPRECATED);
        $editToolbarService = GeneralUtility::makeInstance(EditToolbarService::class);
        return $editToolbarService->createToolbar();
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
     * @return \TYPO3\CMS\Core\Localization\LanguageService
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

    /*****************************************************
     * Admin Panel: Deprecated API
     ****************************************************/

    /**
     * Add an additional stylesheet
     *
     * @return string
     * @deprecated since TYPO3 v9 - implement AdminPanelModules via the new API (see AdminPanelModuleInterface)
     */
    public function getAdminPanelHeaderData()
    {
        trigger_error(
            'Deprecated since TYPO3 v9 - implement AdminPanelModules via the new API (see AdminPanelModuleInterface)',
            E_USER_DEPRECATED
        );
        return $this->getAdminPanelStylesheet();
    }

    /**
     * Checks if an Admin Panel section ("module") is available for the user. If so, TRUE is returned.
     *
     * @param string $key The module key, eg. "edit", "preview", "info" etc.
     * @deprecated but still called in FrontendBackendUserAuthentication (will be refactored in a separate step)
     * @return bool
     */
    public function isAdminModuleEnabled($key)
    {
        $result = false;
        // Returns TRUE if the module checked is "preview" and the forcePreview flag is set.
        if ($key === 'preview' && $this->ext_forcePreview) {
            $result = true;
        } elseif (!empty($this->configuration['enable.']['all'])) {
            $result = true;
        } elseif (!empty($this->configuration['enable.'][$key])) {
            $result = true;
        }
        return $result;
    }

    /**
     * Saves any change in settings made in the Admin Panel.
     *
     * @deprecated since TYPO3 v9 - implement AdminPanelModules via the new API (see AdminPanelModuleInterface)
     */
    public function saveConfigOptions()
    {
        trigger_error(
            'Deprecated since TYPO3 v9 - implement AdminPanelModules via the new API (see AdminPanelModuleInterface)',
            E_USER_DEPRECATED
        );
        $this->saveConfiguration();
    }

    /**
     * Returns the value for an Admin Panel setting.
     *
     * @param string $sectionName Module key
     * @param string $val Setting key
     * @return mixed The setting value
     * @deprecated Since TYPO3 v9 - implement AdminPanelModules via the new API (see AdminPanelModuleInterface)
     */
    public function extGetFeAdminValue($sectionName, $val = '')
    {
        trigger_error(
            'Deprecated since TYPO3 v9 - implement AdminPanelModules via the new API (see AdminPanelModuleInterface)',
            E_USER_DEPRECATED
        );
        if (!$this->isAdminModuleEnabled($sectionName)) {
            return null;
        }

        $beUser = $this->getBackendUser();
        // Exceptions where the values can be overridden (forced) from backend:
        // deprecated
        if (
            $sectionName === 'edit' && (
                $val === 'displayIcons' && $this->configuration['module.']['edit.']['forceDisplayIcons'] ||
                $val === 'displayFieldIcons' && $this->configuration['module.']['edit.']['forceDisplayFieldIcons'] ||
                $val === 'editNoPopup' && $this->configuration['module.']['edit.']['forceNoPopup']
            )
        ) {
            return true;
        }

        // Override all settings with user TSconfig
        if ($val && isset($this->configuration['override.'][$sectionName . '.'][$val])) {
            return $this->configuration['override.'][$sectionName . '.'][$val];
        }
        if (!$val && isset($this->configuration['override.'][$sectionName])) {
            return $this->configuration['override.'][$sectionName];
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
     * @deprecated since TYPO3 v9 - see AdminPanelModule: Preview
     */
    public function forcePreview()
    {
        trigger_error('Deprecated since TYPO3 v9, see AdminPanelModule: Preview', E_USER_DEPRECATED);
        $this->ext_forcePreview = true;
    }

    /**
     * Returns TRUE if admin panel module is open
     *
     * @param string $key Module key
     * @return bool TRUE, if the admin panel is open for the specified admin panel module key.
     * @deprecated Since TYPO3 v9 - implement AdminPanelModules via the new API
     */
    public function isAdminModuleOpen($key)
    {
        trigger_error('since TYPO3 v9 - use new AdminPanel API instead', E_USER_DEPRECATED);
        return $this->getBackendUser()->uc['TSFE_adminConfig']['display_top'] &&
               $this->getBackendUser()->uc['TSFE_adminConfig']['display_' . $key];
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
     * @deprecated since TYPO3 v9 - use new AdminPanel API instead
     */
    public function extGetItem($title, $content = '', $checkbox = '', $outerDivClass = null, $innerDivClass = null)
    {
        trigger_error('since TYPO3 v9 - use new AdminPanel API instead', E_USER_DEPRECATED);
        $title = $title ? '<label for="' . htmlspecialchars($title) . '">' . $this->extGetLL($title) . '</label>' : '';
        $out = '';
        $out .= (string)$outerDivClass ? '<div class="' . htmlspecialchars($outerDivClass) . '">' : '<div>';
        $out .= (string)$innerDivClass ? '<div class="' . htmlspecialchars($innerDivClass) . '">' : '<div>';
        $out .= $checkbox . $title . $content . '</div></div>';
        return $out;
    }

    /**
     * Returns a row (with colspan=4) which is a header for a section in the Admin Panel.
     * It will have a plus/minus icon and a label which is linked so that it submits the form which surrounds the whole Admin Panel when clicked, alterting the TSFE_ADMIN_PANEL[display_' . $pre . '] value
     * See the functions get*Module
     *
     * @param string $sectionSuffix The suffix to the display_ label. Also selects the label from the LOCAL_LANG array.
     * @return string HTML table row.
     * @see extGetItem()
     * @deprecated since TYPO3 v9 - use new AdminPanel API instead
     */
    public function extGetHead($sectionSuffix)
    {
        trigger_error('since TYPO3 v9 - use new AdminPanel API instead', E_USER_DEPRECATED);
        return $this->linkSectionHeader($sectionSuffix, $this->extGetLL($sectionSuffix));
    }

    /**
     * Wraps a string in a link which will open/close a certain part of the Admin Panel
     *
     * @param string $sectionSuffix The code for the display_ label/key
     * @param string $sectionTitle Title (HTML-escaped)
     * @param string $className The classname for the <a> tag
     * @return string $className Linked input string
     * @see extGetHead()
     * @deprecated  since TYPO3 v9 - use new AdminPanel API instead
     */
    public function linkSectionHeader($sectionSuffix, $sectionTitle, $className = '')
    {
        trigger_error('since TYPO3 v9 - use new AdminPanel API instead', E_USER_DEPRECATED);
        $onclick = 'document.TSFE_ADMIN_PANEL_FORM[' .
                   GeneralUtility::quoteJSvalue('TSFE_ADMIN_PANEL[display_' . $sectionSuffix . ']') .
                   '].value=' .
                   ($this->getBackendUser()->uc['TSFE_adminConfig']['display_' . $sectionSuffix] ? '0' : '1') .
                   ';document.TSFE_ADMIN_PANEL_FORM.submit();return false;';

        $output = [];
        $output[] = '<span class="typo3-adminPanel-section-title-identifier"></span>';
        $output[] = '<a href="javascript:void(0)" onclick="' . htmlspecialchars($onclick) . '">';
        $output[] = '  ' . $sectionTitle;
        $output[] = '</a>';
        $output[] = '<input type="hidden" name="TSFE_ADMIN_PANEL[display_' .
                    $sectionSuffix .
                    ']" value="' .
                    (int)$this->isAdminModuleOpen($sectionSuffix) .
                    '" />';

        return implode('', $output);
    }
}
