<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Seo\XmlSitemap;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Interface for XmlSitemapDataProviders containing the methods that are called by the XmlSitemapRenderer
 */
interface XmlSitemapDataProviderInterface
{
    public function __construct(ServerRequestInterface $request, string $name, array $config = [], ContentObjectRenderer $cObj = null);
    public function getKey(): string;
    public function getItems(): array;
    public function getLastModified(): int;
}
