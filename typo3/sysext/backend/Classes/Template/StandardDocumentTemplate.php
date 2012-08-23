<?php
namespace TYPO3\CMS\Backend\Template;

/**
 * Extension class for "template" - used for backend pages without the "document" background image
 * The class were more significant in the past than today.
 */
class StandardDocumentTemplate extends \TYPO3\CMS\Backend\Template\DocumentTemplate {

	/**
	 * @todo Define visibility
	 */
	public $divClass = 'typo3-noDoc';

}


?>