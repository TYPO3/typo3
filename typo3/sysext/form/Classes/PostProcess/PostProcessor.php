<?php
namespace TYPO3\CMS\Form\PostProcess;

/**
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
 * The post processor
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class PostProcessor {

	/**
	 * @var \TYPO3\CMS\Form\View\Form\FormView
	 */
	protected $form;

	/**
	 * @var \TYPO3\CMS\Form\Domain\Factory\TypoScriptFactory
	 */
	protected $typoscriptFactory;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Form $form Form domain model
	 * @param array $typoScript Post processor TypoScript settings
	 */
	public function __construct(\TYPO3\CMS\Form\Domain\Model\Form $form, array $typoScript) {
		$this->form = $form;
		$this->typoscriptFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\Domain\\Factory\\TypoScriptFactory');
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
			$layoutHandler = $this->typoscriptFactory->setLayoutHandler($this->typoScript);

			foreach ($keys as $key) {
				if (!(int)$key || strpos($key, '.') !== FALSE) {
					continue;
				}
				$className = FALSE;
				$processorName = $this->typoScript[$key];
				$processorArguments = array();
				if (isset($this->typoScript[$key . '.'])) {
					$processorArguments = $this->typoScript[$key . '.'];
				}
				if (class_exists($processorName, TRUE)) {
					$className = $processorName;
				} else {
					$classNameExpanded = 'TYPO3\\CMS\\Form\\PostProcess\\' . ucfirst(strtolower($processorName)) . 'PostProcessor';
					if (class_exists($classNameExpanded, TRUE)) {
						$className = $classNameExpanded;
					}
				}
				if ($className !== FALSE) {
					$layout = $this->typoscriptFactory->getLayoutFromTypoScript($this->typoScript[$processorName . '.']);
					$layoutHandler->setLayout($layout);

					$processor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className, $this->form, $processorArguments);
					if ($processor instanceof PostProcessorInterface) {
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
