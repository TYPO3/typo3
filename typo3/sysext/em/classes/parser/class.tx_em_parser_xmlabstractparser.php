<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Marcus Krause <marcus#exp2010@t3sec.info>
 *		   Steffen Kamper <info@sk-typo3.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * class.tx_em_parser_xmlabstractparser.php
 *
 * Module: Extension manager - Extension.xml abstract parser
 *
 * $Id: class.tx_em_parser_xmlabstractparser.php 1910 2010-02-21 14:31:07Z mkrause $
 *
 * @author  Marcus Krause <marcus#exp2010@t3sec.info>
 * @author  Steffen Kamper <info@sk-typo3.de>
 */

/**
 * Abstract parser for EM related TYPO3 xml files.
 *
 * @author	  Marcus Krause <marcus#exp2010@t3sec.info>
 * @author	  Steffen Kamper <info@sk-typo3.de>
 *
 * @since	   2010-02-09
 * @package	 TYPO3
 * @subpackage  EM
 */
abstract class tx_em_Parser_XmlAbstractParser {


	/**
	 * Keeps XML parser instance.
	 *
	 * @var  mixed
	 */
	protected $objXML;

	/**
	 * Keeps name of required PHP extension
	 * for this class to work properly.
	 *
	 * @var  string
	 */
	protected $requiredPHPExt;


	/**
	 * Method determines if a necessary PHP extension is available.
	 *
	 * Method tries to load the extension if necessary and possible.
	 *
	 * @access  public
	 * @return  boolean  true, if PHP extension is available, otherwise false
	 */
	public function isAvailable() {
		$isAvailable = TRUE;
		if (!extension_loaded($this->requiredPHPExt)) {
			$prefix = (PHP_SHLIB_SUFFIX === 'dll') ? 'php_' : '';
			if (!((bool) ini_get('enable_dl') && !(bool) ini_get('safe_mode') && function_exists('dl') && dl($prefix . $this->requiredPHPExt . PHP_SHLIB_SUFFIX))) {
				$isAvailable = FALSE;
			}
		}

		return $isAvailable;
	}

	/**
	 * Method parses an XML file.
	 *
	 * @access  public
	 * @param   string  $file: GZIP stream resource
	 * @return  void
	 * @throws  tx_em_XmlException  in case of XML parser errors
	 */
	abstract public function parseXML($file);

	/**
	 * Method provides a wrapper for an exception call
	 *
	 * @access  protected
	 * @param   string	 $message  the exception message to throw
	 * @param   integer	$code  the exception code
	 * @return  void
	 */
	abstract protected function throwException($message = "", $code = 0);
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/parser/class.tx_em_parser_xmlabstractparser.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/parser/class.tx_em_parser_xmlabstractparser.php']);
}
?>
