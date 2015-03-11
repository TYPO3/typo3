<?php
namespace TYPO3\CMS\Frontend\ContentObject;

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
use TYPO3\CMS\Core\Resource\ResourceFactory;

/**
 * Contains an abstract class for all tslib content class implementations.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
abstract class AbstractContentObject {

	/**
	 * @var ContentObjectRenderer
	 */
	protected $cObj;

	/**
	 * @var ResourceFactory
	 */
	protected $fileFactory = NULL;

	/**
	 * Default constructor.
	 *
	 * @param ContentObjectRenderer $cObj
	 */
	public function __construct(ContentObjectRenderer $cObj) {
		$this->cObj = $cObj;
		$this->fileFactory = ResourceFactory::getInstance();
	}

	/**
	 * Renders the content object.
	 *
	 * @param array $conf
	 * @return string
	 */
	abstract public function render($conf = array());

	/**
	 * Getter for current cObj
	 *
	 * @return ContentObjectRenderer
	 */
	public function getContentObject() {
		return $this->cObj;
	}

}
