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

namespace TYPO3\CMS\Core\Configuration\Tca;

/**
 * @internal internal data object - only to be used within TYPO3 Core
 */
final readonly class TcaProcessingResult
{
    public function __construct(
        private array $tca,
        /** Accumulate messages, occurred on TCA processing, e.g. by TcaMigration. */
        private array $messages = []
    ) {}

    public function getTca(): array
    {
        return $this->tca;
    }

    /**
     * @return string[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function withTca(array $tca): TcaProcessingResult
    {
        return new self($tca, $this->messages);
    }

    public function withAdditionalMessages(string ...$messages): TcaProcessingResult
    {
        return new self($this->tca, array_merge($this->messages, $messages));
    }
}
