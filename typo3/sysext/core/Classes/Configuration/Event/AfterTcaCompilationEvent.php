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

namespace TYPO3\CMS\Core\Configuration\Event;

/**
 * Event after $tca which later becomes $GLOBALS['TCA'] has been built.
 * Allows to further manipulate $tca before it is cached and set as $GLOBALS['TCA'].
 */
final class AfterTcaCompilationEvent
{
    public function __construct(private array $tca) {}

    public function getTca(): array
    {
        return $this->tca;
    }

    public function setTca(array $tca): void
    {
        $this->tca = $tca;
    }
}
