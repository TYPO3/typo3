<?php
namespace TYPO3\CMS\Backend\View;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * class to render the TYPO3 backend menu for the modules
 */
class ModuleMenuView
{
    /**
     * Module loading object
     *
     * @var \TYPO3\CMS\Backend\Module\ModuleLoader
     */
    protected $moduleLoader;

    /**
     * @var string
     */
    protected $backPath;

    /**
     * @var bool
     */
    protected $linkModules;

    /**
     * @var array
     */
    protected $loadedModules;

    /**
     * Constructor, initializes several variables
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, not in use, as everything can be done via the ModuleMenuRepository directly
     */
    public function __construct()
    {
        GeneralUtility::logDeprecatedFunction();
        if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) {
            $GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_misc.xlf');
        }
        $this->backPath = '';
        $this->linkModules = true;
        // Loads the backend modules available for the logged in user.
        $this->moduleLoader = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Module\ModuleLoader::class);
        $this->moduleLoader->observeWorkspaces = true;
        $this->moduleLoader->load($GLOBALS['TBE_MODULES']);
        $this->loadedModules = $this->moduleLoader->modules;
    }

    /**
     * sets the path back to /typo3/
     *
     * @param string $backPath Path back to /typo3/
     * @throws \InvalidArgumentException
     * @return void
     */
    public function setBackPath($backPath)
    {
        if (!is_string($backPath)) {
            throw new \InvalidArgumentException('parameter $backPath must be of type string', 1193315266);
        }
        $this->backPath = $backPath;
    }

    /**
     * loads the collapse states for the main modules from user's configuration (uc)
     *
     * @return array Collapse states
     */
    protected function getCollapsedStates()
    {
        $collapsedStates = [];
        if ($GLOBALS['BE_USER']->uc['moduleData']['moduleMenu']) {
            $collapsedStates = $GLOBALS['BE_USER']->uc['moduleData']['moduleMenu'];
        }
        return $collapsedStates;
    }

    /**
     * ModuleMenu Store loading data
     *
     * @param array $params
     * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj
     * @return array
     */
    public function getModuleData($params, $ajaxObj)
    {
        $data = ['success' => true, 'root' => []];
        $rawModuleData = $this->getRawModuleData();
        $index = 0;
        $dummyLink = BackendUtility::getModuleUrl('dummy');
        foreach ($rawModuleData as $moduleKey => $moduleData) {
            $key = substr($moduleKey, 8);
            $num = count($data['root']);
            if ($moduleData['link'] != $dummyLink || $moduleData['link'] == $dummyLink && is_array($moduleData['subitems'])) {
                $data['root'][$num]['key'] = $key;
                $data['root'][$num]['menuState'] = $GLOBALS['BE_USER']->uc['moduleData']['menuState'][$moduleKey];
                $data['root'][$num]['label'] = $moduleData['title'];
                $data['root'][$num]['subitems'] = is_array($moduleData['subitems']) ? count($moduleData['subitems']) : 0;
                if ($moduleData['link'] && $this->linkModules) {
                    $data['root'][$num]['link'] = 'top.goToModule(' . GeneralUtility::quoteJSvalue($moduleData['name']) . ')';
                }
                // Traverse submodules
                if (is_array($moduleData['subitems'])) {
                    foreach ($moduleData['subitems'] as $subKey => $subData) {
                        $data['root'][$num]['sub'][] = [
                            'name' => $subData['name'],
                            'description' => $subData['description'],
                            'label' => $subData['title'],
                            'icon' => $subData['icon']['filename'],
                            'navframe' => $subData['parentNavigationFrameScript'],
                            'link' => $subData['link'],
                            'originalLink' => $subData['originalLink'],
                            'index' => $index++,
                            'navigationFrameScript' => $subData['navigationFrameScript'],
                            'navigationFrameScriptParam' => $subData['navigationFrameScriptParam'],
                            'navigationComponentId' => $subData['navigationComponentId']
                        ];
                    }
                }
            }
        }
        if ($ajaxObj) {
            $ajaxObj->setContent($data);
            $ajaxObj->setContentFormat('jsonbody');
        } else {
            return $data;
        }
    }

    /**
     * Returns the loaded modules
     *
     * @return array Array of loaded modules
     */
    public function getLoadedModules()
    {
        return $this->loadedModules;
    }

    /**
     * saves the menu's toggle state in the backend user's uc
     *
     * @param array $params Array of parameters from the AJAX interface, currently unused
     * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type AjaxRequestHandler
     * @return void
     */
    public function saveMenuState($params, $ajaxObj)
    {
        $menuItem = GeneralUtility::_POST('menuid');
        $state = GeneralUtility::_POST('state') === 'true' ? 1 : 0;
        $GLOBALS['BE_USER']->uc['moduleData']['menuState'][$menuItem] = $state;
        $GLOBALS['BE_USER']->writeUC();
    }

    /**
     * Reads User configuration from options.hideModules and removes
     * modules from $this->loadedModules accordingly.
     *
     * @return void
     */
    protected function unsetHiddenModules()
    {
        // Hide modules if set in userTS.
        $hiddenModules = $GLOBALS['BE_USER']->getTSConfig('options.hideModules');
        if (!empty($hiddenModules['value'])) {
            $hiddenMainModules = GeneralUtility::trimExplode(',', $hiddenModules['value'], true);
            foreach ($hiddenMainModules as $hiddenMainModule) {
                unset($this->loadedModules[$hiddenMainModule]);
            }
        }

        // Hide sub-modules if set in userTS.
        if (!empty($hiddenModules['properties']) && is_array($hiddenModules['properties'])) {
            foreach ($hiddenModules['properties'] as $mainModuleName => $subModules) {
                $hiddenSubModules = GeneralUtility::trimExplode(',', $subModules, true);
                foreach ($hiddenSubModules as $hiddenSubModule) {
                    unset($this->loadedModules[$mainModuleName]['sub'][$hiddenSubModule]);
                }
            }
        }
    }

    /**
     * gets the raw module data
     *
     * @return array Multi dimension array with module data
     */
    public function getRawModuleData()
    {
        $modules = [];

        // Unset modules that are meant to be hidden from the menu.
        $this->unsetHiddenModules();
        $dummyScript = BackendUtility::getModuleUrl('dummy');
        foreach ($this->loadedModules as $moduleName => $moduleData) {
            $moduleLink = '';
            if (!is_array($moduleData['sub'])) {
                $moduleLink = $moduleData['script'];
            }
            $moduleLink = GeneralUtility::resolveBackPath($moduleLink);
            $moduleKey = 'modmenu_' . $moduleName;
            $moduleIcon = $this->getModuleIcon($moduleKey);
            $modules[$moduleKey] = [
                'name' => $moduleName,
                'title' => $GLOBALS['LANG']->moduleLabels['tabs'][$moduleName . '_tab'],
                'onclick' => 'top.goToModule(' . GeneralUtility::quoteJSvalue($moduleName) . ');',
                'icon' => $moduleIcon,
                'link' => $moduleLink,
                'description' => $GLOBALS['LANG']->moduleLabels['labels'][$moduleKey . 'label']
            ];
            if (!is_array($moduleData['sub']) && $moduleData['script'] != $dummyScript) {
                // Work around for modules with own main entry, but being self the only submodule
                $modules[$moduleKey]['subitems'][$moduleKey] = [
                    'name' => $moduleName,
                    'title' => $GLOBALS['LANG']->moduleLabels['tabs'][$moduleName . '_tab'],
                    'onclick' => 'top.goToModule(' . GeneralUtility::quoteJSvalue($moduleName) . ');',
                    'icon' => $this->getModuleIcon($moduleName . '_tab'),
                    'link' => $moduleLink,
                    'originalLink' => $moduleLink,
                    'description' => $GLOBALS['LANG']->moduleLabels['labels'][$moduleKey . 'label'],
                    'navigationFrameScript' => null,
                    'navigationFrameScriptParam' => null,
                    'navigationComponentId' => null
                ];
            } elseif (is_array($moduleData['sub'])) {
                foreach ($moduleData['sub'] as $submoduleName => $submoduleData) {
                    if (isset($submoduleData['script'])) {
                        $submoduleLink = GeneralUtility::resolveBackPath($submoduleData['script']);
                    } else {
                        $submoduleLink = BackendUtility::getModuleUrl($submoduleData['name']);
                    }
                    $submoduleKey = $moduleName . '_' . $submoduleName . '_tab';
                    $submoduleIcon = $this->getModuleIcon($submoduleKey);
                    $submoduleDescription = $GLOBALS['LANG']->moduleLabels['labels'][$submoduleKey . 'label'];
                    $originalLink = $submoduleLink;
                    $navigationFrameScript = $submoduleData['navFrameScript'];
                    $modules[$moduleKey]['subitems'][$submoduleKey] = [
                        'name' => $moduleName . '_' . $submoduleName,
                        'title' => $GLOBALS['LANG']->moduleLabels['tabs'][$submoduleKey],
                        'onclick' => 'top.goToModule(' . GeneralUtility::quoteJSvalue($moduleName . '_' . $submoduleName) . ');',
                        'icon' => $submoduleIcon,
                        'link' => $submoduleLink,
                        'originalLink' => $originalLink,
                        'description' => $submoduleDescription,
                        'navigationFrameScript' => $navigationFrameScript,
                        'navigationFrameScriptParam' => $submoduleData['navFrameScriptParam'],
                        'navigationComponentId' => $submoduleData['navigationComponentId']
                    ];
                    // if the main module has a navframe script, inherit to the submodule,
                    // but only if it is not disabled explicitly (option is set to FALSE)
                    if ($moduleData['navFrameScript'] && $submoduleData['inheritNavigationComponentFromMainModule'] !== false) {
                        $modules[$moduleKey]['subitems'][$submoduleKey]['parentNavigationFrameScript'] = $moduleData['navFrameScript'];
                    }
                }
            }
        }
        return $modules;
    }

    /**
     * gets the module icon and its size
     *
     * @param string $moduleKey Module key
     * @return array Icon data array with 'filename', 'size', and 'html'
     */
    protected function getModuleIcon($moduleKey)
    {
        $icon = [
            'filename' => '',
            'size' => '',
            'title' => '',
            'html' => ''
        ];

        if (!empty($GLOBALS['LANG']->moduleLabels['tabs_images'][$moduleKey])) {
            $imageReference = $GLOBALS['LANG']->moduleLabels['tabs_images'][$moduleKey];
            $iconFileRelative = $this->getModuleIconRelative($imageReference);
            if (!empty($iconFileRelative)) {
                $iconTitle = $GLOBALS['LANG']->moduleLabels['tabs'][$moduleKey];
                $iconFileAbsolute = $this->getModuleIconAbsolute($imageReference);
                $iconSizes = @getimagesize($iconFileAbsolute);
                $icon['filename'] = $iconFileRelative;
                $icon['size'] = $iconSizes[3];
                $icon['title'] = htmlspecialchars($iconTitle);
                $icon['html'] = '<img src="' . $iconFileRelative . '" ' . $iconSizes[3] . ' title="' . htmlspecialchars($iconTitle) . '" alt="' . htmlspecialchars($iconTitle) . '" />';
            }
        }
        return $icon;
    }

    /**
     * Returns the filename readable for the script from PATH_typo3.
     * That means absolute names are just returned while relative names are
     * prepended with the path pointing back to typo3/ dir
     *
     * @param string $iconFilename Icon filename
     * @return string Icon filename with absolute path
     * @see getModuleIconRelative()
     */
    protected function getModuleIconAbsolute($iconFilename)
    {
        if (!GeneralUtility::isAbsPath($iconFilename)) {
            $iconFilename = $this->backPath . $iconFilename;
        }
        return $iconFilename;
    }

    /**
     * Returns relative path to the icon filename for use in img-tags
     *
     * @param string $iconFilename Icon filename
     * @return string Icon filename with relative path
     * @see getModuleIconAbsolute()
     */
    protected function getModuleIconRelative($iconFilename)
    {
        if (GeneralUtility::isAbsPath($iconFilename)) {
            $iconFilename = '../' . \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($iconFilename);
        }
        return $this->backPath . $iconFilename;
    }

    /**
     * Appends a '?' if there is none in the string already
     *
     * @param string $link Link URL
     * @return string Link URl appended with ? if there wasn't one
     */
    protected function appendQuestionmarkToLink($link)
    {
        if (!strstr($link, '?')) {
            $link .= '?';
        }
        return $link;
    }

    /**
     * renders the logout button form
     *
     * @return string Html code snippet displaying the logout button
     */
    public function renderLogoutButton()
    {
        $buttonLabel = $GLOBALS['BE_USER']->user['ses_backuserid'] ? 'LLL:EXT:lang/locallang_core.xlf:buttons.exit' : 'LLL:EXT:lang/locallang_core.xlf:buttons.logout';
        $buttonForm = '
		<form action="' . htmlspecialchars(BackendUtility::getModuleUrl('logout')) . '" target="_top">
			<input class="btn btn-default" type="submit" id="logout-submit-button" value="' . $GLOBALS['LANG']->sL($buttonLabel, true) . '" />
		</form>';
        return $buttonForm;
    }

    /**
     * turns linking of modules on or off
     *
     * @param bool $linkModules Status for linking modules with a-tags, set to FALSE to turn lining off
     * @throws \InvalidArgumentException
     * @return void
     */
    public function setLinkModules($linkModules)
    {
        if (!is_bool($linkModules)) {
            throw new \InvalidArgumentException('parameter $linkModules must be of type bool', 1193326558);
        }
        $this->linkModules = $linkModules;
    }
}
