<?php
namespace TYPO3\CMS\Install\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Ernesto Baschny <ernst@cron-it.de>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Service for determining the current context (as a backend module or in standalone mode)
 */
class ContextService {

	/**
	 * @var bool
	 */
	private $backendContext = FALSE;

	/**
	 * Constructor, prepare the context information
	 */
	public function __construct() {
		$formValues = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('install');
		if (isset($formValues['context'])) {
			$this->backendContext = ($formValues['context'] === 'backend');
		}
	}

	/**
	 * Is the install tool running in the backend?
	 *
	 * @return boolean
	 */
	public function isBackendContext() {
		return $this->backendContext;
	}

	/**
	 * Is the install tool running as a standalone application?
	 *
	 * @return boolean
	 */
	public function isStandaloneContext() {
		return !$this->backendContext;
	}

	/**
	 * Is the install tool running as a standalone application?
	 *
	 * @return boolean
	 */
	public function getContextString() {
		return ( $this->isBackendContext() ? 'backend' : 'standalone' );
	}
}
