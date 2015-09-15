<?php
namespace TYPO3\CMS\Extbase\Mvc\Controller;

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

use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Imaging\IconFactory;

/**
 * For Usage in Backend Modules.
 *
 * Renders the DocHeader component before the content
 * of the module and serves as generic layout provider.
 */
class BackendActionController extends ActionController {

	/**
	 * @var ModuleTemplate
	 */
	protected $moduleTemplate;

	/**
	 * @var IconFactory
	 */
	protected $iconFactory;

	/**
	 * @param ModuleTemplate $moduleTemplate
	 */
	public function injectModuleTemplate(ModuleTemplate $moduleTemplate) {
		$this->moduleTemplate = $moduleTemplate;
	}

	/**
	 * @param IconFactory $iconFactory
	 */
	public function injectIconFactory(IconFactory $iconFactory) {
		$this->iconFactory = $iconFactory;
	}

	/**
	 * Appends content to response object.
	 * The actual module content is wrapped
	 * in the module template component.
	 *
	 * @param string $content
	 */
	protected function appendContent($content) {
		$this->moduleTemplate->setContent($content);
		$this->response->appendContent($this->moduleTemplate->renderContent());
	}

}