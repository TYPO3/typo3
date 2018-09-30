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

use TYPO3\CMS\Adminpanel\ModuleApi\ConfigurableInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleInterface;
use TYPO3\CMS\Adminpanel\Service\EditToolbarService;
use TYPO3\CMS\Adminpanel\Utility\StateUtility;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
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
     * Force preview
     *
     * @var bool
     */
    protected $ext_forcePreview = false;

    /**
     * Array of adminPanel modules
     *
     * @var \TYPO3\CMS\Adminpanel\ModuleApi\ModuleInterface[]
     */
    protected $modules = [];

    /**
     * @var array
     */
    protected $configuration;

    /**
     * Setter for injecting new-style modules
     *
     * @see \TYPO3\CMS\Adminpanel\Controller\MainController::render()
     * @param array $modules
     * @internal
     */
    public function setModules(array $modules): void
    {
        $this->modules = $modules;
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
     * @return FrontendBackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /*****************************************************
     * Admin Panel: Deprecated API
     ****************************************************/

    /**
     * Backwards compatibility method ensuring hook still gets the same content as before
     *
     * @deprecated since TYPO3 v9 - remove when hook can be removed
     * @internal
     * @return string
     * @throws \UnexpectedValueException
     */
    public function callDeprecatedHookObject(): string
    {
        $moduleContent = '';
        if (StateUtility::isOpen()) {
            foreach ($this->modules as $module) {
                $moduleContent .= $this->getModule($module);
            }

            foreach (
                $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_adminpanel.php']['extendAdminPanel']
                ??
                [] as $className
            ) {
                trigger_error(
                    'The hook $GLOBALS[TYPO3_CONF_VARS][SC_OPTIONS][tslib/class.tslib_adminpanel.php][extendAdminPanel] will be removed in TYPO3 v10.0, register an AdminPanelModule instead.',
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
        return $moduleContent;
    }

    /**
     * Render a single module with header panel
     *
     * @deprecated since TYPO3 v9 - only used in deprecated hook call (which triggers the corresponding deprecation error)
     * @param ModuleInterface $module
     * @return string
     */
    protected function getModule(ModuleInterface $module): string
    {
        $output = [];

        if ($module instanceof ConfigurableInterface && $module->isEnabled()) {
            $output[] = '<div class="typo3-adminPanel-section typo3-adminPanel-section-open">';
            $output[] = '  <div class="typo3-adminPanel-section-title">';
            $output[] = '    ' . $this->getSectionOpenerLink($module);
            $output[] = '  </div>';
            $output[] = '<div class="typo3-adminPanel-section-body">';
            $output[] = '  ' . $module->getContent();
            $output[] = '</div>';
            $output[] = '</div>';
        }

        if ($module instanceof DataProviderInterface) {
            foreach ($module->getJavaScriptFiles() as $javaScriptFile) {
                $output[] =
                    '<script src="' .
                    PathUtility::getAbsoluteWebPath(GeneralUtility::getFileAbsFileName($javaScriptFile)) .
                    '"></script>';
            }
        }

        return implode('', $output);
    }

    /*****************************************************
     * Admin Panel Layout Helper functions
     ****************************************************/

    /**
     * Wraps a string in a link which will open/close a certain part of the Admin Panel
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Only used in deprecated hook call (which triggers the corresponding deprecation error)
     * @param ModuleInterface $module
     * @return string
     */
    protected function getSectionOpenerLink(ModuleInterface $module): string
    {
        $identifier = $module->getIdentifier();
        $onclick = 'document.TSFE_ADMIN_PANEL_FORM[' .
                   GeneralUtility::quoteJSvalue('TSFE_ADMIN_PANEL[display_' . $identifier . ']') .
                   '].value=' .
                   ($this->getBackendUser()->uc['AdminPanel']['display_' . $identifier] ? '0' : '1') .
                   ';document.TSFE_ADMIN_PANEL_FORM.submit();return false;';

        $output = [];
        $output[] = '<span class="typo3-adminPanel-section-title-identifier"></span>';
        $output[] = '<a href="javascript:void(0)" onclick="' . htmlspecialchars($onclick) . '">';
        $output[] = '  ' . htmlspecialchars($module->getLabel());
        $output[] = '</a>';
        $output[] = '<input type="hidden" name="TSFE_ADMIN_PANEL[display_' .
                    $identifier .
                    ']" value="' .
                    1 .
                    '" />';

        return implode('', $output);
    }

    /**
     * Creates the tool bar links for the "edit" section of the Admin Panel.
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Use EditToolbarService instead or create buttons via fluid
     * @return string A string containing images wrapped in <a>-tags linking them to proper functions.
     */
    public function ext_makeToolBar(): string
    {
        trigger_error(
            'AdminPanelView::ext_makeToolBar() will be removed in TYPO3 v10.0, use fluid and backend uri builder to create a toolbar.',
            E_USER_DEPRECATED
        );
        $editToolbarService = GeneralUtility::makeInstance(EditToolbarService::class);
        return $editToolbarService->createToolbar();
    }

    /**
     * Translate given key
     *
     * @param string $key Key for a label in the $LOCAL_LANG array of "EXT:core/Resources/Private/Language/locallang_tsfe.xlf
     * @param bool $convertWithHtmlspecialchars If TRUE the language-label will be sent through htmlspecialchars
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0 - only used in deprecated methods
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
     * Add an additional stylesheet
     *
     * @return string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0 - implement AdminPanelModules via the new API (see AdminPanelModuleInterface)
     */
    public function getAdminPanelHeaderData()
    {
        trigger_error(
            'AdminPanelView->getAdminPanelHeaderData() will be removed in TYPO3 v10.0. Implement AdminPanelModules via the new API (see AdminPanelModuleInterface).',
            E_USER_DEPRECATED
        );
        $result = '';
        if (!empty($GLOBALS['TBE_STYLES']['stylesheets']['admPanel'])) {
            $stylesheet = GeneralUtility::locationHeaderUrl($GLOBALS['TBE_STYLES']['stylesheets']['admPanel']);
            $result = '<link rel="stylesheet" type="text/css" href="' .
                      htmlspecialchars($stylesheet, ENT_QUOTES | ENT_HTML5) . '" />';
        }
        return $result;
    }

    /**
     * Checks if an Admin Panel section ("module") is available for the user. If so, TRUE is returned.
     *
     * @param string $key The module key, eg. "edit", "preview", "info" etc.
     * @return bool
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0 - implement AdminPanelModules via the new API (see AdminPanelModuleInterface)
     */
    public function isAdminModuleEnabled($key)
    {
        trigger_error(
            'AdminPanelView->isAdminModuleEnabled() will be removed in TYPO3 v10.0. Implement AdminPanelModules via the new API (see AdminPanelModuleInterface).',
            E_USER_DEPRECATED
        );
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0 - implement AdminPanelModules via the new API (see AdminPanelModuleInterface)
     */
    public function saveConfigOptions()
    {
        trigger_error(
            'AdminPanelView->saveConfigOptions() will be removed in TYPO3 v10.0. Implement AdminPanelModules via the new API (see AdminPanelModuleInterface).',
            E_USER_DEPRECATED
        );
        $input = GeneralUtility::_GP('TSFE_ADMIN_PANEL');
        $beUser = $this->getBackendUser();
        if (is_array($input)) {
            // Setting
            $beUser->uc['AdminPanel'] = array_merge(
                !is_array($beUser->uc['AdminPanel']) ? [] : $beUser->uc['AdminPanel'],
                $input
            );
            unset($beUser->uc['AdminPanel']['action']);

            foreach ($this->modules as $module) {
                if ($module->isEnabled()) {
                    // We use TYPO3_REQUEST for compatibility reasons. The object and this method are deprecated anyway, this should be fine.
                    $module->onSubmit($input, $GLOBALS['TYPO3_REQUEST']);
                }
            }
            // Saving
            $beUser->writeUC();
            // Flush fluid template cache
            $cacheManager = new CacheManager();
            $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
            $cacheManager->getCache('fluid_template')->flush();
        }
    }

    /**
     * Returns the value for an Admin Panel setting.
     *
     * @param string $sectionName Module key
     * @param string $val Setting key
     * @return mixed The setting value
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0 - implement AdminPanelModules via the new API (see AdminPanelModuleInterface)
     */
    public function extGetFeAdminValue($sectionName, $val = '')
    {
        trigger_error(
            'AdminPanelView->extGetFeAdminValue() will be removed in TYPO3 v10.0. Implement AdminPanelModules via the new API (see AdminPanelModuleInterface).',
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

        $returnValue = $val ? $beUser->uc['AdminPanel'][$sectionName . '_' . $val] : 1;

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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0 - see AdminPanelModule: Preview
     */
    public function forcePreview()
    {
        trigger_error(
            'AdminPanelView->forcePreview() will be removed in TYPO3 v10.0. Use new AdminPanel Preview Module instead.',
            E_USER_DEPRECATED
        );
        $this->ext_forcePreview = true;
    }

    /**
     * Returns TRUE if admin panel module is open
     *
     * @param string $key Module key
     * @return bool TRUE, if the admin panel is open for the specified admin panel module key.
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0 - use new AdminPanel API instead
     */
    public function isAdminModuleOpen($key)
    {
        trigger_error(
            'AdminPanelView->isAdminModuleOpen() will be removed in TYPO3 v10.0. Use new AdminPanel API instead.',
            E_USER_DEPRECATED
        );
        return $this->getBackendUser()->uc['AdminPanel']['display_top'] &&
               $this->getBackendUser()->uc['AdminPanel']['display_' . $key];
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0 - use new AdminPanel API instead
     */
    public function extGetItem($title, $content = '', $checkbox = '', $outerDivClass = null, $innerDivClass = null)
    {
        trigger_error(
            'AdminPanelView->extGetItem() will be removed in TYPO3 v10.0. Use new AdminPanel API instead.',
            E_USER_DEPRECATED
        );
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0 - use new AdminPanel API instead
     */
    public function extGetHead($sectionSuffix)
    {
        trigger_error(
            'AdminPanelView->extGetHead() will be removed in TYPO3 v10.0. Use new AdminPanel API instead.',
            E_USER_DEPRECATED
        );
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0 - use new AdminPanel API instead
     */
    public function linkSectionHeader($sectionSuffix, $sectionTitle, $className = '')
    {
        trigger_error(
            'AdminPanelView->linkSectionHeader() will be removed in TYPO3 v10.0. Use new AdminPanel API instead.',
            E_USER_DEPRECATED
        );
        $onclick = 'document.TSFE_ADMIN_PANEL_FORM[' .
                   GeneralUtility::quoteJSvalue('TSFE_ADMIN_PANEL[display_' . $sectionSuffix . ']') .
                   '].value=' .
                   ($this->getBackendUser()->uc['AdminPanel']['display_' . $sectionSuffix] ? '0' : '1') .
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
