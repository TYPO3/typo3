<?php
namespace TYPO3\CMS\Backend\Template;

/**
 * Extension class for "template" - used in the context of frontend editing.
 */
class FrontendDocumentTemplate extends \TYPO3\CMS\Backend\Template\DocumentTemplate {

	/**
	 * Gets instance of PageRenderer
	 *
	 * @return \TYPO3\CMS\Core\Page\PageRenderer
	 */
	public function getPageRenderer() {
		if (!isset($this->pageRenderer)) {
			$this->pageRenderer = $GLOBALS['TSFE']->getPageRenderer();
		}
		return $this->pageRenderer;
	}

	/**
	 * Used in the frontend context to insert header data via TSFE->additionalHeaderData.
	 * Mimics header inclusion from template->startPage().
	 *
	 * @return void
	 */
	public function insertHeaderData() {
		$this->backPath = ($GLOBALS['TSFE']->backPath = TYPO3_mainDir);
		$this->pageRenderer->setBackPath($this->backPath);
		$this->docStyle();
		// Add applied JS/CSS to $GLOBALS['TSFE']
		if ($this->JScode) {
			$this->pageRenderer->addHeaderData($this->JScode);
		}
		if (count($this->JScodeArray)) {
			foreach ($this->JScodeArray as $name => $code) {
				$this->pageRenderer->addJsInlineCode($name, $code, FALSE);
			}
		}
	}

}


?>