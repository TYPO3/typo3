<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extensionmanager\Parser;

use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;

/**
 * Parser for TYPO3's extension.xml file.
 *
 * Depends on PHP ext/xml which is a required composer php extension
 * and enabled in PHP by default since a long time.
 *
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
class ExtensionXmlParser implements \SplSubject
{
    /**
     * Keeps list of attached observers.
     *
     * @var \SplObserver[]
     */
    protected array $observers = [];

    /**
     * Keeps current data of element to process.
     */
    protected string $elementData = '';

    /**
     * Parsed property data
     */
    protected string $authorcompany = '';
    protected string $authoremail = '';
    protected string $authorname = '';
    protected string $category = '';
    protected string $dependencies = '';
    protected string $description = '';
    protected int $extensionDownloadCounter = 0;
    protected string $extensionKey = '';
    protected int $lastuploaddate = 0;
    protected string $ownerusername = '';
    protected int $reviewstate = 0;
    protected string $state = '';
    protected string $t3xfilemd5 = '';
    protected string $title = '';
    protected string $uploadcomment = '';
    protected string $version = '';
    protected int $versionDownloadCounter = 0;
    protected string $documentationLink = '';
    protected string $distributionImage = '';
    protected string $distributionWelcomeImage = '';

    public function __construct()
    {
        if (!extension_loaded('xml')) {
            throw new \RuntimeException('PHP extension "xml" not loaded', 1622148496);
        }
    }

    /**
     * Method parses an extensions.xml file.
     *
     * @param string $file GZIP stream resource
     * @throws ExtensionManagerException in case of parse errors
     */
    public function parseXml($file): void
    {
        if (PHP_MAJOR_VERSION < 8) {
            // @deprecated will be removed as soon as the minimum version of TYPO3 is 8.0
            $this->parseWithLegacyResource($file);
            return;
        }

        /** @var \XMLParser $parser */
        $parser = xml_parser_create();
        xml_set_object($parser, $this);

        // keep original character case of XML document
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'utf-8');
        xml_set_element_handler($parser, [$this, 'startElement'], [$this, 'endElement']);
        xml_set_character_data_handler($parser, [$this, 'characterData']);
        if (!($fp = fopen($file, 'r'))) {
            throw $this->createUnableToOpenFileResourceException($file);
        }
        while ($data = fread($fp, 4096)) {
            if (!xml_parse($parser, $data, feof($fp))) {
                throw $this->createXmlErrorException($parser, $file);
            }
        }
        xml_parser_free($parser);
    }

    /**
     * @throws ExtensionManagerException
     * @internal
     */
    private function parseWithLegacyResource(string $file): void
    {
        // Store the xml parser resource in when run with PHP <= 7.4
        // @deprecated will be removed as soon as the minimum version of TYPO3 is 8.0
        $legacyXmlParserResource = xml_parser_create();
        xml_set_object($legacyXmlParserResource, $this);
        if ($legacyXmlParserResource === null) {
            throw new ExtensionManagerException('Unable to create XML parser.', 1342640663);
        }
        $parser = $legacyXmlParserResource;

        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
        $previousValueOfEntityLoader = libxml_disable_entity_loader(true);

        // keep original character case of XML document
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'utf-8');
        xml_set_element_handler($parser, [$this, 'startElement'], [$this, 'endElement']);
        xml_set_character_data_handler($parser, [$this, 'characterData']);
        if (!($fp = fopen($file, 'r'))) {
            throw $this->createUnableToOpenFileResourceException($file);
        }
        while ($data = fread($fp, 4096)) {
            if (!xml_parse($parser, $data, feof($fp))) {
                throw $this->createXmlErrorException($parser, $file);
            }
        }

        libxml_disable_entity_loader($previousValueOfEntityLoader);

        xml_parser_free($parser);
    }

    private function createUnableToOpenFileResourceException(string $file): ExtensionManagerException
    {
        return new ExtensionManagerException(sprintf('Unable to open file resource %s.', $file), 1342640689);
    }

    private function createXmlErrorException($parser, string $file): ExtensionManagerException
    {
        return new ExtensionManagerException(
            sprintf(
                'XML error %s in line %u of file resource %s.',
                xml_error_string(xml_get_error_code($parser)),
                xml_get_current_line_number($parser),
                $file
            ),
            1342640703
        );
    }

    /**
     * Method is invoked when parser accesses start tag of an element.
     *
     * @param resource $parser parser resource
     * @param string $elementName element name at parser's current position
     * @param array $attrs array of an element's attributes if available
     */
    protected function startElement($parser, $elementName, $attrs)
    {
        switch ($elementName) {
            case 'extension':
                $this->extensionKey = $attrs['extensionkey'];
                break;
            case 'version':
                $this->version = $attrs['version'];
                break;
            default:
                $this->elementData = '';
        }
    }

    /**
     * Method is invoked when parser accesses end tag of an element.
     *
     * @param resource $parser parser resource
     * @param string $elementName Element name at parser's current position
     */
    protected function endElement($parser, $elementName)
    {
        switch ($elementName) {
            case 'extension':
                $this->resetProperties(true);
                break;
            case 'version':
                $this->notify();
                $this->resetProperties();
                break;
            case 'downloadcounter':
                // downloadcounter can be a child node of extension or version
                if ($this->version === '') {
                    $this->extensionDownloadCounter = (int)$this->elementData;
                } else {
                    $this->versionDownloadCounter = (int)$this->elementData;
                }
                break;
            case 'title':
                $this->title = $this->elementData;
                break;
            case 'description':
                $this->description = $this->elementData;
                break;
            case 'state':
                $this->state = $this->elementData;
                break;
            case 'reviewstate':
                $this->reviewstate = (int)$this->elementData;
                break;
            case 'category':
                $this->category = $this->elementData;
                break;
            case 'lastuploaddate':
                $this->lastuploaddate = (int)$this->elementData;
                break;
            case 'uploadcomment':
                $this->uploadcomment = $this->elementData;
                break;
            case 'dependencies':
                $newDependencies = [];
                $dependenciesArray = unserialize($this->elementData, ['allowed_classes' => false]);
                if (is_array($dependenciesArray)) {
                    foreach ($dependenciesArray as $version) {
                        if (!empty($version['kind']) && !empty($version['extensionKey'])) {
                            $newDependencies[$version['kind']][$version['extensionKey']] = $version['versionRange'];
                        }
                    }
                }
                $this->dependencies = serialize($newDependencies);
                break;
            case 'authorname':
                $this->authorname = $this->elementData;
                break;
            case 'authoremail':
                $this->authoremail = $this->elementData;
                break;
            case 'authorcompany':
                $this->authorcompany = $this->elementData;
                break;
            case 'ownerusername':
                $this->ownerusername = $this->elementData;
                break;
            case 't3xfilemd5':
                $this->t3xfilemd5 = $this->elementData;
                break;
            case 'documentation_link':
                $this->documentationLink = $this->elementData;
                break;
            case 'distributionImage':
                if (preg_match('/^https:\/\/extensions\.typo3\.org[a-zA-Z0-9._\/]+Distribution\.png$/', $this->elementData)) {
                    $this->distributionImage = $this->elementData;
                }
                break;
            case 'distributionImageWelcome':
                if (preg_match('/^https:\/\/extensions\.typo3\.org[a-zA-Z0-9._\/]+DistributionWelcome\.png$/', $this->elementData)) {
                    $this->distributionWelcomeImage = $this->elementData;
                }
                break;
        }
    }

    /**
     * Method resets version class properties.
     *
     * @param bool $resetAll If TRUE, additionally extension properties are reset
     */
    protected function resetProperties($resetAll = false): void
    {
        // Resetting at least class property "version" is mandatory as we need to do some magic in
        // regards to an extension's and version's child node "downloadcounter"
        $this->version = $this->authorcompany = $this->authorname = $this->authoremail = $this->category = $this->dependencies = $this->state = '';
        $this->description = $this->ownerusername = $this->t3xfilemd5 = $this->title = $this->uploadcomment = $this->documentationLink = $this->distributionImage = $this->distributionWelcomeImage = '';
        $this->lastuploaddate = $this->reviewstate = $this->versionDownloadCounter = 0;
        if ($resetAll) {
            $this->extensionKey = '';
            $this->extensionDownloadCounter = 0;
        }
    }

    /**
     * Method is invoked when parser accesses any character other than elements.
     *
     * @param resource|\XmlParser $parser XmlParser with PHP >= 8
     * @param string $data An element's value
     */
    protected function characterData($parser, string $data)
    {
        $this->elementData .= $data;
    }

    /**
     * Method attaches an observer.
     *
     * @param \SplObserver $observer an observer to attach
     * @see detach()
     * @see notify()
     */
    public function attach(\SplObserver $observer): void
    {
        $this->observers[] = $observer;
    }

    /**
     * Method detaches an attached observer
     *
     * @param \SplObserver $observer an observer to detach
     */
    public function detach(\SplObserver $observer): void
    {
        $key = array_search($observer, $this->observers, true);
        if ($key !== false) {
            unset($this->observers[$key]);
        }
    }

    /**
     * Method notifies attached observers.
     */
    public function notify(): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }

    /**
     * Returns download number sum of all extension's versions.
     */
    public function getAlldownloadcounter(): int
    {
        return $this->extensionDownloadCounter;
    }

    /**
     * Returns company name of extension author.
     */
    public function getAuthorcompany(): string
    {
        return $this->authorcompany;
    }

    /**
     * Returns e-mail address of extension author.
     */
    public function getAuthoremail(): string
    {
        return $this->authoremail;
    }

    /**
     * Returns name of extension author.
     */
    public function getAuthorname(): string
    {
        return $this->authorname;
    }

    /**
     * Returns category of an extension.
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * Returns dependencies of an extension's version as a serialized string
     */
    public function getDependencies(): string
    {
        return $this->dependencies;
    }

    /**
     * Returns description of an extension's version.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Returns download number of an extension's version.
     */
    public function getDownloadcounter(): int
    {
        return $this->versionDownloadCounter;
    }

    /**
     * Returns key of an extension.
     */
    public function getExtkey(): string
    {
        return $this->extensionKey;
    }

    /**
     * Returns last uploaddate of an extension's version.
     */
    public function getLastuploaddate(): int
    {
        return $this->lastuploaddate;
    }

    /**
     * Returns username of extension owner.
     */
    public function getOwnerusername(): string
    {
        return $this->ownerusername;
    }

    /**
     * Returns review state of an extension's version.
     */
    public function getReviewstate(): int
    {
        return $this->reviewstate;
    }

    /**
     * Returns state of an extension's version.
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Returns t3x file hash of an extension's version.
     */
    public function getT3xfilemd5(): string
    {
        return $this->t3xfilemd5;
    }

    /**
     * Returns title of an extension's version.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Returns extension upload comment.
     */
    public function getUploadcomment(): string
    {
        return $this->uploadcomment;
    }

    /**
     * Returns version number as unparsed string.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Whether the current version number is valid
     */
    public function isValidVersionNumber(): bool
    {
        // Validate the version number, see `isValidVersionNumber` in TER API
        return (bool)preg_match('/^(0|[1-9]\d{0,2})\.(0|[1-9]\d{0,2})\.(0|[1-9]\d{0,2})$/', $this->version);
    }

    /**
     * Returns documentation link.
     */
    public function getDocumentationLink(): string
    {
        return $this->documentationLink;
    }

    /**
     * Returns distribution image url.
     */
    public function getDistributionImage(): string
    {
        return $this->distributionImage;
    }

    /**
     * Returns distribution welcome image url.
     */
    public function getDistributionWelcomeImage(): string
    {
        return $this->distributionWelcomeImage;
    }
}
