<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Definition\Metadata;

use Webmozart\Assert\Assert;

final class Names implements NamesInterface
{

    private readonly array $shortnames;

    /**
     * @param string[] $shortnames
     */
    public function __construct(
        private readonly string $plural,
        private readonly string $singular,
        private readonly string $kind,
        array $shortnames = [],
    )
    {
        Assert::allString($shortnames);
        $this->shortnames = $shortnames;
    }

    public function getPlural(): string
    {
        return $this->plural;
    }

    public function getSingular(): string
    {
        return $this->singular;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    /**
     * @return string[]
     */
    public function getShortnames(): array
    {
        return $this->shortnames;
    }
}
