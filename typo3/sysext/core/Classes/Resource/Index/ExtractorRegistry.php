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

namespace TYPO3\CMS\Core\Resource\Index;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Registry for MetaData extraction Services
 */
#[Autoconfigure(public: true)]
readonly class ExtractorRegistry
{
    public function __construct(
        #[AutowireLocator('metadata.extractor')]
        private ServiceLocator $extractors
    ) {}

    /**
     * Get all registered extractor instances.
     *
     * @return ExtractorInterface[]
     */
    public function getExtractors(): array
    {
        // @todo Isn't there an option to get the services ordered automatically by the ServiceLocator?
        $extractors = [];
        foreach ($this->extractors as $extractor) {
            $extractors[] = $extractor;
        }
        usort($extractors, [$this, 'compareExtractorPriority']);
        return $extractors;

    }

    /**
     * Get Extractors which work for a specific driver.
     *
     * @return ExtractorInterface[]
     */
    public function getExtractorsWithDriverSupport(string $driverType): array
    {
        return array_filter(
            $this->getExtractors(),
            function (ExtractorInterface $extractor) use ($driverType): bool {
                return empty($extractor->getDriverRestrictions())
                    || in_array($driverType, $extractor->getDriverRestrictions(), true);
            }
        );
    }

    /**
     * @deprecated no-op. Will be removed with TYPO3 v15, classes implementing ExtractorInterface are registered automatically.
     */
    public function registerExtractionService(): void
    {
        trigger_error(
            'ExtractorRegistry::registerExtractionService has been deprecated in TYPO3 v14 and will be removed in TYPO3 v15. Implementing the ExtractorInterface automatically registers the class.',
            E_USER_DEPRECATED
        );
    }

    /**
     * Compare the priority of two Extractor classes.
     * Is used for sorting array of Extractor instances by priority.
     * We want the result to be ordered from high to low so a higher
     * priority comes before a lower.
     *
     * @return int -1 a > b, 0 a == b, 1 a < b
     */
    private function compareExtractorPriority(ExtractorInterface $extractorA, ExtractorInterface $extractorB): int
    {
        return $extractorB->getExecutionPriority() - $extractorA->getExecutionPriority();
    }
}
