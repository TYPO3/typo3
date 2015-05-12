<?php
namespace TYPO3\CMS\Backend\Form;

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
 * Interface must be implemented by all container and widget classes
 */
interface NodeInterface {

	/**
	 * Set global options from parent instance
	 *
	 * @param array $globalOptions Global options like 'readonly' for all elements
	 * @return $this
	 */
	public function setGlobalOptions(array $globalOptions);

	/**
	 * Main render method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render();

}
