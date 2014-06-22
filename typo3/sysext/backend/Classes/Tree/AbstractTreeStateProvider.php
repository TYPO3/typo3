<?php
namespace TYPO3\CMS\Backend\Tree;

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
 * Abstract State Provider
 *
 * @TODO This class is incomplete, because the methods still need
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @author Steffen Ritter <info@steffen-ritter.net>
 */
abstract class AbstractTreeStateProvider {

	/**
	 * Sets the current tree state
	 *
	 * @return void
	 */
	abstract public function setState();

	/**
	 * Returns the last tree state
	 *
	 * @return something
	 */
	abstract public function getState();

}
