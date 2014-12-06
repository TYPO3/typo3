<?php
namespace TYPO3\CMS\Beuser\ViewHelpers\Be;

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

/**
 * View helper which allows you to create extbase based modules in the style of TYPO3 default modules.
 *
 * Extends fluid be.container view helper adding a feature to register RequireJS modules.
 *
 * @see \TYPO3\CMS\Fluid\ViewHelpers\Be\ContainerViewHelper
 */
class ContainerViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\ContainerViewHelper {

	/**
	 * Render start page with \TYPO3\CMS\Backend\Template\DocumentTemplate
	 *
	 * @param string $pageTitle title tag of the module. Not required by default, as BE modules are shown in a frame
	 * @param bool $enableClickMenu If TRUE, loads clickmenu.js required by BE context menus. Defaults to TRUE
	 * @param bool $loadPrototype specifies whether to load prototype library. Defaults to TRUE
	 * @param bool $loadScriptaculous specifies whether to load scriptaculous libraries. Defaults to FALSE
	 * @param string $scriptaculousModule additionales modules for scriptaculous
	 * @param bool $loadExtJs specifies whether to load ExtJS library. Defaults to FALSE
	 * @param bool $loadExtJsTheme whether to load ExtJS "grey" theme. Defaults to FALSE
	 * @param string $extJsAdapter load alternative adapter (ext-base is default adapter)
	 * @param bool $enableExtJsDebug if TRUE, debug version of ExtJS is loaded. Use this for development only
	 * @param bool $loadJQuery whether to load jQuery library. Defaults to FALSE
	 * @param array $includeCssFiles List of custom CSS file to be loaded
	 * @param array $includeJsFiles List of custom JavaScript file to be loaded
	 * @param array $addJsInlineLabels Custom labels to add to JavaScript inline labels
	 * @param bool $includeCsh flag for including CSH
	 * @param array $loadRequireJsModules List of require JS modules to register
	 * @return string
	 */
	public function render(
		$pageTitle = '',
		$enableClickMenu = TRUE,
		$loadPrototype = TRUE,
		$loadScriptaculous = FALSE,
		$scriptaculousModule = '',
		$loadExtJs = FALSE,
		$loadExtJsTheme = TRUE,
		$extJsAdapter = '',
		$enableExtJsDebug = FALSE,
		$loadJQuery = FALSE,
		$includeCssFiles = NULL,
		$includeJsFiles = NULL,
		$addJsInlineLabels = NULL,
		$includeCsh = TRUE,
		$loadRequireJsModules = NULL
	) {
		$doc = $this->getDocInstance();
		$pageRenderer = $doc->getPageRenderer();

		if (is_array($loadRequireJsModules)) {
			foreach ($loadRequireJsModules as $module) {
				$pageRenderer->loadRequireJsModule($module);
			}
		}

		return parent::render(
			$pageTitle,
			$enableClickMenu,
			$loadPrototype,
			$loadScriptaculous,
			$scriptaculousModule,
			$loadExtJs,
			$loadExtJsTheme,
			$extJsAdapter,
			$enableExtJsDebug,
			FALSE,
			$includeCssFiles,
			$includeJsFiles,
			$addJsInlineLabels,
			$includeCsh
		);
	}
}
