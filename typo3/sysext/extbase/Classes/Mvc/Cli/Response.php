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
 * A CLI specific response implementation
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Response extends \TYPO3\CMS\Extbase\Mvc\Response
{
    /**
     * @var int
     */
    private $exitCode = 0;

    /**
     * Sets the numerical exit code which should be returned when exiting this application.
     *
     * @param int $exitCode
     * @throws \InvalidArgumentException
     * @return void
     * @api
     */
    public function setExitCode($exitCode)
    {
        if (!is_int($exitCode)) {
            throw new \InvalidArgumentException(sprintf('Tried to set invalid exit code. The value must be integer, %s given.', gettype($exitCode)), 1312222064);
        }
        $this->exitCode = $exitCode;
    }

    /**
     * Rets the numerical exit code which should be returned when exiting this application.
     *
     * @return int
     * @api
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }

    /**
     * Renders and sends the whole web response
     *
     * @return void
     * @api
     */
    public function send()
    {
        if ($this->content !== null) {
            echo $this->shutdown();
        }
    }
}
