<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Resources\Domain\NamespacedReference;
use TYPO3\CMS\Resources\Domain\Reference;
use TYPO3\CMS\Resources\Domain\ResourceUri;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class NamespacedReferenceTest extends UnitTestCase
{

    #[Test]
    public function itReturnsProvidedValuesViaGetters(): void
    {
        $namespace = new Reference('foo', 'bar');
        $reference = new NamespacedReference($namespace, 'faz', 'baz');
        $this->assertSame($namespace, $reference->getNamespace());
        $this->assertSame('faz', $reference->getType());
        $this->assertSame('baz', $reference->getIdentifier());
    }

    #[Test]
    #[DataProvider('goodReferences')]
    public function itCreatesCorrectUris(string $expected, string $namespaceType, string $namespaceIdentifier, string $type, string $identifier): void
    {
        $reference = new NamespacedReference(new Reference($namespaceType, $namespaceIdentifier), $type, $identifier);
        self::assertSame($expected, (string)$reference->toUri());
    }

    public static function goodReferences(): array
    {
        return [
            ['t3://site.core.typo3.org/test-site/post.blog-example.typo3.org/foo-bar', 'site.core.typo3.org', 'test-site', 'post.blog-example.typo3.org', 'foo-bar'],
            ['t3://site.core.typo3.org/test-site/post.blog-example.typo3.org/foo-bar', 'site.core.typo3.org', 'test-site', 'post.blog-example.typo3.org', 'foo-bar'],
            ['t3://site.core.typo3.org/test-site/post.blog-example.typo3.org/f8a464f6-e7e3-4bb0-ac36-528665dc4358', 'site.core.typo3.org', 'test-site', 'post.blog-example.typo3.org', 'f8a464f6-e7e3-4bb0-ac36-528665dc4358'],
        ];
    }

    #[Test]
    #[DataProvider('badReferences')]
    public function itDoesntCreateIncorrectUris(string $namespaceType, string $namespaceIdentifier, string $type, string $identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NamespacedReference(new Reference($namespaceType, $namespaceIdentifier), $type, $identifier);
    }

    public static function badReferences(): array
    {
        return [
            ['site.core.typo3.org', 'test-site', 'post.blog-example.typo3.org/112', '15'],
        ];
    }

    #[Test]
    #[DataProvider('goodUris')]
    public function itConstructsItselfFromCorrectUris(UriInterface $uri, Reference $expectedNamespace, string $expectedType, string $expectedIdentifier): void
    {
        $reference = NamespacedReference::fromUri($uri);
        self::assertSame($expectedType, $reference->getType());
        self::assertSame($expectedIdentifier, $reference->getIdentifier());
    }

    public static function goodUris(): array
    {
        $expectedNamespace = new Reference('faz', 'baz');
        return [
            [new ResourceUri('t3://site.core.typo3.org/test-site/post.blog-example.typo3.org/15'), $expectedNamespace, 'post.blog-example.typo3.org', '15'],
            [new ResourceUri('t3://site.core.typo3.org/test-site/post.blog-example.typo3.org/foo-bar'), $expectedNamespace, 'post.blog-example.typo3.org', 'foo-bar'],
            [new ResourceUri('t3://site.core.typo3.org/test-site/post.blog-example.typo3.org/f8a464f6-e7e3-4bb0-ac36-528665dc4358'), $expectedNamespace, 'post.blog-example.typo3.org', 'f8a464f6-e7e3-4bb0-ac36-528665dc4358'],
        ];
    }

}
