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
 * Trim filter
 */
class TrimFilter extends AbstractFilter implements FilterInterface
{
    /**
     * Characters used by trim filter
     *
     * @var string
     */
    protected $characterList;

    /**
     * Constructor
     *
     * @param array $arguments Filter configuration
     */
    public function __construct(array $arguments = [])
    {
        $this->setCharacterList($arguments['characterList']);
    }

    /**
     * Set the characters that need to be stripped from the
     * beginning or the end of the input,
     * in addition to the default trim characters
     *
     * @param string $characterList
     * @return void
     */
    public function setCharacterList($characterList)
    {
        $this->characterList = $characterList;
    }

    /**
     * Return filtered value
     * Strip characters from the beginning and the end
     *
     * @param string $value
     * @return string
     */
    public function filter($value)
    {
        if (
            $this->characterList === null
            || $this->characterList === ''
        ) {
            return trim((string)$value);
        } else {
            return trim((string)$value, $this->characterList);
        }
    }
}
