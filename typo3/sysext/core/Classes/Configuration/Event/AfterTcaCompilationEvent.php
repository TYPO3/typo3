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
 * Event after $GLOBALS['TCA'] is built to allow to further manipulate $tca.
 *
 * Side note: It is possible to check against the original TCA as this is stored within $GLOBALS['TCA']
 * before this event is fired.
 */
final class AfterTcaCompilationEvent
{
    /**
     * @var array
     */
    private $tca;

    public function __construct(array $tca)
    {
        $this->tca = $tca;
    }

    public function getTca(): array
    {
        return $this->tca;
    }

    public function setTca(array $tca)
    {
        $this->tca = $tca;
    }
}
