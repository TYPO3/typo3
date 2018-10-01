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
     * Keeps sponsor link of currently processed mirror.
     *
     * @var string
     */
    protected $sponsorlink;

    /**
     * Keeps sponsor logo location of currently processed mirror.
     *
     * @var string
     */
    protected $sponsorlogo;

    /**
     * Keeps sponsor name of currently processed mirror.
     *
     * @var string
     */
    protected $sponsorname;

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
     * country, host, path, sponsorlink, sponsorlogo, sponsorname, title
     *
     * @return array assoziative array of a mirror's properties
     * @see $country, $host, $path, $sponsorlink, $sponsorlogo, $sponsorname, $title
     */
    public function getAll()
    {
        $mirrorProperties = [];
        $mirrorProperties['title'] = $this->title;
        $mirrorProperties['host'] = $this->host;
        $mirrorProperties['path'] = $this->path;
        $mirrorProperties['country'] = $this->country;
        $mirrorProperties['sponsorname'] = $this->sponsorname;
        $mirrorProperties['sponsorlink'] = $this->sponsorlink;
        $mirrorProperties['sponsorlogo'] = $this->sponsorlogo;
        return $mirrorProperties;
    }

    /**
     * Returns country of currently processed mirror.
     *
     * @return string name of country a mirror is located in
     * @see $country, getAll()
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Returns host of currently processed mirror.
     *
     * @return string host name
     * @see $host, getAll()
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Returns path to mirrored TER of currently processed mirror.
     *
     * @return string path name
     * @see $path, getAll()
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns sponsor link of currently processed mirror.
     *
     * @return string URL of a sponsor's website
     * @see $sponsorlink, getAll()
     */
    public function getSponsorlink()
    {
        return $this->sponsorlink;
    }

    /**
     * Returns sponsor logo location of currently processed mirror.
     *
     * @return string a sponsor's logo location
     * @see $sponsorlogo, getAll()
     */
    public function getSponsorlogo()
    {
        return $this->sponsorlogo;
    }

    /**
     * Returns sponsor name of currently processed mirror.
     *
     * @return string name of sponsor
     * @see $sponsorname, getAll()
     */
    public function getSponsorname()
    {
        return $this->sponsorname;
    }

    /**
     * Returns title of currently processed mirror.
     *
     * @return string title of mirror
     * @see $title, getAll()
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Method resets version class properties.
     *
     * @see $country, $host, $path, $sponsorlink, $sponsorlogo, $sponsorname, $title
     */
    protected function resetProperties()
    {
        $this->title = $this->host = $this->path = $this->country
            = $this->sponsorname = $this->sponsorlink = $this->sponsorlogo = null;
    }
}
