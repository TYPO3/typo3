<?php
/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * Extension of the parse_html class.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class local_t3lib_parsehtml extends \TYPO3\CMS\Core\Html\HtmlParser {

	/**
	 * Processing content between tags for HTML_cleaner
	 *
	 * @param string $value The value
	 * @param integer $dir Direction, either -1 or +1. 0 (zero) means no change to input value.
	 * @param mixed $conf Not used, ignore.
	 * @return string The processed value.
	 * @access private
	 * @todo Define visibility
	 */
	public function processContent($value, $dir, $conf) {
		$value = $this->pObj->substituteGlossaryWords_htmlcleaner_callback($value);
		return $value;
	}

}

/*
 * @deprecated since 6.0, the classname SC_view_help and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/cshmanual/Classes/Controller/HelpModuleController.php
 */
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Cshmanual\\Controller\\HelpModuleController');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
