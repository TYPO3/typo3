<?php
namespace TYPO3\CMS\Extensionmanager\Utility\Parser;

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
 * Abstract parser for TYPO3's mirror.xml file.
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
abstract class AbstractMirrorXmlParser extends AbstractXmlParser
{
    /**
     * Keeps country of currently processed mirror.
     *
     * @var string
     */
    protected $country;

    /**
     * Keeps hostname of currently processed mirror.
     *
     * @var string
     */
    protected $host;

    /**
     * Keeps path to mirrored TER of currently processed mirror.
     *
     * @var string
     */
    protected $path;

    /**
     * Keeps title of currently processed mirror.
     *
     * @var string
     */
    protected $title;

    /**
     * Returns an associative array of all mirror properties.
     *
     * Valid array keys of returned array are:
     * country, host, path, title
     *
     * @return array associative array of a mirror's properties
     */
    public function getAll()
    {
        $mirrorProperties = [];
        $mirrorProperties['title'] = $this->title;
        $mirrorProperties['host'] = $this->host;
        $mirrorProperties['path'] = $this->path;
        $mirrorProperties['country'] = $this->country;
        return $mirrorProperties;
    }

    /**
     * Returns country of currently processed mirror.
     *
     * @return string name of country a mirror is located in
     * @see getAll()
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Returns host of currently processed mirror.
     *
     * @return string host name
     * @see getAll()
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Returns path to mirrored TER of currently processed mirror.
     *
     * @return string path name
     * @see getAll()
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns title of currently processed mirror.
     *
     * @return string title of mirror
     * @see getAll()
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Method resets version class properties.
     */
    protected function resetProperties()
    {
        $this->title = $this->host = $this->path = $this->country = null;
    }
}
