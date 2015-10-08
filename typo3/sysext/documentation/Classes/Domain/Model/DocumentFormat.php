<?php
namespace TYPO3\CMS\Documentation\Domain\Model;

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
 * An extension helper model to be used in ext:documentation context
 *
 * @entity
 */
class DocumentFormat extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * format
     *
     * @var string
     * @validate NotEmpty
     */
    protected $format;

    /**
     * path
     *
     * @var string
     * @validate NotEmpty
     */
    protected $path;

    /**
     * Returns the format.
     *
     * @return string $format
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Sets the format.
     *
     * @param string $format
     * @return DocumentFormat
     */
    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * Returns the path.
     *
     * @return string $path
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Sets the path.
     *
     * @param string $path
     * @return DocumentFormat
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }
}
