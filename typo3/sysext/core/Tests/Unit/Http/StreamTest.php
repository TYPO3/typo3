<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Tests\Unit\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class StreamTest extends UnitTestCase
{
    /**
     * Helper method to create a random directory and return the path.
     * The path will be registered for deletion upon test ending
     */
    private function getTestDirectory(): string
    {
        $path = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('root_');
        $this->testFilesToDelete[] = $path;
        GeneralUtility::mkdir_deep($path);
        return $path;
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function canBeInstantiatedWithStreamIdentifier(): void
    {
        new Stream('php://memory', 'wb+');
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function canBeInstantiatedWithStreamResource(): void
    {
        new Stream(fopen('php://memory', 'wb+'));
    }

    #[Test]
    public function isReadableReturnsFalseIfStreamIsNotReadable(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $subject = new Stream($fileName, 'w');
        self::assertFalse($subject->isReadable());
    }

    public static function isWritableDetectsTheActualStreamModeDataProvider(): \Generator
    {
        yield 'r' => ['r', false];
        yield 'w' => ['w', true];
        yield 'a' => ['w', true];
        yield 'r+' => ['r+', true];
        yield 'w+' => ['w+', true];
        yield 'a+' => ['a+', true];
        yield 'rw' => ['rw', true];
        yield 'rw+' => ['rw+', true];
    }

    #[Test]
    #[DataProvider('isWritableDetectsTheActualStreamModeDataProvider')]
    public function isWritableDetectsTheActualStreamMode(string $mode, bool $expectation): void
    {
        $subject = new Stream('php://memory', $mode);
        self::assertSame($expectation, $subject->isWritable());
    }

    #[Test]
    public function toStringRetrievesFullContentsOfStream(): void
    {
        $message = 'foo bar';
        $subject = new Stream('php://memory', 'wb+');
        $subject->write($message);
        self::assertEquals($message, (string)$subject);
    }

    #[Test]
    public function detachReturnsResource(): void
    {
        $resource = fopen('php://memory', 'wb+');
        $subject = new Stream($resource);
        self::assertSame($resource, $subject->detach());
    }

    #[Test]
    public function constructorRaisesExceptionWhenPassingInvalidStreamResource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Stream(['  THIS WILL NOT WORK  ']);
    }

    #[Test]
    public function toStringSerializationReturnsEmptyStringWhenStreamIsNotReadable(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        file_put_contents($fileName, 'FOO BAR');
        $subject = new Stream($fileName, 'w');
        self::assertEquals('', $subject->__toString());
    }

    #[Test]
    public function closeClosesResource(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $resource = fopen($fileName, 'wb+');
        $subject = new Stream($resource);
        $subject->close();
        // Testing with a variable here, otherwise the suggested assertion would be assertIsNotResource, which fails.
        $isResource = is_resource($resource);
        self::assertFalse($isResource);
    }

    #[Test]
    public function closeUnsetsResource(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $resource = fopen($fileName, 'wb+');
        $subject = new Stream($resource);
        $subject->close();
        self::assertNull($subject->detach());
    }

    #[Test]
    public function closeDoesNothingAfterDetach(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $resource = fopen($fileName, 'wb+');
        $subject = new Stream($resource);
        $detached = $subject->detach();
        $subject->close();
        self::assertIsResource($detached);
        self::assertSame($resource, $detached);
    }

    #[Test]
    public function getSizeReportsNullWhenNoResourcePresent(): void
    {
        $subject = new Stream('php://memory', 'wb+');
        $subject->detach();
        self::assertNull($subject->getSize());
    }

    #[Test]
    public function tellReportsCurrentPositionInResource(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $subject = new Stream($resource);
        fseek($resource, 2);
        self::assertEquals(2, $subject->tell());
    }

    #[Test]
    public function tellRaisesExceptionIfResourceIsDetached(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $subject = new Stream($resource);
        fseek($resource, 2);
        $subject->detach();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1436717285);
        $subject->tell();
    }

    #[Test]
    public function eofReportsFalseWhenNotAtEndOfStream(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $subject = new Stream($resource);
        fseek($resource, 2);
        self::assertFalse($subject->eof());
    }

    #[Test]
    public function eofReportsTrueWhenAtEndOfStream(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $subject = new Stream($resource);
        while (!feof($resource)) {
            fread($resource, 4096);
        }
        self::assertTrue($subject->eof());
    }

    #[Test]
    public function eofReportsTrueWhenStreamIsDetached(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $subject = new Stream($resource);
        fseek($resource, 2);
        $subject->detach();
        self::assertTrue($subject->eof());
    }

    #[Test]
    public function isSeekableReturnsTrueForReadableStreams(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $subject = new Stream($resource);
        self::assertTrue($subject->isSeekable());
    }

    #[Test]
    public function isSeekableReturnsFalseForDetachedStreams(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $subject = new Stream($resource);
        $subject->detach();
        self::assertFalse($subject->isSeekable());
    }

    #[Test]
    public function seekAdvancesToGivenOffsetOfStream(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $subject = new Stream($resource);
        $subject->seek(2);
        self::assertEquals(2, $subject->tell());
    }

    #[Test]
    public function rewindResetsToStartOfStream(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $subject = new Stream($resource);
        $subject->seek(2);
        $subject->rewind();
        self::assertEquals(0, $subject->tell());
    }

    #[Test]
    public function seekRaisesExceptionWhenStreamIsDetached(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $subject = new Stream($resource);
        $subject->detach();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1436717287);
        $subject->seek(2);
    }

    #[Test]
    public function isWritableReturnsFalseWhenStreamIsDetached(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $subject = new Stream($resource);
        $subject->detach();
        self::assertFalse($subject->isWritable());
    }

    #[Test]
    public function writeRaisesExceptionWhenStreamIsDetached(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $subject = new Stream($resource);
        $subject->detach();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1436717290);
        $subject->write('bar');
    }

    #[Test]
    public function isReadableReturnsFalseWhenStreamIsDetached(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $subject = new Stream($resource);
        $subject->detach();
        self::assertFalse($subject->isReadable());
    }

    #[Test]
    public function readRaisesExceptionWhenStreamIsDetached(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'r');
        $subject = new Stream($resource);
        $subject->detach();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1436717292);
        $subject->read(4096);
    }

    #[Test]
    public function readReturnsEmptyStringWhenAtEndOfFile(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'r');
        $subject = new Stream($resource);
        while (!feof($resource)) {
            fread($resource, 4096);
        }
        self::assertEquals('', $subject->read(4096));
    }

    #[Test]
    public function getContentsReturnsEmptyStringIfStreamIsNotReadable(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'w');
        $subject = new Stream($resource);
        self::assertEquals('', $subject->getContents());
    }

    public static function invalidResourcesDataProvider(): array
    {
        return [
            'null' => [null],
            'false' => [false],
            'true' => [true],
            'int' => [1],
            'float' => [1.1],
            'array' => [[]],
            'object' => [new \stdClass()],
        ];
    }

    #[DataProvider('invalidResourcesDataProvider')]
    #[Test]
    public function attachWithNonStringNonResourceRaisesExceptionByType($resource): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717297);
        $subject = new Stream('php://memory', 'wb+');
        $subject->attach($resource);
    }

    #[Test]
    public function attachWithNonStringNonResourceRaisesExceptionByString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717296);
        $subject = new Stream('php://memory', 'wb+');
        $subject->attach('foo-bar-baz');
    }

    #[Test]
    public function attachWithResourceAttachesResource(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $resource = fopen($fileName, 'r+');
        $subject = new Stream('php://memory', 'wb+');
        $subject->attach($resource);
        $reflection = new \ReflectionProperty($subject, 'resource');
        self::assertSame($resource, $reflection->getValue($subject));
    }

    #[Test]
    public function attachWithStringRepresentingResourceCreatesAndAttachesResource(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $subject = new Stream('php://memory', 'wb+');
        $subject->attach($fileName);
        $resource = fopen($fileName, 'r+');
        fwrite($resource, 'FooBar');
        $subject->rewind();
        self::assertEquals('FooBar', (string)$subject);
    }

    #[Test]
    public function getContentsShouldGetFullStreamContents(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $resource = fopen($fileName, 'r+');
        $subject = new Stream('php://memory', 'wb+');
        $subject->attach($resource);
        fwrite($resource, 'FooBar');
        // rewind, because current pointer is at end of stream!
        $subject->rewind();
        self::assertEquals('FooBar', $subject->getContents());
    }

    #[Test]
    public function getContentsShouldReturnStreamContentsFromCurrentPointer(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $resource = fopen($fileName, 'r+');
        $subject = new Stream('php://memory', 'wb+');
        $subject->attach($resource);
        fwrite($resource, 'FooBar');
        // seek to position 3
        $subject->seek(3);
        self::assertEquals('Bar', $subject->getContents());
    }

    #[Test]
    public function getMetadataReturnsAllMetadataWhenNoKeyPresent(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $resource = fopen($fileName, 'r+');
        $subject = new Stream('php://memory', 'wb+');
        $subject->attach($resource);
        $expected = stream_get_meta_data($resource);
        self::assertEquals($expected, $subject->getMetadata());
    }

    #[Test]
    public function getMetadataReturnsDataForSpecifiedKey(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $resource = fopen($fileName, 'r+');
        $subject = new Stream('php://memory', 'wb+');
        $subject->attach($resource);
        $metadata = stream_get_meta_data($resource);
        $expected = $metadata['uri'];
        self::assertEquals($expected, $subject->getMetadata('uri'));
    }

    #[Test]
    public function getMetadataReturnsNullIfNoDataExistsForKey(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $resource = fopen($fileName, 'r+');
        $subject = new Stream('php://memory', 'wb+');
        $subject->attach($resource);
        self::assertNull($subject->getMetadata('TOTALLY_MADE_UP'));
    }

    #[Test]
    public function getSizeReturnsStreamSize(): void
    {
        $resource = fopen(__FILE__, 'r');
        $expected = fstat($resource);
        $subject = new Stream($resource);
        self::assertEquals($expected['size'], $subject->getSize());
    }
}
