<?php
namespace TYPO3\CMS\Form\View\Form\Element;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Model\Element\AbstractElement;

/**
 * View object for the image button element
 * @deprecated The element IMAGEBUTTON is deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8.
 */
class ImagebuttonElementView extends \TYPO3\CMS\Form\View\Form\Element\AbstractElementView {

	/**
	 * Default layout of this object
	 *
	 * @var string
	 */
	protected $layout = '
		<label />
		<input />
	';

	/**
	 * Constructor
	 *
	 * @param AbstractElement $model Current elements model
	 */
	public function __construct(AbstractElement $model) {
		GeneralUtility::deprecationLog('The element IMAGEBUTTON is deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8.');
		parent::__construct($model);
	}

}
