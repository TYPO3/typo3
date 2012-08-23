<?php
namespace TYPO3\CMS\Backend\Template;

/**
 * Extension class for "template" - used for backend pages which were narrow (like the Web>List modules list frame. Or the "Show details" pop up box)
 * The class were more significant in the past than today.
 */
class SmallDocumentTemplate extends \TYPO3\CMS\Backend\Template\DocumentTemplate {

	/**
	 * @todo Define visibility
	 */
	public $divClass = 'typo3-smallDoc';

}


?>