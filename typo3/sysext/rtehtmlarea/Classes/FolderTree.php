<?php
namespace TYPO3\CMS\Rtehtmlarea;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Base extension class which generates the folder tree.
 * Used directly by the RTE.
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class FolderTree extends \localFolderTree {

	/**
	 * Constructor function of the class
	 */
	public function __construct() {
		// The backpath is set here to fix problems with relatives path when used in ajax scope
		$GLOBALS['BACK_PATH'] = isset($GLOBALS['ajaxID']) ? '../../../' : $GLOBALS['BACK_PATH'];
		parent::__construct();
	}

	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param string $title Title, ready for output.
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject The "record"
	 * @return string Wrapping title string.
	 * @todo Define visibility
	 */
	public function wrapTitle($title, \TYPO3\CMS\Core\Resource\Folder $folderObject) {
		if ($this->ext_isLinkable($folderObject)) {
			$aOnClick = 'return jumpToUrl(\'' . $this->getThisScript() . 'act=' . $GLOBALS['SOBE']->browser->act . '&mode=' . $GLOBALS['SOBE']->browser->mode . '&editorNo=' . $GLOBALS['SOBE']->browser->editorNo . '&contentTypo3Language=' . $GLOBALS['SOBE']->browser->contentTypo3Language . '&contentTypo3Charset=' . $GLOBALS['SOBE']->browser->contentTypo3Charset . '&expandFolder=' . rawurlencode($folderObject->getCombinedIdentifier()) . '\');';
			return '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $title . '</a>';
		} else {
			return '<span class="typo3-dimmed">' . $title . '</span>';
		}
	}

	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param string $icon HTML string to wrap, probably an image tag.
	 * @param string $cmd Command for 'PM' get var
	 * @param boolean $isExpand If expanded
	 * @return string Link-wrapped input string
	 * @access private
	 */
	public function PMiconATagWrap($icon, $cmd, $isExpand = TRUE) {

		if (empty($this->scope)) {
			$this->scope = array(
				'class' => get_class($this),
				'script' => $this->thisScript,
				'ext_noTempRecyclerDirs' => $this->ext_noTempRecyclerDirs,
				'browser' => array(
					'mode' => $GLOBALS['SOBE']->browser->mode,
					'act' => $GLOBALS['SOBE']->browser->act,
					'editorNo' => $GLOBALS['SOBE']->browser->editorNo
				),
			);
		}

		if ($this->thisScript) {
			// Activates dynamic AJAX based tree
			$scopeData = serialize($this->scope);
			$scopeHash = GeneralUtility::hmac($scopeData);
			$js = htmlspecialchars('Tree.load(' . GeneralUtility::quoteJSvalue($cmd) . ', ' . (int)$isExpand . ', this, ' . GeneralUtility::quoteJSvalue($scopeData) . ', ' . GeneralUtility::quoteJSvalue($scopeHash) . ');');
			return '<a class="pm" onclick="' . $js . '">' . $icon . '</a>';
		} else {
			return $icon;
		}
	}

}
