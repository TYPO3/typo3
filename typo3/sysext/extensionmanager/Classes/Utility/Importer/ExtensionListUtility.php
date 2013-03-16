<?php
namespace TYPO3\CMS\Extensionmanager\Utility\Importer;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Marcus Krause <marcus#exp2010@t3sec.info>
 * Steffen Kamper <info@sk-typo3.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Module: Extension manager - Extension list importer
 *
 * @author Marcus Krause <marcus#exp2010@t3sec.info>
 * @author Steffen Kamper <info@sk-typo3.de>
 */
/**
 * Importer object for extension list
 *
 * @author Marcus Krause <marcus#exp2010@t3sec.info>
 * @author Steffen Kamper <info@sk-typo3.de>
 * @since 2010-02-10
 */
class ExtensionListUtility implements \SplObserver {

	/**
	 * Keeps instance of a XML parser.
	 *
	 * @var \TYPO3\CMS\Extensionmanager\Utility\Parser\AbstractExtensionXmlParser
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
	 * @var array
	 */
	protected $arrRows = array();

	/**
	 * Keeps fieldnames of tx_extensionmanager_domain_model_extension table.
	 *
	 * @var array
	 */
	static protected $fieldNames = array(
		'extension_key',
		'version',
		'integer_version',
		'alldownloadcounter',
		'downloadcounter',
		'title',
		'ownerusername',
		'author_name',
		'author_email',
		'authorcompany',
		'last_updated',
		'md5hash',
		'repository',
		'state',
		'review_state',
		'category',
		'description',
		'serialized_dependencies',
		'update_comment'
	);

	/**
	 * Keeps indexes of fields that should not be quoted.
	 *
	 * @var array
	 */
	static protected $fieldIndicesNoQuote = array(2, 3, 4, 10, 12, 13, 14, 15);

	/**
	 * Keeps repository UID.
	 *
	 * The UID is necessary for inserting records.
	 *
	 * @var integer
	 */
	protected $repositoryUid = 1;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository
	 */
	protected $repositoryRepository;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
	 */
	protected $extensionRepository;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Model\Extension
	 */
	protected $extensionModel;

	/**
	 * Class constructor.
	 *
	 * Method retrieves and initializes extension XML parser instance.
	 *
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 */
	public function __construct() {
		/** @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->repositoryRepository = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\RepositoryRepository');
		$this->extensionRepository = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\ExtensionRepository');
		$this->extensionModel = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension');
		// TODO catch parser exception
		$this->parser = \TYPO3\CMS\Extensionmanager\Utility\Parser\XmlParserFactory::getParserInstance('extension');
		if (is_object($this->parser)) {
			$this->parser->attach($this);
		} else {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(get_class($this) . ': No XML parser available.');
		}
	}

	/**
	 * Method initializes parsing of extension.xml.gz file.
	 *
	 * @param string $localExtensionListFile absolute path to extension list xml.gz
	 * @param integer $repositoryUid UID of repository when inserting records into DB
	 * @return integer total number of imported extension versions
	 */
	public function import($localExtensionListFile, $repositoryUid = NULL) {
		if (!is_null($repositoryUid) && is_int($repositoryUid)) {
			$this->repositoryUid = $repositoryUid;
		}
		$zlibStream = 'compress.zlib://';
		$this->sumRecords = 0;
		$this->parser->parseXML($zlibStream . $localExtensionListFile);
		// flush last rows to database if existing
		if (count($this->arrRows)) {
			$GLOBALS['TYPO3_DB']->exec_INSERTmultipleRows('tx_extensionmanager_domain_model_extension', self::$fieldNames, $this->arrRows, self::$fieldIndicesNoQuote);
		}
		$extensions = $this->extensionRepository->insertLastVersion($this->repositoryUid);
		$this->repositoryRepository->updateRepositoryCount($extensions, $this->repositoryUid);
		return $this->sumRecords;
	}

	/**
	 * Method collects and stores extension version details into the database.
	 *
	 * @param SplSubject &$subject a subject notifying this observer
	 * @return void
	 */
	protected function loadIntoDatabase(\SplSubject &$subject) {
		// flush every 50 rows to database
		if ($this->sumRecords !== 0 && $this->sumRecords % 50 === 0) {
			$GLOBALS['TYPO3_DB']->exec_INSERTmultipleRows('tx_extensionmanager_domain_model_extension', self::$fieldNames, $this->arrRows, self::$fieldIndicesNoQuote);
			$this->arrRows = array();
		}
		$versionRepresentations = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionStringToArray($subject->getVersion());
		// order must match that of self::$fieldNamses!
		$this->arrRows[] = array(
			$subject->getExtkey(),
			$subject->getVersion(),
			$versionRepresentations['version_int'],
			intval($subject->getAlldownloadcounter()),
			intval($subject->getDownloadcounter()),
			!is_null($subject->getTitle()) ? $subject->getTitle() : '',
			$subject->getOwnerusername(),
			!is_null($subject->getAuthorname()) ? $subject->getAuthorname() : '',
			!is_null($subject->getAuthoremail()) ? $subject->getAuthoremail() : '',
			!is_null($subject->getAuthorcompany()) ? $subject->getAuthorcompany() : '',
			intval($subject->getLastuploaddate()),
			$subject->getT3xfilemd5(),
			$this->repositoryUid,
			$this->extensionModel->getDefaultState($subject->getState() ? $subject->getState() : ''),
			intval($subject->getReviewstate()),
			$this->extensionModel->getCategoryIndexFromStringOrNumber($subject->getCategory() ? $subject->getCategory() : ''),
			$subject->getDescription() ? $subject->getDescription() : '',
			$subject->getDependencies() ? $subject->getDependencies() : '',
			$subject->getUploadcomment() ? $subject->getUploadcomment() : ''
		);
		++$this->sumRecords;
	}

	/**
	 * Method receives an update from a subject.
	 *
	 * @param SplSubject $subject a subject notifying this observer
	 * @return void
	 */
	public function update(\SplSubject $subject) {
		if (is_subclass_of($subject, 'TYPO3\\CMS\\Extensionmanager\\Utility\\Parser\\AbstractExtensionXmlParser')) {
			$this->loadIntoDatabase($subject);
		}
	}

}


?>