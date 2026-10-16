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

namespace TYPO3\CMS\Form\Event;

use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Finishers\FinisherInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Listeners to this event are able to react to a successfully submitted form
 * and to modify the finishers executed for this submission.
 */
final class BeforeFinishersAreExecutedEvent
{
    /**
     * @param list<FinisherInterface> $finishers
     */
    public function __construct(
        public array $finishers,
        public readonly FormRuntime $formRuntime,
        public readonly FinisherContext $finisherContext,
    ) {}
}
