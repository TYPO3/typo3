<?php
namespace TYPO3\CMS\Backend\Template;

/**
 * Extension class for "template" - used for backend pages without the "document" background image
 * The class were more significant in the past than today.
 *
 * @deprecated since 6.1 will be removed two versions later
 */
class StandardDocumentTemplate extends \TYPO3\CMS\Backend\Template\DocumentTemplate {

	public function __construct() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		parent::__construct();
	}

}

?>