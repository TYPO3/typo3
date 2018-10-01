<?php
namespace TYPO3\CMS\Fluid\Core\ViewHelper;

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

/**
 * Class ViewHelperResolver
 *
 * Class whose purpose is dedicated to resolving classes which
 * can be used as ViewHelpers and ExpressionNodes in Fluid.
 *
 * This CMS-specific version of the ViewHelperResolver works
 * almost exactly like the one from Fluid itself, with the main
 * differences being that this one supports a legacy mode flag
 * which when toggled on makes the Fluid parser behave exactly
 * like it did in the legacy CMS Fluid package.
 *
 * In addition to modifying the behavior or the parser when
 * legacy mode is requested, this ViewHelperResolver is also
 * made capable of "mixing" two different ViewHelper namespaces
 * to effectively create aliases for the Fluid core ViewHelpers
 * to be loaded in the TYPO3\CMS\ViewHelpers scope as well.
 *
 * Default ViewHelper namespaces are read TYPO3 configuration at:
 *
 * $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']
 *
 * Extending this array allows third party ViewHelper providers
 * to automatically add or extend namespaces which then become
 * available in every Fluid template file without having to
 * register the namespace.
 *
 * @internal This is a helper class which is not considered part of TYPO3's Public API.
 */
class ViewHelperResolver extends \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperResolver
{
    /**
     * ViewHelperResolver constructor
     *
     * Loads namespaces defined in global TYPO3 configuration. Overlays `f:`
     * with `f:debug:` when Fluid debugging is enabled in the admin panel,
     * causing debugging-specific ViewHelpers to be resolved in that case.
     */
    public function __construct()
    {
        $this->namespaces = $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces'];
        if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_FE && $this->getBackendUser() instanceof BackendUserAuthentication) {
            $configuration = $this->getBackendUser()->uc['AdminPanel'];
            if (isset($configuration['preview_showFluidDebug']) && $configuration['preview_showFluidDebug']) {
                $this->namespaces['f'][] = 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Debug';
            }
        }
    }

    /**
     * @param string $viewHelperClassName
     * @return \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface
     */
    public function createViewHelperInstanceFromClassName($viewHelperClassName)
    {
        return $this->getObjectManager()->get($viewHelperClassName);
    }

    /**
     * @return ObjectManagerInterface
     */
    protected function getObjectManager()
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
