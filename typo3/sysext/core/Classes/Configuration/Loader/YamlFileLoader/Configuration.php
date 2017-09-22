<?php
declare(strict_types=1);
namespace TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;

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
 * Configuration for YAML file loading
 */
class Configuration
{
    /**
     * @var bool
     */
    protected $processImports = true;

    /**
     * @var bool
     */
    protected $removeImportsProperty = true;

    /**
     * @var bool
     */
    protected $mergeLists = true;

    /**
     * @var bool
     */
    protected $processPlaceholders = true;

    /**
     * @return bool
     */
    public function getProcessImports(): bool
    {
        return $this->processImports;
    }

    /**
     * @param bool $processImports
     * @return Configuration
     */
    public function setProcessImports(bool $processImports): self
    {
        $this->processImports = $processImports;
        return $this;
    }

    /**
     * @return bool
     */
    public function getRemoveImportsProperty(): bool
    {
        return $this->removeImportsProperty;
    }

    /**
     * @param bool $removeImportsProperty
     * @return Configuration
     */
    public function setRemoveImportsProperty(bool $removeImportsProperty): self
    {
        $this->removeImportsProperty = $removeImportsProperty;
        return $this;
    }

    /**
     * @return bool
     */
    public function getProcessPlaceholders(): bool
    {
        return $this->processPlaceholders;
    }

    /**
     * @param bool $processPlaceholders
     * @return Configuration
     */
    public function setProcessPlaceholders(bool $processPlaceholders): self
    {
        $this->processPlaceholders = $processPlaceholders;
        return $this;
    }

    /**
     * @return bool
     */
    public function getMergeLists(): bool
    {
        return $this->mergeLists;
    }

    /**
     * @param bool $mergeLists
     * @return Configuration
     */
    public function setMergeLists(bool $mergeLists): self
    {
        $this->mergeLists = $mergeLists;
        return $this;
    }
}
