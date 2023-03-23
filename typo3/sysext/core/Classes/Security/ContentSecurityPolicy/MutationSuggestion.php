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
 * Representation of a mutation suggested by a handler.
 * The identifier is used to keep track of the original handling class/aspect.
 * Higher priorities take precedence when being visualized in the backend module.
 */
final class MutationSuggestion implements \JsonSerializable
{
    /**
     * @param string $identifier a unique identifier (e.g. `Vendor\Extension\MyHandler@knownJavaScript`)
     * @param ?int $priority an integer in the range of [0; 10]
     * @param ?string $label to be shown in backend module
     */
    public function __construct(
        public readonly MutationCollection $collection,
        public readonly string $identifier,
        public readonly ?int $priority = null,
        public readonly ?string $label = null,
    ) {
        if ($this->priority !== null && ($this->priority < 0 || $this->priority > 10)) {
            throw new \LogicException('Priority must be in range [0; 10]', 1679601774);
        }
        if ($this->identifier === '') {
            throw new \LogicException('Identifer cannot be empty', 1679601795);
        }
    }

    public function hash(): string
    {
        return sha1(json_encode($this->getHashProperties()));
    }

    public function hmac(): string
    {
        return GeneralUtility::hmac(json_encode($this->getHashProperties()), self::class);
    }

    public function jsonSerialize(): array
    {
        $properties = [
            'collection' => $this->collection,
            'identifier' => $this->identifier,
            'priority' => $this->priority,
            'label' => $this->label,
        ];
        $hashContent = json_encode($this->getHashProperties());
        $properties['hash'] = sha1($hashContent);
        $properties['hmac'] = GeneralUtility::hmac($hashContent, self::class);
        return $properties;
    }

    private function getHashProperties(): array
    {
        return [
            'collection' => $this->collection,
            'identifier' => $this->identifier,
        ];
    }
}
