<?php
namespace TYPO3\CMS\Extbase\Mvc\Cli;

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
 * Represents a CommandArgumentDefinition
 *
 * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Use symfony/console commands instead.
 */
class CommandArgumentDefinition
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var bool
     */
    protected $required = false;

    /**
     * @var string
     */
    protected $description = '';

    /**
     * Constructor
     *
     * @param string $name name of the command argument (= parameter name)
     * @param bool $required defines whether this argument is required or optional
     * @param string $description description of the argument
     */
    public function __construct($name, $required, $description)
    {
        $this->name = $name;
        $this->required = $required;
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the lowercased name with dashes as word separator
     *
     * @return string
     */
    public function getDashedName()
    {
        $dashedName = ucfirst($this->name);
        $dashedName = preg_replace('/([A-Z][a-z0-9]+)/', '$1-', $dashedName);
        return '--' . strtolower(substr($dashedName, 0, -1));
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function isRequired()
    {
        return $this->required;
    }
}
