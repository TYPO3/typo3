<?php
namespace TYPO3\CMS\Form\PostProcess;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Patrick Broens <patrick@patrickbroens.nl>
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
 * The post processor
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class PostProcessor {

	/**
	 * Constructor
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Form $form Form domain model
	 * @param array $typoScript Post processor TypoScript settings
	 */
	public function __construct(\TYPO3\CMS\Form\Domain\Model\Form $form, array $typoScript) {
		$this->form = $form;
		$this->typoScript = $typoScript;
	}

	/**
	 * The main method called by the controller
	 *
	 * Iterates over the configured post processors and calls them with their
	 * own settings
	 *
	 * @return string HTML messages from the called processors
	 */
	public function process() {
		$html = '';
		if (is_array($this->typoScript)) {
			$keys = $this->sortTypoScriptKeyList();
			foreach ($keys as $key) {
				if (!intval($key) || strpos($key, '.') !== FALSE) {
					continue;
				}
				$className = FALSE;
				$processorArguments = array();
				if (isset($this->typoScript[$key . '.'])) {
					$processorArguments = $this->typoScript[$key . '.'];
				}
				if (class_exists($this->typoScript[$key], TRUE)) {
					$className = $this->typoScript[$key];
				} else {
					$classNameExpanded = 'TYPO3\\CMS\\Form\\PostProcess\\' . ucfirst(strtolower($this->typoScript[$key])) . 'PostProcessor';
					if (class_exists($classNameExpanded, TRUE)) {
						$className = $classNameExpanded;
					}
				}
				if ($className !== FALSE) {
					$processor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className, $this->form, $processorArguments);
					if ($processor instanceof \TYPO3\CMS\Form\PostProcess\PostProcessorInterface) {
						$html .= $processor->process();
					}
				}
			}
		}
		return $html;
	}

	/**
	 * Wrapper method for \TYPO3\CMS\Core\TypoScript\TemplateService::sortedKeyList
	 * (makes unit testing possible)
	 *
	 * @return array
	 */
	public function sortTypoScriptKeyList() {
		return \TYPO3\CMS\Core\TypoScript\TemplateService::sortedKeyList($this->typoScript);
	}

}

?>