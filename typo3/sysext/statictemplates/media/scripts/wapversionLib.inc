<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2009 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Class for creating WAP pages for TYPO3
 *
 * Class that creates the current page and content element records as an WML
 * structure using the library "t3lib_xml"
 * It is demonstrated in use in the testsite package on page "59"
 * The static template "plugin.alt.wap" is used to trigger this WML creation as well.
 * That template contains this set of TypoScript lines which triggers the WML creation
 * and disables all regular HTML headers
 *
 * ## Set up page/type:
 * alt_wap >
 * alt_wap = PAGE
 * alt_wap {
 * typeNum=97
 * config.disableAllHeaderCode = 1
 * config.additionalHeaders = Content-type: text/vnd.wap.wml
 *
 * ## Includes the newsLib:
 * includeLibs.alt_wap = media/scripts/wapversionLib.inc
 *
 * ## Inserting the USER cObject for WAP/XML rendering
 * 10 = USER
 * 10 {
 * userFunc = user_wapversion->main_wapversion
 * debug=0
 * preTitle = T3WAP
 * navLabels.prev = Prev
 * navLabels.next = Next
 * navLabels.up = Up
 * }
 * }
 *
 * NOTICE:
 *
 * In the static template "plugin.alt.wap" there is a part in the
 * end looking like this:
 *
 * ## If the browser is a WAP-device,
 * [device=wap]
 * alt_wap.typeNum=0
 * [global]
 *
 * This means that IF the device coming to the URL is a WAP device they will get
 * wap content even if they don't specify "&type=97" since the typeNum is
 * changed to zero here! In fact they CANNOT get any wap-content at
 * "&type=97" anymore! This has been a source of error and confusion
 * for many people
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class user_wapversion {

	// The backReference to the mother cObj object set at call time
	/**
	 * @todo Define visibility
	 */
	public $cObj;

	/**
	 * @todo Define visibility
	 */
	public $idx = 0;

	/**
	 * Main function, called from TypoScript
	 *
	 * @param string $content Empty, ignore.
	 * @param array $conf TypoScript properties for this content object/function call
	 * @return string WML content
	 * @todo Define visibility
	 */
	public function main_wapversion($content, $conf) {
		$GLOBALS['TSFE']->set_no_cache();
		$xmlObj = t3lib_div::makeInstance('t3lib_xml', 'wml');
		$xmlObj->XMLdebug = $conf['debug'];
		// Creating top level object
		$xmlObj->WAPHeader();
		// Creating back button:
		$xmlObj->WAPback();
		$pageRec = $GLOBALS['TSFE']->page;
		if ($GLOBALS['TSFE']->idParts[1]) {
			// Creating content card:
			$xmlObj->newLevel('card', 1, array(
				'id' => 'content',
				'title' => ($conf['preTitle'] ? $conf['preTitle'] . ': ' : '') . $pageRec['title']
			));
			$cParts = $this->contentAll();
			$pointer = t3lib_utility_Math::forceIntegerInRange($GLOBALS['TSFE']->idParts[1], 1, 10000);
			$msg = '';
			if ($pointer - 1) {
				$msg .= $this->navLink(htmlspecialchars($conf['navLabels.']['prev']), ($pointer - 1)) . ' ';
			}
			$msg .= $this->navLink(htmlspecialchars($conf['navLabels.']['up']), 0) . ' ';
			if ($pointer < count($cParts)) {
				$msg .= $this->navLink(htmlspecialchars($conf['navLabels.']['next']), ($pointer + 1)) . ' ';
			}
			$msg .= ((('[' . $pointer) . '/') . count($cParts)) . ']<br/>';
			$xmlObj->lines[] = $this->paragraph($msg);
			$xmlObj->lines[] = $this->paragraph($cParts[$pointer - 1]);
			$xmlObj->lines[] = $this->paragraph('<br/>' . $msg);
			$xmlObj->newLevel('card', 0);
		} else {
			// Creating menu card:
			$xmlObj->newLevel('card', 1, array(
				'id' => 'menu',
				'title' => ($conf['preTitle'] ? $conf['preTitle'] . ': ' : '') . $pageRec['title']
			));
			$xmlObj->lines[] = $this->contentAbstract();
			$xmlObj->lines[] = ('<p><br/>' . $this->bold('Menu:')) . '</p>';
			$xmlObj->lines[] = $this->menuCurrentLevel($xmlObj->Icode);
			$xmlObj->newLevel('card', 0);
		}
		// Footer
		$xmlObj->renderFooter();
		return $xmlObj->getResult();
	}

	/**
	 * Getting abstract of the first content elements header
	 * and bodytext for the menu
	 *
	 * @return string WML string
	 * @todo Define visibility
	 */
	public function contentAbstract() {
		$res = $this->getContentResult('tt_content');
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$out = ($this->bold(t3lib_div::fixed_lgd_cs(htmlspecialchars($row['header']), 20)) . '<br/>') . t3lib_div::fixed_lgd_cs(htmlspecialchars($row['bodytext']), 50);
		$out = ((('<p>' . $out) . ' <a href="') . htmlspecialchars(((('?id=' . $GLOBALS['TSFE']->id) . ',1.') . $GLOBALS['TSFE']->type))) . '">[more]</a></p>';
		return $out;
	}

	/**
	 * Returns all page content, but in an array where the content is
	 * divided into chunks or a max length (for WAP clients with limited
	 * memory capabilities) Content is then displayed using the pointer
	 * value found in $GLOBALS['TSFE']->idParts[1], see main_wapversion()
	 *
	 * @param integer $chunkLgd Max length of each content chunk
	 * @return array Array with the page content divided into chucks WML code (default length equals $chunkLgd; 850)
	 * @see main_wapversion()
	 * @todo Define visibility
	 */
	public function contentAll($chunkLgd = 850) {
		$res = $this->getContentResult('tt_content');
		$overlap = 5;
		$idx = 0;
		$out = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			// Header:
			$get = ('<br/>' . $this->cHeader($row['header'])) . '<br/>';
			if (strlen($out[$idx] . $get) > $chunkLgd) {
				$idx++;
			}
			$out[$idx] .= $get;
			switch ($row['CType']) {
			case 'text':

			case 'bullets':

			case 'table':
				$bodyText = $row['bodytext'];
				break;
			case 'textpic':

			case 'image':
				if ($row['CType'] != 'image') {
					$bodyText = $row['bodytext'];
				}
				$bodyText .= ((((chr(10) . '[') . count(explode(',', $row['image']))) . 'images, caption: ') . $row['imagecaption']) . ']';
				break;
			case 'header':
				$bodyText = $row['subheader'];
				break;
			default:
				$bodyText = ('[Un-rendered element, ' . $row['CType']) . ']';
				break;
			}
			// Bodytext:
			$get = $this->cBodytext($bodyText) . '<br/>';
			$diff = $chunkLgd - strlen($out[$idx]);
			if ($diff > strlen($get)) {
				$out[$idx] .= $get;
			} else {
				$out[$idx] .= $this->cBodytext($bodyText, 0, ($diff + $overlap)) . '<br/>';
				$safe = 0;
				do {
					$idx++;
					$out[$idx] .= $this->cBodytext($bodyText, (($diff + $safe * $chunkLgd) - $overlap), ($chunkLgd + $overlap)) . '<br/>';
					$safe++;
					if ($safe > 100) {
						break;
					}
				} while (strlen($out[$idx]) > $chunkLgd);
			}
		}
		return $out;
	}

	/**
	 * Formats the header for a content element
	 *
	 * @param string $str Header value to format
	 * @return string Returns the formatted version, stripped from tags and htmlspecialchar()'ed
	 * @see contentAll()
	 * @todo Define visibility
	 */
	public function cHeader($str) {
		$out = $this->bold(htmlspecialchars(strip_tags($str)));
		return $out;
	}

	/**
	 * Formats the bodytext for a content element
	 *
	 * @param string $str The bodytext content
	 * @param integer $start Position where to start in the bodytext stream. If larger than zero a prefix, "...", is prepended.
	 * @param integer $max Max length
	 * @return string Prepared content
	 * @see contentAll()
	 * @todo Define visibility
	 */
	public function cBodytext($str, $start = 0, $max = 0) {
		$out = t3lib_div::fixed_lgd_cs(($start ? '...' : '') . substr($this->nl2br(htmlspecialchars(strip_tags($str))), $start), $max ? $max : 100000);
		// No & in WAP docs??? --> or maybe just htmlspecialchar() things
		// as the LAST thing instead!)
		$out = str_replace('&', '', $out);
		return $out;
	}

	/**
	 * Local version of ml2br(). Replaces linebreaks with <br/> tags.
	 *
	 * @param string $str The input string to process
	 * @return string The processed value returned.
	 * @todo Define visibility
	 */
	public function nl2br($str) {
		return str_replace(chr(10), '<br/>', $str);
	}

	/**
	 * Selects all records from $table having the current page id as PID
	 * (records belonging to that page)
	 * Used to select content elements from "tt_content"
	 *
	 * @param string $table A tablename found in $GLOBALS['TCA']
	 * @return pointer A database resource pointer
	 * @todo Define visibility
	 */
	public function getContentResult($table) {
		if ($GLOBALS['TCA'][$table]) {
			$orderBy = $GLOBALS['TCA'][$table]['ctrl']['sortby'] ? 'ORDER BY ' . $GLOBALS['TCA'][$table]['ctrl']['sortby'] : $GLOBALS['TCA'][$table]['ctrl']['default_sortby'];
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, ('pid=' . intval($GLOBALS['TSFE']->id)) . $this->cObj->enableFields($table), '', $GLOBALS['TYPO3_DB']->stripOrderBy($orderBy));
			return $res;
		}
	}

	/**
	 * Simulates bold type - basically setting it in uppercase
	 *
	 * @param string $str The string for format in "bold" (uppercase)
	 * @return string Processed output.
	 * @todo Define visibility
	 */
	public function bold($str) {
		return strtoupper($str);
	}

	/**
	 * Wraps a string in <p>...</p> tags
	 *
	 * @param string $str The input string
	 * @return string The wrapped string
	 * @todo Define visibility
	 */
	public function paragraph($str) {
		return ('<p>' . $str) . '</p>';
	}

	/**
	 * Adds a linebreak character to the end of $str
	 *
	 * @param string $str The string/line
	 * @return string The input string with '<br/>' prepended
	 * @todo Define visibility
	 */
	public function line($str) {
		return $str . '<br/>';
	}

	/**
	 * Creates a navigation link to the next part of the page content!
	 *
	 * @param string $str The link text
	 * @param integer $pointer The pointer value
	 * @return string The input string, linked with the pointer value to the current page.
	 * @todo Define visibility
	 */
	public function navLink($str, $pointer) {
		return ((('<a href="' . htmlspecialchars(((((('?id=' . $GLOBALS['TSFE']->id) . ',') . $pointer) . '.') . $GLOBALS['TSFE']->type))) . '">') . $str) . '</a>';
	}

	/**
	 * Creates a menu for the current pagelevel. Navigation is both a
	 * path-menu (rootline) and a menu of current page subpages.
	 *
	 * @param string $indent Indentation prefix string per menu item.
	 * @return string A paragraph with the menu items inside.
	 * @todo Define visibility
	 */
	public function menuCurrentLevel($indent) {
		$rL = $GLOBALS['TSFE']->config['rootLine'];
		$preSpace = '';
		$out = array();
		// Hierarchy menu
		foreach ($rL as $level => $data) {
			$preSign = count($rL) - 1 > $level ? '-' : '>';
			$menuItem = htmlspecialchars(($preSign . ' ') . $data['title']);
			$menuItem = $this->link($preSpace . $menuItem, $data['uid']);
			$out[] = $indent . $this->line($menuItem);
			$preSpace .= '..';
		}
		// Current page menu:
		$menu = $this->cleanMenuArray($GLOBALS['TSFE']->sys_page->getMenu($GLOBALS['TSFE']->id));
		foreach ($menu as $data) {
			$preSign = count($this->cleanMenuArray($GLOBALS['TSFE']->sys_page->getMenu($data['uid']))) ? '+' : '*';
			$menuItem = htmlspecialchars(($preSign . ' ') . $data['title']);
			$menuItem = $this->link($preSpace . $menuItem, $data['uid']);
			$out[] = $indent . $this->line($menuItem);
		}
		return $this->paragraph(implode(chr(10), $out));
	}

	/**
	 * Creates a link around the input string to another page/deck
	 * Used to create menus
	 *
	 * @param string $str The string to be wrapped in <a> tags
	 * @param integer $id The page id to link to
	 * @param string $deck The deck name, if any
	 * @return string String wrapped in <a> tags
	 * @see menuCurrentLevel()
	 * @todo Define visibility
	 */
	public function link($str, $id, $deck = '') {
		if ($GLOBALS['TSFE']->id == $id && $deck) {
			$out = ((('<a href="#' . $deck) . '">') . $str) . '</a>';
		} else {
			$type = $GLOBALS['TSFE']->type;
			$out = ((('<a href="' . htmlspecialchars(((((('?id=' . $id) . ',') . ($GLOBALS['TSFE']->id == $id ? 1 : $this->idx)) . '.') . $type))) . '">') . $str) . '</a>';
		}
		return $out;
	}

	/**
	 * Cleaning up the menu array returned from sys_page->getMenu().
	 * Removing pages having "not in menu" set.
	 *
	 * @param array $menu Menu item array
	 * @return array New menu item array with "not in menu" elements removed.
	 * @todo Define visibility
	 */
	public function cleanMenuArray($menu) {
		$newMenu = array();
		foreach ($menu as $data) {
			if (!$data['nav_hide']) {
				$newMenu[] = $data;
			}
		}
		return $newMenu;
	}

}

?>