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
 * Regular expression filter
 */
class RegExpFilter extends AbstractFilter implements FilterInterface
{
    /**
     * Regular expression for filter
     *
     * @var bool
     */
    protected $regularExpression;

    /**
     * Constructor
     *
     * @param array $arguments Filter configuration
     */
    public function __construct(array $arguments = [])
    {
        $this->setRegularExpression($arguments['expression']);
    }

    /**
     * Set the regular expression
     *
     * @param string $expression The regular expression
     * @return void
     */
    public function setRegularExpression($expression)
    {
        $this->regularExpression = (string)$expression;
    }

    /**
     * Return filtered value
     * Remove all characters found in regular expression
     *
     * @param string $value
     * @return string
     */
    public function filter($value)
    {
        return preg_replace($this->regularExpression, '', (string)$value);
    }
}
