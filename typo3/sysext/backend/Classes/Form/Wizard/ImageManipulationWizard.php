<?php
namespace TYPO3\CMS\Backend\Form\Wizard;

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

use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Wizard for rendering image manipulation view
 */
class ImageManipulationWizard {

	/**
	 * @var string
	 */
	protected $templatePath = 'EXT:backend/Resources/Private/Templates/';

	/**
	 * Returns the html for the AJAX API
	 *
	 * @param array $params
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxRequestHandler
	 * @return void
	 */
	public function getHtmlForImageManipulationWizard($params, $ajaxRequestHandler) {
		if (!$this->checkHmacToken()) {
			HttpUtility::setResponseCodeAndExit(HttpUtility::HTTP_STATUS_403);
		}

		$fileUid = GeneralUtility::_GET('file');
		$image = NULL;
		if (MathUtility::canBeInterpretedAsInteger($fileUid)) {
			try {
				$image = ResourceFactory::getInstance()->getFileObject($fileUid);
			} catch (FileDoesNotExistException $e) {}
		}

		$view = $this->getFluidTemplateObject($this->templatePath . 'Wizards/ImageManipulationWizard.html');
		$view->assign('image', $image);
		$view->assign('zoom', (bool)GeneralUtility::_GET('zoom'));
		$view->assign('ratios', $this->getRatiosArray());
		$content = $view->render();

		$ajaxRequestHandler->addContent('content', $content);
		$ajaxRequestHandler->setContentFormat('html');
	}

	/**
	 * Check if hmac token is correct
	 *
	 * @return bool
	 */
	protected function checkHmacToken() {
		$parameters = array();
		if (GeneralUtility::_GET('file')) {
			$parameters['file'] = GeneralUtility::_GET('file');
		}
		$parameters['zoom'] = GeneralUtility::_GET('zoom') ? '1' : '0';
		$parameters['ratios'] = GeneralUtility::_GET('ratios') ?: '';

		$token = GeneralUtility::hmac(implode('|', $parameters), 'ImageManipulationWizard');
		return $token === GeneralUtility::_GET('token');
	}

	/**
	 * Get available ratios
	 *
	 * @return array
	 */
	protected function getRatiosArray() {
		$ratios = json_decode(GeneralUtility::_GET('ratios'));
		// Json transforms a array with sting keys to a array,
		// we need to transform this to an array for the fluid ForViewHelper
		if (is_object($ratios)) {
			$ratios = get_object_vars($ratios);
		}
		return $ratios;
	}

	/**
	 * Returns a new standalone view, shorthand function
	 *
	 * @param string $templatePathAndFileName optional the path to set the template path and filename
	 * @return StandaloneView
	 */
	protected function getFluidTemplateObject($templatePathAndFileName = NULL) {
		$view = GeneralUtility::makeInstance(StandaloneView::class);
		if ($templatePathAndFileName) {
			$view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templatePathAndFileName));
		}
		return $view;
	}
}
