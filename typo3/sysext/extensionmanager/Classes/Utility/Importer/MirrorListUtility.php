<?php
namespace TYPO3\CMS\Extensionmanager\Utility\Importer;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Marcus Krause <marcus#exp2010@t3sec.info>
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
 * Module: Extension manager - Mirror list importer
 *
 * @author Marcus Krause <marcus#exp2010@t3sec.info>
 * @author Steffen Kamper <info@sk-typo3.de>
 */
/**
 * Importer object for mirror list.
 *
 * @author Marcus Krause <marcus#exp2010@t3sec.info>
 * @author Steffen Kamper <info@sk-typo3.de>
 * @since 2010-02-10
 */
class MirrorListUtility implements \SplObserver {

	/**
	 * Keeps instance of a XML parser.
	 *
	 * @var \TYPO3\CMS\Extensionmanager\Utility\Parser\AbstractMirrorXmlParser
	 */
	protected $parser;

	/**
	 * Keeps mirrors' details.
	 *
	 * @var array
	 */
	protected $arrTmpMirrors = array();

	/**
	 * Class constructor.
	 *
	 * Method retrieves and initializes extension XML parser instance.
	 *
	 * @throws tx_em_XmlException
	 */
	public function __construct() {
		// TODO catch parser exception
		$this->parser = \TYPO3\CMS\Extensionmanager\Utility\Parser\XmlParserFactory::getParserInstance('mirror');
		if (is_object($this->parser)) {
			$this->parser->attach($this);
		} else {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(get_class($this) . ': No XML parser available.', 1342640390);
		}
	}

	/**
	 * Method collects mirrors' details and returns instance of
	 * Tx_Extensionmanager_Domain_Model_Mirrors with retrieved details.
	 *
	 * @param string $localMirrorListFile absolute path to local mirror xml.gz file
	 * @return \TYPO3\CMS\Extensionmanager\Domain\Model\Mirrors
	 */
	public function getMirrors($localMirrorListFile) {
		$zlibStream = 'compress.zlib://';
		$this->parser->parseXml($zlibStream . $localMirrorListFile);
		/** @var $objRepositoryMirrors \TYPO3\CMS\Extensionmanager\Domain\Model\Mirrors */
		$objRepositoryMirrors = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Mirrors');
		$objRepositoryMirrors->setMirrors($this->arrTmpMirrors);
		$this->arrTmpMirrors = array();
		return $objRepositoryMirrors;
	}

	/**
	 * Method receives an update from a subject.
	 *
	 * @param SplSubject $subject a subject notifying this observer
	 * @return void
	 */
	public function update(\SplSubject $subject) {
		// TODO mirrorxml_abstract_parser
		if (is_subclass_of($subject, 'TYPO3\\CMS\\Extensionmanager\\Utility\\Parser\\AbstractXmlParser')) {
			$this->arrTmpMirrors[] = $subject->getAll();
		}
	}

}


?>