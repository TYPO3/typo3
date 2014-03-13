<?php
namespace TYPO3\CMS\Documentation\Slots;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Xavier Perseguers <xavier@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * This slot listens to a signal in Extension Manager to add links to
 * manuals available locally.
 */
class ExtensionManager {

	/**
	 * @var \TYPO3\CMS\Documentation\Domain\Model\Document[]
	 */
	static protected $documents = NULL;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * Processes the list of actions for a given extension and adds
	 * a link to the manual(s), if available.
	 *
	 * @param array $extension
	 * @param array $actions
	 * @return void
	 */
	public function processActions(array $extension, array &$actions) {
		if (static::$documents === NULL) {
			/** @var \TYPO3\CMS\Documentation\Controller\DocumentController $documentController */
			$documentController = $this->objectManager->get('TYPO3\\CMS\\Documentation\\Controller\\DocumentController');
			static::$documents = $documentController->getDocuments();
		}

		$extensionKey = $extension['key'];
		$documentKey = 'typo3cms.extensions.' . $extensionKey;

		if (isset(static::$documents[$documentKey])) {
			$document = static::$documents[$documentKey];

			/** @var \TYPO3\CMS\Documentation\ViewHelpers\FormatsViewHelper $formatsViewHelper */
			$formatsViewHelper = $this->objectManager->get('TYPO3\\CMS\\Documentation\\ViewHelpers\\FormatsViewHelper');

			foreach ($document->getTranslations() as $documentTranslation) {
				$actions[] = $formatsViewHelper->render($documentTranslation);
			}
		}
	}

}
