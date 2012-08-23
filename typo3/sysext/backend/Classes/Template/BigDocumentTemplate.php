<?php
namespace TYPO3\CMS\Backend\Template;

// Extension classes of the template class.
// These are meant to provide backend screens with different widths.
// They still do because of the different class-prefixes used for the <div>-sections
// but obviously the final width is determined by the stylesheet used.
/**
 * Extension class for "template" - used for backend pages which are wide. Typically modules taking up all the space in the "content" frame of the backend
 * The class were more significant in the past than today.
 */
class BigDocumentTemplate extends \TYPO3\CMS\Backend\Template\DocumentTemplate {

	/**
	 * @todo Define visibility
	 */
	public $divClass = 'typo3-bigDoc';

}


?>