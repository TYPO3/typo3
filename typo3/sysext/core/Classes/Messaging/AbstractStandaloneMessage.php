<?php
namespace TYPO3\CMS\Core\Messaging;

use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * Abstract class as base for standalone messages (error pages etc.)
 */
abstract class AbstractStandaloneMessage extends AbstractMessage
{
    /**
     * Path to the HTML template file, relative to PATH_site
     *
     * @var string
     */
    protected $htmlTemplate;

    /**
     * Default markers
     *
     * @var array
     */
    protected $defaultMarkers = [];

    /**
     * Markers in template to be filled
     *
     * @var array
     */
    protected $markers = [];

    /**
     * Constructor
     *
     * @param string $message Message
     * @param string $title Title
     * @param int $severity Severity, see class constants of AbstractMessage
     */
    public function __construct($message = '', $title = '', $severity = AbstractMessage::ERROR)
    {
        if (!empty($message)) {
            $this->setMessage($message);
        }
        $this->setTitle(!empty($title) ? $title : 'Error!');
        $this->setSeverity($severity);
    }

    /**
     * Sets the markers of the templates, which have to be replaced with the specified contents.
     * The marker array passed, will be merged with already present markers.
     *
     * @param array $markers Array containing the markers and values (e.g. ###MARKERNAME### => value)
     * @return void
     */
    public function setMarkers(array $markers)
    {
        $this->markers = array_merge($this->markers, $markers);
    }

    /**
     * Returns the default markers like title and message, which exist for every standalone message
     *
     * @return array
     */
    protected function getDefaultMarkers()
    {
        $classes = [
            self::NOTICE => 'notice',
            self::INFO => 'information',
            self::OK => 'ok',
            self::WARNING => 'warning',
            self::ERROR => 'error'
        ];
        $defaultMarkers = [
            '###CSS_CLASS###' => $classes[$this->severity],
            '###TITLE###' => $this->title,
            '###MESSAGE###' => $this->message,
            // Avoid calling TYPO3_SITE_URL here to get the base URL as it might be that we output an exception message with
            // invalid trusted host, which would lead to a nested exception! See: #30377
            // Instead we calculate the relative path to the document root without involving HTTP request parameters.
            '###BASEURL###' => substr(PATH_site, strlen(GeneralUtility::getIndpEnv('TYPO3_DOCUMENT_ROOT'))),
            '###TYPO3_mainDir###' => TYPO3_mainDir,
            '###TYPO3_copyright_year###' => TYPO3_copyright_year
        ];
        return $defaultMarkers;
    }

    /**
     * Gets the filename of the HTML template.
     *
     * @return string The filename of the HTML template.
     */
    public function getHtmlTemplate()
    {
        if (!$this->htmlTemplate) {
            throw new \RuntimeException('No HTML template file has been defined, yet', 1314390127);
        }
        return $this->htmlTemplate;
    }

    /**
     * Sets the filename to the HTML template
     *
     * @param string $htmlTemplate The filename of the HTML template, relative to PATH_site
     * @return void
     */
    public function setHtmlTemplate($htmlTemplate)
    {
        $this->htmlTemplate = PATH_site . $htmlTemplate;
        if (!file_exists($this->htmlTemplate)) {
            throw new \RuntimeException('Template file "' . $this->htmlTemplate . '" not found', 1312830504);
        }
    }

    /**
     * Renders the message.
     *
     * @return string The message as HTML.
     */
    public function render()
    {
        $markers = array_merge($this->getDefaultMarkers(), $this->markers);
        $content = GeneralUtility::getUrl($this->htmlTemplate);
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $content = $templateService->substituteMarkerArray($content, $markers, '', false, true);
        return $content;
    }

    /**
     * Renders the message and echoes it.
     *
     * @return void
     */
    public function output()
    {
        $content = $this->render();
        echo $content;
    }
}
