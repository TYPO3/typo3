<?php
namespace TYPO3\CMS\Form\Domain\Filter;

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
 * Alphabetic filter
 */
class AlphabeticFilter extends AbstractFilter implements FilterInterface
{
    /**
     * Allow whitespace
     *
     * @var bool
     */
    protected $allowWhiteSpace;

    /**
     * Constructor
     *
     * @param array $arguments Filter configuration
     */
    public function __construct($arguments = [])
    {
        $this->setAllowWhiteSpace($arguments['allowWhiteSpace']);
    }

    /**
     * Allow white space in the submitted value
     *
     * @param bool $allowWhiteSpace True if allowed
     * @return void
     */
    public function setAllowWhiteSpace($allowWhiteSpace = true)
    {
        $this->allowWhiteSpace = (bool)$allowWhiteSpace;
    }

    /**
     * Return filtered value
     * Remove all but alphabetic characters
     * Allow whitespace by choice
     *
     * @param string $value
     * @return string
     */
    public function filter($value)
    {
        $whiteSpace = $this->allowWhiteSpace ? '\\s' : '';
        $pattern = '/[^\pL' . $whiteSpace . ']/u';
        return preg_replace($pattern, '', (string)$value);
    }
}
