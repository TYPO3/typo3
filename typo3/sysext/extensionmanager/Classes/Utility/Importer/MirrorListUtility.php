<?php
namespace TYPO3\CMS\Extensionmanager\Utility\Importer;

/*
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
 * Module: Extension manager - Mirror list importer
 */
/**
 * Importer object for mirror list.
 * @since 2010-02-10
 */
class MirrorListUtility implements \SplObserver
{
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
    protected $arrTmpMirrors = [];

    /**
     * Class constructor.
     *
     * Method retrieves and initializes extension XML parser instance
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
     */
    public function __construct()
    {
        // @todo catch parser exception
        $this->parser = \TYPO3\CMS\Extensionmanager\Utility\Parser\XmlParserFactory::getParserInstance('mirror');
        if (is_object($this->parser)) {
            $this->parser->attach($this);
        } else {
            throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(get_class($this) . ': No XML parser available.', 1342640390);
        }
    }

    /**
     * Method collects mirrors' details and returns instance of
     * \TYPO3\CMS\Extensionmanager\Domain\Model\Mirrors with retrieved details.
     *
     * @param string $localMirrorListFile absolute path to local mirror xml.gz file
     * @return \TYPO3\CMS\Extensionmanager\Domain\Model\Mirrors
     */
    public function getMirrors($localMirrorListFile)
    {
        $zlibStream = 'compress.zlib://';
        $this->parser->parseXml($zlibStream . $localMirrorListFile);
        /** @var $objRepositoryMirrors \TYPO3\CMS\Extensionmanager\Domain\Model\Mirrors */
        $objRepositoryMirrors = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extensionmanager\Domain\Model\Mirrors::class);
        $objRepositoryMirrors->setMirrors($this->arrTmpMirrors);
        $this->arrTmpMirrors = [];
        return $objRepositoryMirrors;
    }

    /**
     * Method receives an update from a subject.
     *
     * @param \SplSubject $subject a subject notifying this observer
     * @return void
     */
    public function update(\SplSubject $subject)
    {
        // @todo mirrorxml_abstract_parser
        if (is_subclass_of($subject, \TYPO3\CMS\Extensionmanager\Utility\Parser\AbstractXmlParser::class)) {
            $this->arrTmpMirrors[] = $subject->getAll();
        }
    }
}
