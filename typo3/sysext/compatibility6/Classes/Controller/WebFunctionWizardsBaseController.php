<?php
namespace TYPO3\CMS\Compatibility6\Controller;

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

use TYPO3\CMS\Backend\Module\BaseScriptClass;

/**
 * The Wizard function in the Web>Info module
 * Creates a framework for adding wizard sub-sub-modules under the Wizard function in Web>Info
 */
class WebFunctionWizardsBaseController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule
{
    /**
     * @var string
     */
    public $function_key = 'wiz';

    /**
     * Initialize.
     * Calls parent init function and then the handleExternalFunctionValue() function from the parent class
     *
     * @param BaseScriptClass $pObj A reference to the parent (calling) object (which is probably an instance of an extension class to \TYPO3\CMS\Backend\Module\BaseScriptClass)
     * @param array $conf The configuration set for this module - from global array TBE_MODULES_EXT
     * @return void
     */
    public function init(&$pObj, $conf)
    {
        // OK, handles ordinary init. This includes setting up the menu array with ->modMenu
        parent::init($pObj, $conf);
        $this->handleExternalFunctionValue();
    }

    /**
     * Modifies parent objects internal MOD_MENU array, adding items this module needs.
     *
     * @return array Items merged with the parent objects.
     */
    public function modMenu()
    {
        $GLOBALS['LANG']->includeLLFile('EXT:compatibility6/Resources/Private/Language/wizards.xlf');
        $modMenuAdd = array(
            $this->function_key => array()
        );
        $modMenuAdd[$this->function_key] = $this->pObj->mergeExternalItems($this->pObj->MCONF['name'], $this->function_key, $modMenuAdd[$this->function_key]);
        $modMenuAdd[$this->function_key] = \TYPO3\CMS\Backend\Utility\BackendUtility::unsetMenuItems(
            $this->pObj->modTSconfig['properties'],
            $modMenuAdd[$this->function_key],
            'menu.' . $this->function_key
        );
        return $modMenuAdd;
    }

    /**
     * Creation of the main content. Calling extObjContent() to trigger content generation from the sub-sub modules
     *
     * @return string The content
     */
    public function main()
    {
        $menu = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu(
            $this->pObj->id,
            'SET[wiz]',
            $this->pObj->MOD_SETTINGS['wiz'],
            $this->pObj->MOD_MENU['wiz']
        );

        $content = '';
        if (!empty($menu)) {
            $menu = $GLOBALS['LANG']->getLL('wiz_lWizards', true) . ': ' . $menu;
            $content = '<div>';
            $content .= '<span class="text-nowrap">' . $menu . '</span>';
            $content .= '</div>';
            $content .= '<div style="padding-top: 20px;"></div>';
        }

        $content .= $this->extObjContent();
        return $content;
    }
}
