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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Representation of a Content-Security-Policy mutation, changing an existing policy directive.
 */
class Mutation implements \JsonSerializable
{
    /**
     * @var list<SourceInterface>
     */
    public readonly array $sources;

    public function __construct(
        public readonly MutationMode $mode,
        public readonly Directive $directive,
        SourceInterface ...$sources,
    ) {
        // @todo continue with source collecting internally?
        if ($sources !== [] && $this->mode === MutationMode::Remove) {
            throw new \LogicException(
                'Cannot remove and declare sources at the same time',
                1677244893
            );
        }
        $this->sources = $sources;
    }

    public function jsonSerialize(): array
    {
        $service = GeneralUtility::makeInstance(ModelService::class);
        return [
            'mode' => $this->mode,
            'directive' => $this->directive,
            'sources' => $service->serializeSources(...$this->sources),
        ];
    }
}
