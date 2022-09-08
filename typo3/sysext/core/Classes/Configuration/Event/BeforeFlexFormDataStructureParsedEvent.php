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

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Listeners to this event are able to specify a flex form data structure that
 * corresponds to a given identifier.
 *
 * Listeners should call ->setDataStructure() to set the data structure (this
 * can either be a resolved data structure string, a "FILE:" reference or a
 * fully parsed data structure as array) or ignore the event to allow other
 * listeners to set it. Do not set an empty array or string as this will
 * immediately stop event propagation!
 *
 * See the note on FlexFormTools regarding the schema of $dataStructure.
 */
final class BeforeFlexFormDataStructureParsedEvent implements StoppableEventInterface
{
    private array|string|null $dataStructure = null;

    public function __construct(
        private readonly array $identifier,
    ) {
    }

    /**
     * Returns the current data structure, which will always be `null`
     * for listeners, since the event propagation is stopped as soon as
     * a listener sets a data structure.
     */
    public function getDataStructure(): array|string|null
    {
        return $this->dataStructure ?? null;
    }

    /**
     * Allows to either set an already parsed data structure as `array`,
     * a file reference or the XML structure as `string`. Setting a data
     * structure will immediately stop propagation. Avoid setting this parameter
     * to an empty array or string as this will also stop propagation.
     */
    public function setDataStructure(array|string $dataStructure): void
    {
        $this->dataStructure = $dataStructure;
    }

    public function getIdentifier(): array
    {
        return $this->identifier;
    }

    public function isPropagationStopped(): bool
    {
        return isset($this->dataStructure);
    }
}
