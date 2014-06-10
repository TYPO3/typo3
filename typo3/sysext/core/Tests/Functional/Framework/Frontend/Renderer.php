<?php
namespace TYPO3\CMS\Core\Tests\Functional\Framework\Frontend;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Oliver Hader <oliver.hader@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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
