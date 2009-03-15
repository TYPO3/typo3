<?php

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/View/Helper/TX_EXTMVC_View_Helper_AbstractHelper.php');

/**
 * A For Helper
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_View_Helper_LinkHelper extends TX_EXTMVC_View_Helper_AbstractHelper {

	/**
	 * an instance of tslib_cObj
	 *
	 * @var	tslib_cObj
	 */
	protected $contentObject = null;

	/**
	 * constructor for class tx_community_viewhelper_Link
	 */
	public function __construct(array $arguments = array()) {
		if (is_null($this->contentObject)) {
			$this->contentObject = t3lib_div::makeInstance('tslib_cObj');
		}
	}

	public function render($view, $content, $arguments, $templateResource, $variables) {
		$parameters = t3lib_div::_GET();
		$prefixedExtensionKey = 'tx_' . strtolower($this->request->getControllerExtensionKey());
		if (!empty($arguments['to'])) {
			$linkTo = $arguments['to'];
			$view->replaceReferencesWithValues($linkTo, $variables);
			unset($parameters['id']);
		}
		if (!empty($arguments['parameters'])) {
			$explodedParameters = explode(' ', $arguments['parameters']);
			$additionalParameters = array();
			foreach ($explodedParameters as $parameterString) {
				list($parameterKey, $parameterValue) = explode('=', trim($parameterString));
				$view->replaceReferencesWithValues($parameterValue, $variables);
				$additionalParameters[$prefixedExtensionKey] = array(trim($parameterKey) => $parameterValue);
				if (is_array($parameters[$prefixedExtensionKey])) {
					$parameters[$prefixedExtensionKey] = array_merge($parameters[$prefixedExtensionKey], $additionalParameters[$prefixedExtensionKey]);
				} else {
					$parameters[$prefixedExtensionKey] = $additionalParameters[$prefixedExtensionKey];
				}
			}
		}
		
		$linkText = $view->renderTemplate($templateResource, $variables);

		$parameters = is_array($parameters) ? t3lib_div::implodeArrayForUrl('', $parameters, '', 1) : '';

		$link = $this->contentObject->typoLink(
			$linkText,
			array(
				'parameter' => $linkTo, // FIXME
				'additionalParams' => $parameters
			)
		);
		
		return $link;
	}
			
}

?>
