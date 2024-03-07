<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Definition\Metadata;

use TYPO3\CMS\Resources\ResourceInterface;

interface VersionInterface extends ResourceInterface
{
    public function getName(): string;

    /**
     * Whether the version is served via the API or internal only.
     */
    public function isServed(): bool;

    public function getControllerServiceId(): string;
}
