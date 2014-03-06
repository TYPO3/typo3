<?php
namespace TYPO3\CMS\Install\View;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * A standalone fluid view with reduced functionality for ext:install
 *
 * In contrast to the parent class, this StandaloneView does not
 * bootstrap a frontend, so some view helper like f:link will not work.
 */
class StandaloneView extends \TYPO3\CMS\Fluid\View\StandaloneView {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->templateParser = $this->objectManager->get('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser');
		$this->setRenderingContext($this->objectManager->get('TYPO3\\CMS\\Fluid\\Core\\Rendering\\RenderingContext'));
		$request = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Request');
		$request->setRequestURI(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
		$request->setBaseURI(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
		$uriBuilder = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
		$uriBuilder->setRequest($request);
		$controllerContext = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerContext');
		$controllerContext->setRequest($request);
		$controllerContext->setUriBuilder($uriBuilder);
		$this->setControllerContext($controllerContext);
		$this->templateCompiler = $this->objectManager->get('TYPO3\\CMS\\Fluid\\Core\\Compiler\\TemplateCompiler');
		$this->templateCompiler->setTemplateCache(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->getCache('fluid_template'));
	}
}
