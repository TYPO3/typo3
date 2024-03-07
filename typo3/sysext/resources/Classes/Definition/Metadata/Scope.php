<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Definition\Metadata;

final readonly class Scope implements ScopeInterface
{

    public function __construct(private string $fqn)
    {
    }

    public function getFQN(): string
    {
        return $this->fqn;
    }

}
