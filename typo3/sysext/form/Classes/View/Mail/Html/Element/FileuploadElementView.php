<?php
namespace TYPO3\CMS\Form\View\Mail\Html\Element;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Rens Admiraal (r.admiraal@drecomm.nl)
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * View object for the fileupload element
 *
 * @author Rens Admiraal <r.admiraal@drecomm.nl>
 */
class FileuploadElementView extends \TYPO3\CMS\Form\View\Mail\Html\Element\AbstractElementView {

	/**
	 * Default layout of this object
	 *
	 * @var string
	 */
	protected $layout = '
		<td style="width: 200px;">
			<label />
		</td>
		<td>
			<inputvalue />
		</td>
	';

	/**
	 * Constructor
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Element\FileuploadElement $model Model for this element
	 */
	public function __construct(\TYPO3\CMS\Form\Domain\Model\Element\FileuploadElement $model) {
		parent::__construct($model);
	}

}

?>