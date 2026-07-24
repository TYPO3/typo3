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

namespace TYPO3\CMS\Core\Resource\TextExtraction;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Registry for text extractors, which are registered as tagged services
 * via the #[AsTextExtractor] attribute or the 'fal.text_extractor'
 * service tag. Extractors are ordered by their tag priority, an
 * extractor with a higher priority is asked first whether it can
 * extract text from a file.
 */
class TextExtractorRegistry implements SingletonInterface
{
    /**
     * Instance cache for text extractor classes
     *
     * @var TextExtractorInterface[]|null
     */
    protected ?array $instances = null;

    /**
     * @param iterable<TextExtractorInterface> $textExtractors
     */
    public function __construct(protected readonly iterable $textExtractors = []) {}

    /**
     * @deprecated since TYPO3 v15.0, this method is a no-op and will be removed in TYPO3 v16.0. Register the text extractor as a tagged service using the #[AsTextExtractor] attribute instead.
     */
    public function registerTextExtractor(string $className): void {}

    /**
     * Get all registered text extractor instances
     *
     * @return TextExtractorInterface[]
     */
    protected function getTextExtractorInstances(): array
    {
        if ($this->instances === null) {
            $this->instances = [];
            foreach ($this->textExtractors as $textExtractor) {
                $this->instances[] = $textExtractor;
            }
        }
        return $this->instances;
    }

    /**
     * Checks whether any registered text extractor can deal with a given file
     * and returns it.
     */
    public function getTextExtractor(FileInterface $file): ?TextExtractorInterface
    {
        foreach ($this->getTextExtractorInstances() as $textExtractor) {
            if ($textExtractor->canExtractText($file)) {
                return $textExtractor;
            }
        }
        return null;
    }
}
