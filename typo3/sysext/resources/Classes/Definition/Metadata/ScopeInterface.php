<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Definition\Metadata;

interface ScopeInterface
{

    public const SCOPE_GLOBAL = 'global.scopes.typo3.org';

    public function getFQN(): string;

}
