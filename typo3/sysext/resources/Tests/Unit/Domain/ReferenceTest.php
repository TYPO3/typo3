<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Resources\Domain\Reference;
use TYPO3\CMS\Resources\Domain\ResourceUri;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ReferenceTest extends UnitTestCase
{

    #[Test]
    public function itReturnsProvidedValuesViaGetters(): void
    {
        $reference = new Reference('foo', 'bar');
        $this->assertSame('foo', $reference->getType());
        $this->assertSame('bar', $reference->getIdentifier());
    }

    #[Test]
    #[DataProvider('goodReferences')]
    public function itCreatesCorrectUris($expected, $type, $identifier): void
    {
        $reference = new Reference($type, $identifier);
        self::assertSame($expected, (string)$reference->toUri());
    }

    public static function goodReferences(): array
    {
        return [
            ['t3://post.blog-example.typo3.org/15', 'post.blog-example.typo3.org', '15'],
            ['t3://post.blog-example.typo3.org/foo-bar', 'post.blog-example.typo3.org', 'foo-bar'],
            ['t3://post.blog-example.typo3.org/f8a464f6-e7e3-4bb0-ac36-528665dc4358', 'post.blog-example.typo3.org', 'f8a464f6-e7e3-4bb0-ac36-528665dc4358'],
        ];
    }

    #[Test]
    #[DataProvider('badReferences')]
    public function itDoesntCreateIncorrectUris($type, $identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Reference($type, $identifier);
    }

    public static function badReferences(): array
    {
        return [
            ['post.blog-example.typo3.org/112', '15'],
        ];
    }

    #[Test]
    #[DataProvider('goodUris')]
    public function itConstructsItselfFromCorrectUris(UriInterface $uri, string $expectedType, string $expectedIdentifier): void
    {
        $reference = Reference::fromUri($uri);
        self::assertSame($expectedType, $reference->getType());
        self::assertSame($expectedIdentifier, $reference->getIdentifier());
    }

    public static function goodUris(): array
    {
        return [
            [new ResourceUri('t3://post.blog-example.typo3.org/15'), 'post.blog-example.typo3.org', '15'],
            [new ResourceUri('t3://post.blog-example.typo3.org/foo-bar'), 'post.blog-example.typo3.org', 'foo-bar'],
            [new ResourceUri('t3://post.blog-example.typo3.org/f8a464f6-e7e3-4bb0-ac36-528665dc4358'), 'post.blog-example.typo3.org', 'f8a464f6-e7e3-4bb0-ac36-528665dc4358'],
        ];
    }
}
