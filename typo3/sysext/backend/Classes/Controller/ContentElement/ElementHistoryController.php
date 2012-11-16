<?php
namespace TYPO3\CMS\Backend\Controller\ContentElement;

/**
 * Script Class for showing the history module of TYPO3s backend
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @see class.show_rechis.inc
 */
class ElementHistoryController {

	// Internal:
	/**
	 * @todo Define visibility
	 */
	public $content;

	/**
	 * Document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\MediumDocumentTemplate
	 * @todo Define visibility
	 */
	public $doc;

	/**
	 * Initialize the module output
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		// Create internal template object:
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/show_rechis.html');
		// Start the page header:
		$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));
	}

	/**
	 * Generate module output
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		// Start history object
		$historyObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\History\\RecordHistory');
		// Get content:
		$this->content .= $historyObj->main();
		// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers['CONTENT'] = $this->content;
		$markers['CSH'] = $docHeaderButtons['csh'];
		// Build the <body> for the module
		$this->content = $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array All available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'back' => ''
		);
		// CSH
		$buttons['csh'] = \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('xMOD_csh_corebe', 'history_log', $GLOBALS['BACK_PATH'], '', TRUE);
		// Start history object
		$historyObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\History\\RecordHistory');
		if ($historyObj->returnUrl) {
			$buttons['back'] = '<a href="' . htmlspecialchars($historyObj->returnUrl) . '" class="typo3-goBack">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-view-go-back') . '</a>';
		}
		return $buttons;
	}

}


?>