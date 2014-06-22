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
 * Interface for postprocessors
 *
 * @author Franz Geiger <mail@fx-g.de>
 */
interface PostProcessorInterface {

	/**
	 * Constructor
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Form $form Form domain model
	 * @param array $typoScript Post processor TypoScript settings
	 */
	public function __construct(\TYPO3\CMS\Form\Domain\Model\Form $form, array $typoScript);

	/**
	 * The main method called by the post processor
	 *
	 * @return string The post processing HTML
	 */
	public function process();

}
