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

/**
 * Listeners are able to modify the options, used by the EmailFinisher. Possible
 * use cases might be unsetting recipients or to changing the subject of the mail.
 */
final class BeforeEmailFinisherInitializedEvent
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        protected readonly FinisherContext $finisherContext,
        protected array $options
    ) {}

    public function getFinisherContext(): FinisherContext
    {
        return $this->finisherContext;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }
}
