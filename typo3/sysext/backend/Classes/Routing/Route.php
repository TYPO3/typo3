<?php

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

namespace TYPO3\CMS\Backend\Routing;

/**
 * This is a single entity for a Route.
 *
 * The architecture is highly inspired by the Symfony Routing Component.
 */
class Route
{
    /**
     * @var string
     */
    protected $path = '/';

    protected array $methods = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * Constructor setting up the required path and options
     *
     * @param string $path The path pattern to match
     * @param array $options An array of options
     */
    public function __construct($path, $options)
    {
        $this->setPath($path)->setOptions($options);
    }

    /**
     * Returns the path
     *
     * @return string The path pattern
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Sets the pattern for the path
     * A pattern must start with a slash and must not have multiple slashes at the beginning because the
     * generated path for this route would be confused with a network path, e.g. '//domain.com/path'.
     *
     * This method implements a fluent interface.
     *
     * @param string $pattern The path pattern
     * @return Route The current Route instance
     */
    public function setPath($pattern)
    {
        $this->path = '/' . ltrim(trim($pattern), '/');
        return $this;
    }

    /**
     * Returns the uppercased HTTP methods this route is restricted to.
     * An empty array means that any method is allowed.
     *
     * @return string[] The methods
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Sets the HTTP methods (e.g. ['POST']) this route is restricted to.
     * An empty array means that any method is allowed.
     *
     * This method implements a fluent interface.
     *
     * @param string[] $methods The array of allowed methods
     * @return self
     */
    public function setMethods(array $methods): self
    {
        $this->methods = array_map('strtoupper', $methods);
        return $this;
    }

    /**
     * Returns the options set
     *
     * @return array The options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Sets the options
     *
     * This method implements a fluent interface.
     *
     * @param array $options The options
     * @return Route The current Route instance
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Sets an option value
     *
     * This method implements a fluent interface.
     *
     * @param string $name An option name
     * @param mixed $value The option value
     * @return Route The current Route instance
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
        return $this;
    }

    /**
     * Get an option value
     *
     * @param string $name An option name
     * @return mixed The option value or NULL when not given
     */
    public function getOption($name)
    {
        return $this->options[$name] ?? null;
    }

    /**
     * Checks if an option has been set
     *
     * @param string $name An option name
     * @return bool TRUE if the option is set, FALSE otherwise
     */
    public function hasOption($name)
    {
        return array_key_exists($name, $this->options);
    }
}
