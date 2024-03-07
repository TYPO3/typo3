<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Definition\Metadata;

use TYPO3\CMS\Resources\Definition\Metadata\Exception\UnsupportedOperationException;
use Webmozart\Assert\Assert;

final class VersionCollection extends \ArrayObject
{

    public function __construct(array $versions = [])
    {
        Assert::allImplementsInterface($versions, VersionInterface::class);
        $versions = array_combine(
          array_map([$this, 'mapVersionKey'], $versions),
          $versions
        );
        parent::__construct($this->sortElements($versions));
    }

    /**
     * Remove a leading v, so version_compare can do it's job.
     */
    private function mapVersionKey(VersionInterface $version): string
    {
        $versionName = $version->getName();
        return isset($versionName[0]) && $versionName[0] === 'v' ? substr($versionName, 1) : $versionName;
    }

    private function sortElements(array $elements): array
    {
        uksort($elements, 'version_compare');
        return $elements;
    }

    public function offsetSet(mixed $key, mixed $value): void
    {
        throw new UnsupportedOperationException('This is a read-only collection.', 1652705869);
    }

    // TODO: check offsetUnset, append and sort methods for mutation

}
