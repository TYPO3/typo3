<?php
namespace TYPO3\CMS\Extensionmanager\Domain\Model;

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
 * Repository mirrors object for extension manager.
 * @internal This class is a specific domain model implementation and is not part of the Public TYPO3 API.
 */
class Mirrors extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * Keeps mirrors.
     *
     * @var array
     */
    protected $mirrors = [];

    /**
     * Keeps currently select mirror.
     *
     * Is array index.
     *
     * @var int
     */
    protected $currentMirror;

    /**
     * Keeps information if a mirror should
     * be randomly selected.
     *
     * @var bool
     */
    protected $isRandomSelection = true;

    /**
     * Method selects one specific mirror to be used.
     *
     * @param int $mirrorId number (>=1) of mirror or NULL for random selection
     * @see $currentMirror
     */
    public function setSelect($mirrorId = null)
    {
        if ($mirrorId === null) {
            $this->isRandomSelection = true;
        } else {
            if (is_int($mirrorId) && $mirrorId >= 1 && $mirrorId <= count($this->mirrors)) {
                $this->currentMirror = $mirrorId - 1;
            }
        }
    }

    /**
     * Method returns one mirror for use.
     *
     * Mirror has previously been selected or is chosen
     * randomly.
     *
     * @return array array of a mirror's properties or NULL in case of errors
     */
    public function getMirror()
    {
        $sumMirrors = count($this->mirrors);
        if ($sumMirrors > 0) {
            if (!is_int($this->currentMirror)) {
                $this->currentMirror = rand(0, $sumMirrors - 1);
            }
            return $this->mirrors[$this->currentMirror];
        }
        return null;
    }

    /**
     * Gets the mirror url from selected mirror
     *
     * @return string
     */
    public function getMirrorUrl()
    {
        $mirror = $this->getMirror();
        $mirrorUrl = $mirror['host'] . $mirror['path'];
        return 'https://' . $mirrorUrl;
    }

    /**
     * Method returns all available mirrors.
     *
     * @return array multidimensional array with mirrors and their properties
     * @see $mirrors, setMirrors()
     */
    public function getMirrors()
    {
        return $this->mirrors;
    }

    /**
     * Method sets available mirrors.
     *
     * @param array $mirrors multidimensional array with mirrors and their properties
     * @see $mirrors, getMirrors()
     */
    public function setMirrors(array $mirrors)
    {
        if (count($mirrors) >= 1) {
            $this->mirrors = $mirrors;
        }
    }
}
