<?php
namespace TYPO3\CMS\Extbase\Mvc\View;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of FLOW3.
 *  All credits go to the v5 team.
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
 * The not found view - a special case.
 *
 * @package Extbase
 * @subpackage MVC\View
 * @version $Id: EmptyView.php 2517 2010-08-04 17:56:45Z bwaidelich $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NotFoundView extends \TYPO3\CMS\Extbase\Mvc\View\AbstractView {

	/**
	 * @var array
	 */
	protected $variablesMarker = array('errorMessage' => 'ERROR_MESSAGE');

	/**
	 * Renders the not found view
	 *
	 * @return string The rendered view
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception if no request has been set
	 * @api
	 */
	public function render() {
		if (!is_object($this->controllerContext->getRequest())) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception('Can\'t render view without request object.', 1192450280);
		}
		$template = file_get_contents($this->getTemplatePathAndFilename());
		if ($this->controllerContext->getRequest() instanceof \TYPO3\CMS\Extbase\Mvc\Web\Request) {
			$template = str_replace('###BASEURI###', \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), $template);
		}
		foreach ($this->variablesMarker as $variableName => $marker) {
			$variableValue = isset($this->variables[$variableName]) ? $this->variables[$variableName] : '';
			$template = str_replace(('###' . $marker) . '###', $variableValue, $template);
		}
		return $template;
	}

	/**
	 * Retrieves path and filename of the not-found-template
	 *
	 * @return string path and filename of the not-found-template
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function getTemplatePathAndFilename() {
		return \t3lib_extmgm::extPath('extbase') . 'Resources/Private/MVC/NotFoundView_Template.html';
	}

	/**
	 * A magic call method.
	 *
	 * Because this not found view is used as a Special Case in situations when no matching
	 * view is available, it must be able to handle method calls which originally were
	 * directed to another type of view. This magic method should prevent PHP from issuing
	 * a fatal error.
	 *
	 * @param string $methodName
	 * @param array $arguments
	 * @return void
	 */
	public function __call($methodName, array $arguments) {

	}

}


?>