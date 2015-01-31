<?php
namespace TYPO3\CMS\Fluid\Compatibility;

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
 * Build a template parser.
 * Use this class to get a fresh instance of a correctly initialized Fluid template parser.
 */
class TemplateParserBuilder {

	/**
	 * Creates a new TemplateParser which is correctly initialized. This is the correct
	 * way to get a Fluid parser instance.
	 *
	 * @return \TYPO3\CMS\Fluid\Core\Parser\TemplateParser A correctly initialized Template Parser
	 */
	static public function build() {
		/** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
		$templateParser = $objectManager->get(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class);
		return $templateParser;
	}

}
