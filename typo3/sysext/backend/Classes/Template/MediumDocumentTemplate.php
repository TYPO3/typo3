<?php
namespace TYPO3\CMS\Backend\Template;

/**
 * Extension class for "template" - used for backend pages which were medium wide. Typically submodules to Web or File which were presented in the list-frame when the content frame were divided into a navigation and list frame.
 * The class were more significant in the past than today. But probably you should use this one for most modules you make.
 */
class MediumDocumentTemplate extends \TYPO3\CMS\Backend\Template\DocumentTemplate {

	/**
	 * @todo Define visibility
	 */
	public $divClass = 'typo3-mediumDoc';

}


?>