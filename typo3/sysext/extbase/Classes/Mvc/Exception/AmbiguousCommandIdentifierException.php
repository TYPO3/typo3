<?php
namespace TYPO3\CMS\Extbase\Mvc\Exception;

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
 * An "Ambiguous command identifier" exception
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AmbiguousCommandIdentifierException extends \TYPO3\CMS\Extbase\Mvc\Exception\CommandException
{
    /**
     * @var array<\TYPO3\CMS\Extbase\Mvc\Cli\Command>
     */
    protected $matchingCommands = [];

    /**
     * Overwrites parent constructor to be able to inject matching commands.
     *
     * @param string $message
     * @param int $code
     * @param \Exception|null $previousException
     * @param array $matchingCommands <\TYPO3\CMS\Extbase\Mvc\Cli\Command> $matchingCommands Commands that matched the command identifier
     * @see Exception
     */
    public function __construct($message = '', $code = 0, \Exception $previousException = null, array $matchingCommands)
    {
        $this->matchingCommands = $matchingCommands;
        parent::__construct($message, $code, $previousException);
    }

    /**
     * @return array<\TYPO3\CMS\Extbase\Mvc\Cli\Command>
     */
    public function getMatchingCommands()
    {
        return $this->matchingCommands;
    }
}
