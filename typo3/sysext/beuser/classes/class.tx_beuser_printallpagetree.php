<?php
/**
 * Extension class for printing a page tree: All pages of a mount point.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_beuser
 */
class tx_beuser_printAllPageTree extends tx_beuser_localPageTree {
	var $expandFirst=1;
	var $expandAll=1;

	/**
	 * Return select permissions.
	 *
	 * @return	string		WHERE query part.
	 */
	function ext_permsC() {
		return ' AND '.$this->BE_USER->getPagePermsClause(1);
	}

	/**
	 * Returns the plus/minus icon.
	 *
	 * @param	string		[See parent]
	 * @param	string		[See parent]
	 * @param	string		[See parent]
	 * @return	string
	 */
	function PM_ATagWrap($icon, $cmd, $bMark='') {
		return $icon;
	}

	/**
	 * Wrapping the icon of the element/page. Normally a click menu is wrapped around the icon, but in this case only a title parameter is set.
	 *
	 * @param	string		Icon image tag.
	 * @param	array		Row.
	 * @return	string		Icon with title attribute added.
	 */
	function wrapIcon($icon, $row) {
		// Add title attribute to input icon tag
		$title = '['.$row['uid'].']';
		$theIcon = $this->addTagAttributes($icon, ($this->titleAttrib ? $this->titleAttrib.'="'.htmlspecialchars($title).'"' : '').' border="0"');

		return $theIcon;
	}
}
?>