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
 * class.tx_em_import_extensionlistimporter.php
 *
 * Module: Extension manager - Extension list importer
 *
 * $Id: class.tx_em_import_extensionlistimporter.php 2016 2010-03-14 04:01:47Z mkrause $
 *
 * @author  Marcus Krause <marcus#exp2010@t3sec.info>
 * @author  Steffen Kamper <info@sk-typo3.de>
 */

/**
 * Importer object for extension list
 *
 * @author	  Marcus Krause <marcus#exp2010@t3sec.info>
 * @author	  Steffen Kamper <info@sk-typo3.de>
 *
 * @since	   2010-02-10
 * @package	 TYPO3
 * @subpackage  EM
 */
class tx_em_Import_ExtensionListImporter implements SplObserver {

	/**
	 * Keeps instance of a XML parser.
	 *
	 * @var em_xml_abstract_parser
	 */
	protected $parser;

	/**
	 * Keeps number of processed version records.
	 *
	 * @var integer
	 */
	protected $sumRecords = 0;

	/**
	 * Keeps record values to be inserted into database.
	 *
	 * @var  array
	 */
	protected $arrRows = array();

	/**
	 * Keeps fieldnames of cache_extension table.
	 *
	 * @var  array
	 */
	static protected $fieldNames = array('extkey', 'version', 'intversion', 'alldownloadcounter', 'downloadcounter', 'title', 'ownerusername', 'authorname', 'authoremail', 'authorcompany', 'lastuploaddate', 't3xfilemd5', 'repository', 'state', 'reviewstate', 'category', 'description', 'dependencies', 'uploadcomment' /*, 'lastversion', 'lastreviewedversion'*/);

	/**
	 * Keeps indexes of fields that should not be quoted.
	 *
	 * @var  array
	 */
	static protected $fieldIndicesNoQuote = array(2, 3, 4, 10, 12, 13, 14, 15);


	/**
	 * Keeps repository UID.
	 *
	 * The UID is necessary for inserting records.
	 *
	 * @var  integer
	 */
	protected $repositoryUID = 1;


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
		$this->parser = em_xml_parser_factory::getParserInstance('extension');
		if (!is_object(tx_em_Parser_XmlParserFactory::getParserInstance('extension'))) {
			throw new tx_em_XmlException(get_class($this) . ': ' . 'No XML parser available.');
		}
	}

	/**
	 * Gets parser object
	 *
	 * @return tx_em_XmlParserFactory
	 */
	protected function getParser() {
		$parser = tx_em_Parser_XmlParserFactory::getParserInstance('extension');
		$parser->attach($this);
		return $parser;
	}

	/**
	 * Method initializes parsing of extension.xml.gz file.
	 *
	 * @access  public
	 * @param   string   $localExtListFile  absolute path to (gzipped) local extension list xml file
	 * @param   integer  $repositoryUID	 UID of repository to be used when inserting records into DB
	 * @return  integer  total number of imported extension versions
	 */
	public function import($localExtListFile, $repositoryUID = NULL) {
		if (!is_null($repositoryUID) && is_int($repositoryUID)) {
			$this->repositoryUID = $repositoryUID;
		}
		$zlibStream = 'compress.zlib://';
		$this->sumRecords = 0;

		$this->getParser()->parseXML($zlibStream . $localExtListFile);

		// flush last rows to database if existing
		if (count($this->arrRows)) {
			$GLOBALS['TYPO3_DB']->exec_INSERTmultipleRows(
				'cache_extensions',
				self::$fieldNames,
				$this->arrRows,
				self::$fieldIndicesNoQuote
			);
		}
		return $this->sumRecords;
	}

	/**
	 * Method collects and stores extension version details into the database.
	 *
	 * @access  protected
	 * @param   SplSubject  $subject  a subject notifying this observer
	 * @return  void
	 */
	protected function loadIntoDB(SplSubject &$subject) {
		// flush every 50 rows to database
		if ($this->sumRecords !== 0 && $this->sumRecords % 50 === 0) {
			$GLOBALS['TYPO3_DB']->exec_INSERTmultipleRows(
				'cache_extensions',
				self::$fieldNames,
				$this->arrRows,
				self::$fieldIndicesNoQuote
			);
			$this->arrRows = array();
		}
		// order must match that of self::$fieldNamses!
		$this->arrRows[] = array(
			$subject->getExtkey(),
			$subject->getVersion(),
			tx_em_Tools::makeVersion($subject->getVersion(), 'int'),
			intval($subject->getAlldownloadcounter()),
			intval($subject->getDownloadcounter()),
			!is_null($subject->getTitle()) ? $subject->getTitle() : '',
			$subject->getOwnerusername(),
			!is_null($subject->getAuthorname()) ? $subject->getAuthorname() : '',
			!is_null($subject->getAuthoremail()) ? $subject->getAuthoremail() : '',
			!is_null($subject->getAuthorcompany()) ? $subject->getAuthorcompany() : '',
			intval($subject->getLastuploaddate()),
			$subject->getT3xfilemd5(),
			$this->repositoryUID,
			tx_em_Tools::getDefaultState($subject->getState() ? $subject->getState() : ''),
			intval($subject->getReviewstate()),
			tx_em_Tools::getDefaultCategory($subject->getCategory() ? $subject->getCategory() : ''),
			$subject->getDescription() ? $subject->getDescription() : '',
			$subject->getDependencies() ? $subject->getDependencies() : '',
			$subject->getUploadcomment() ? $subject->getUploadcomment() : '',
		);
		++$this->sumRecords;
	}

	/**
	 * Method receives an update from a subject.
	 *
	 * @access  public
	 * @param   SplSubject  $subject  a subject notifying this observer
	 * @return  void
	 */
	public function update(SplSubject $subject) {
		if (is_subclass_of($subject, 'tx_em_ExtensionXmlAbstractParser')) {
			$this->loadIntoDB($subject);
		}
	}
}

?>