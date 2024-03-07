<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Definition\Metadata;

final class Group implements GroupInterface
{

    public function __construct(private readonly string $fqn)
    {
    }

    public function getFQN(): string
    {
        return $this->fqn;
    }
}
