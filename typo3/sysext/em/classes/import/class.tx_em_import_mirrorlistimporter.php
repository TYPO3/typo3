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
 * class.tx_em_import_mirrorlistimporter.php
 *
 * Module: Extension manager - Mirror list importer
 *
 * $Id: class.tx_em_import_mirrorlistimporter.php 1982 2010-03-09 06:29:55Z mkrause $
 *
 * @author  Marcus Krause <marcus#exp2010@t3sec.info>
 * @author  Steffen Kamper <info@sk-typo3.de>
 */


/**
 * Importer object for mirror list.
 *
 * @author	  Marcus Krause <marcus#exp2010@t3sec.info>
 * @author	  Steffen Kamper <info@sk-typo3.de>
 *
 * @since	   2010-02-10
 * @package	 TYPO3
 * @subpackage  EM
 */
class tx_em_Import_MirrorListImporter implements SplObserver {

	/**
	 * Keeps instance of a XML parser.
	 *
	 * @var  tx_em_Parser_MirrorXmlAbstractParser
	 */
	protected $parser;

	/**
	 * Keeps mirrors' details.
	 *
	 * @var  array
	 */
	protected $arrTmpMirrors = array();


	/**
	 * Class constructor.
	 *
	 * Method retrieves and initializes extension XML parser instance.
	 *
	 * @access  public
	 * @return  void
	 * @throws  tx_em_XmlException in case no valid parser instance is available
	 */
	function __construct() {
			// TODO catch parser exception
		$this->parser = tx_em_Parser_XmlParserFactory::getParserInstance('mirror');
		if (is_object($this->parser)) {
			$this->parser->attach($this);
		} else {
			throw new tx_em_XmlException(get_class($this) . ': ' . 'No XML parser available.');
		}
	}

	/**
	 * Method collects mirrors' details and returns instance of em_repository_mirrors
	 * with retrieved details.
	 *
	 * @access  public
	 * @param   string  $localMirrorListFile  bsolute path to (gzipped) local mirror list xml file
	 * @return  em_repository_mirrors
	 */
	public function getMirrors($localMirrorListFile) {
		$zlibStream = 'compress.zlib://';

		$this->parser->parseXML($zlibStream . $localMirrorListFile);
		$objRepositoryMirrors = t3lib_div::makeInstance('tx_em_Repository_Mirrors');
		$objRepositoryMirrors->setMirrors($this->arrTmpMirrors);
		$this->arrTmpMirrors = array();
		return $objRepositoryMirrors;
	}

	/**
	 * Method receives an update from a subject.
	 *
	 * @access  public
	 * @param   SplSubject  $subject  a subject notifying this observer
	 * @return  void
	 */
	public function update(SplSubject $subject) {
		// TODO mirrorxml_abstract_parser
		if (is_subclass_of($subject, 'tx_em_Parser_XmlAbstractParser')) {
			$this->arrTmpMirrors[] = $subject->getAll();
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/import/class.tx_em_import_mirrorlistimporter.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/import/class.tx_em_import_mirrorlistimporter.php']);
}

?>
