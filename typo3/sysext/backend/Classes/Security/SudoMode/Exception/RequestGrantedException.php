<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Security\SudoMode\Exception;

use TYPO3\CMS\Backend\Security\SudoMode\Access\ServerRequestInstruction;

/**
 * Exception that signals that the current user must verify the access for a
 * particular resource, route, module, etc. by entering their password again.
 *
 * @internal
 */
final class RequestGrantedException extends \RuntimeException
{
    protected ServerRequestInstruction $instruction;

    public function withInstruction(ServerRequestInstruction $instruction): self
    {
        $this->instruction = $instruction;
        return $this;
    }

    public function getInstruction(): ServerRequestInstruction
    {
        return $this->instruction;
    }
}
