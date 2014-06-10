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
 * Model of frontend response content
 */
class ResponseContent {

	/**
	 * @var array|ResponseSection[]
	 */
	protected $sections;

	/**
	 * @var array
	 */
	protected $structure;

	/**
	 * @var array
	 */
	protected $structurePaths;

	/**
	 * @var array
	 */
	protected $records;

	/**
	 * @var array
	 */
	protected $queries;

	/**
	 * @param Response $response
	 */
	public function __construct(Response $response) {
		$content = json_decode($response->getContent(), TRUE);

		if ($content !== NULL && is_array($content)) {
			foreach ($content as $sectionIdentifier => $sectionData) {
				$section = new ResponseSection($sectionIdentifier, $sectionData);
				$this->sections[$sectionIdentifier] = $section;
			}
		}
	}

	/**
	 * @param string $sectionIdentifier
	 * @return NULL|ResponseSection
	 * @throws \RuntimeException
	 */
	public function getSection($sectionIdentifier) {
		if (isset($this->sections[$sectionIdentifier])) {
			return $this->sections[$sectionIdentifier];
		}

		throw new \RuntimeException('ResponseSection "' . $sectionIdentifier . '" does not exist');
	}

}
