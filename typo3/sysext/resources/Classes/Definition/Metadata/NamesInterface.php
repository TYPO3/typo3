<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Definition\Metadata;

interface NamesInterface
{

    public function getPlural(): string;

    public function getSingular(): string;

    public function getKind(): string;

    public function getShortnames(): array;

}
