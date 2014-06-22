<?php
namespace TYPO3\CMS\Core\Tests\Functional\Framework\Frontend;

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
 * Model of frontend response
 */
class Renderer implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array
	 */
	protected $sections = array();

	/**
	 * @param string $content
	 * @param NULL|array $configuration
	 * @return void
	 */
	public function parseValues($content, array $configuration = NULL) {
		if (empty($content)) {
			return;
		}

		$values = json_decode($content, TRUE);

		if (empty($values) || !is_array($values)) {
			return;
		}

		$asPrefix = (!empty($configuration['as']) ? $configuration['as'] . ':' : NULL);
		foreach ($values as $identifier => $structure) {
			$parser = $this->createParser();
			$parser->parse($structure);

			$section = array(
				'structure' => $structure,
				'structurePaths' => $parser->getPaths(),
				'records' => $parser->getRecords(),
			);

			$this->addSection($section, $asPrefix . $identifier);
		}
	}

	/**
	 * @param array $section
	 * @param NULL|string $as
	 */
	public function addSection(array $section, $as = NULL) {
		if (!empty($as)) {
			$this->sections[$as] = $section;
		} else {
			$this->sections[] = $section;
		}
	}

	/**
	 * @param string $content
	 * @param NULL|array $configuration
	 * @return string
	 */
	public function renderSections($content, array $configuration = NULL) {
		$content = json_encode($this->sections);
		return $content;
	}

	/**
	 * @return Parser
	 */
	protected function createParser() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Tests\\Functional\\Framework\\Frontend\\Parser'
		);
	}

}
