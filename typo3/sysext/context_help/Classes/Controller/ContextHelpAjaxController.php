<?php
namespace TYPO3\CMS\ContextHelp\Controller;

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

use TYPO3\CMS\Core\Http\AjaxRequestHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;

/**
 * Class ContextHelpAjaxController
 */
class ContextHelpAjaxController {

	/**
	 * The main dispatcher function. Collect data and prepare HTML output.
	 *
	 * @param array $params array of parameters, currently unused
	 * @param AjaxRequestHandler $ajaxObj object of type AjaxRequestHandler
	 * @return void
	 */
	public function dispatch($params = array(), AjaxRequestHandler $ajaxObj = NULL) {
		$params = GeneralUtility::_GP('params');
		if ($params['action'] === 'getContextHelp') {
			$result = $this->getContextHelp($params['table'], $params['field']);
			$ajaxObj->addContent('title', $result['title']);
			$ajaxObj->addContent('content', $result['description']);
			$ajaxObj->addContent('link', $result['moreInfo']);
			$ajaxObj->setContentFormat('json');
		}
	}

	/**
	 * Fetch the context help for the given table/field parameters
	 *
	 * @param string $table Table identifier
	 * @param string $field Field identifier
	 * @return array complete Help information
	 */
	protected function getContextHelp($table, $field) {
		$helpTextArray = BackendUtility::helpTextArray($table, $field);
		$moreIcon = $helpTextArray['moreInfo'] ? IconUtility::getSpriteIcon('actions-view-go-forward') : '';
		return array(
			'title' => $helpTextArray['title'],
			'description' => '<p class="t3-help-short' . ($moreIcon ? ' tipIsLinked' : '') . '">' . $helpTextArray['description'] . $moreIcon . '</p>',
			'id' => $table . '.' . $field,
			'moreInfo' => $helpTextArray['moreInfo']
		);
	}

}
