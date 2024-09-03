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

namespace TYPO3\CMS\Core\Tests\Functional\Cache\Backend;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Cache\Backend\RedisBackend;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[RequiresPhpExtension('redis')]
final class RedisBackendTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected function setUp(): void
    {
        if (!getenv('typo3TestingRedisHost')) {
            self::markTestSkipped('environment variable "typo3TestingRedisHost" must be set to run this test');
        }
        // Note we assume that if typo3TestingRedisHost env is set, we can use that for testing,
        // there is no test to see if the daemon is actually up and running. Tests will fail if env
        // is set but daemon is down.
        parent::setUp();
    }

    protected function setUpSubject(array $backendOptions = []): RedisBackend
    {
        // We know this env is set, otherwise setUp() would skip the tests
        $backendOptions['hostname'] = getenv('typo3TestingRedisHost');
        // If typo3TestingRedisPort env is set, use it, otherwise fall back to standard port
        $env = getenv('typo3TestingRedisPort');
        $backendOptions['port'] = is_string($env) ? (int)$env : 6379;

        $frontendMock = $this->createMock(FrontendInterface::class);
        $frontendMock->method('getIdentifier')->willReturn('pages');

        $GLOBALS['TYPO3_CONF_VARS']['LOG'] = 'only needed for logger initialisation';
        $subject = new RedisBackend('Testing', $backendOptions);
        $subject->setLogger($this->createMock(LoggerInterface::class));
        $subject->setCache($frontendMock);
        $subject->initializeObject();
        $subject->flush();
        return $subject;
    }

    private function setUpRedis(): \Redis
    {
        // We know this env is set, otherwise setUp() would skip the tests
        $redisHost = getenv('typo3TestingRedisHost');
        // If typo3TestingRedisPort env is set, use it, otherwise fall back to standard port
        $env = getenv('typo3TestingRedisPort');
        $redisPort = is_string($env) ? (int)$env : 6379;
        $redis = new \Redis();
        $redis->connect($redisHost, $redisPort);
        return $redis;
    }

    #[Test]
    public function setDatabaseThrowsExceptionIfGivenDatabaseNumberIsNotAnInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1279763057);
        $this->setUpSubject(['database' => 'foo']);
    }

    #[Test]
    public function setDatabaseThrowsExceptionIfGivenDatabaseNumberIsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1279763534);
        $this->setUpSubject(['database' => -1]);
    }

    #[Test]
    public function setCompressionThrowsExceptionIfCompressionParameterIsNotOfTypeBoolean(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1289679153);
        $this->setUpSubject(['compression' => 'foo']);
    }

    #[Test]
    public function setCompressionLevelThrowsExceptionIfCompressionLevelIsNotInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1289679154);
        $this->setUpSubject(['compressionLevel' => 'foo']);
    }

    #[Test]
    public function setCompressionLevelThrowsExceptionIfCompressionLevelIsNotBetweenMinusOneAndNine(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1289679155);
        $this->setUpSubject(['compressionLevel' => 11]);
    }

    #[Test]
    public function setConnectionTimeoutThrowsExceptionIfConnectionTimeoutIsNotInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1487849315);
        $this->setUpSubject(['connectionTimeout' => 'foo']);
    }

    #[Test]
    public function setConnectionTimeoutThrowsExceptionIfConnectionTimeoutIsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1487849326);
        $this->setUpSubject(['connectionTimeout' => -1]);
    }

    #[Test]
    public function setThrowsExceptionIfIdentifierIsNotAString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1377006651);
        $subject = $this->setUpSubject();
        $subject->set([], 'data');
    }

    #[Test]
    public function setThrowsExceptionIfDataIsNotAString(): void
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionCode(1279469941);
        $subject = $this->setUpSubject();
        $subject->set(StringUtility::getUniqueId('identifier'), []);
    }

    #[Test]
    public function setThrowsExceptionIfLifetimeIsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1279487573);
        $subject = $this->setUpSubject();
        $subject->set(StringUtility::getUniqueId('identifier'), 'data', [], -42);
    }

    #[Test]
    public function setThrowsExceptionIfLifetimeIsNotNullOrAnInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1279488008);
        $subject = $this->setUpSubject();
        $subject->set(StringUtility::getUniqueId('identifier'), 'data', [], []);
    }

    #[Test]
    public function setStoresEntriesInSelectedDatabase(): void
    {
        $redis = $this->setUpRedis();
        $redis->select(1);
        $subject = $this->setUpSubject(['database' => 1]);
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, 'data');
        $result = $redis->exists('identData:' . $identifier);
        if (is_int($result)) {
            // Since 3.1.4 of phpredis/phpredis the return types has been changed
            $result = (bool)$result;
        }
        self::assertTrue($result);
    }

    #[Test]
    public function setSavesStringDataTypeForIdentifierToDataEntry(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, 'data');
        self::assertSame(\Redis::REDIS_STRING, $redis->type('identData:' . $identifier));
    }

    #[Test]
    public function setSavesEntryWithDefaultLifeTime(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $defaultLifetime = 42;
        $subject->setDefaultLifetime($defaultLifetime);
        $subject->set($identifier, 'data');
        $lifetimeRegisteredInBackend = $redis->ttl('identData:' . $identifier);
        self::assertSame($defaultLifetime, $lifetimeRegisteredInBackend);
    }

    #[Test]
    public function setSavesEntryWithSpecifiedLifeTime(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $lifetime = 43;
        $subject->set($identifier, 'data', [], $lifetime);
        $lifetimeRegisteredInBackend = $redis->ttl('identData:' . $identifier);
        self::assertSame($lifetime, $lifetimeRegisteredInBackend);
    }

    #[Test]
    public function setSavesEntryWithSpecifiedKeyPrefix(): void
    {
        $firstBackend = $this->setUpSubject(['keyPrefix' => 'kp1_']);
        $secondBackend = $this->setUpSubject(['keyPrefix' => 'kp2_']);
        $redis = $this->setUpRedis();

        $identifier = StringUtility::getUniqueId('identifier');
        $data = 'data';

        $firstBackend->set($identifier, $data);
        self::assertSame($data, $firstBackend->get($identifier));
        self::assertSame($data, $redis->get(sprintf('kp1_identData:%s', $identifier)));
        self::assertFalse($redis->get(sprintf('kp2_identData:%s', $identifier)));
        self::assertFalse($secondBackend->get($identifier));
    }

    #[Test]
    public function flushOnPrefixedBackendDoesNotDeleteKeysOfSecondPrefixedBackend(): void
    {
        $firstBackend = $this->setUpSubject(['keyPrefix' => 'kp1_']);
        $secondBackend = $this->setUpSubject(['keyPrefix' => 'kp2_']);
        $redis = $this->setUpRedis();
        $redis->flushAll();

        $identifier = StringUtility::getUniqueId('identifier');
        $data = 'data';

        $firstBackend->set($identifier, $data);
        $secondBackend->set($identifier, $data);
        self::assertSame($data, $firstBackend->get($identifier));
        self::assertSame($data, $secondBackend->get($identifier));
        self::assertSame($data, $redis->get(sprintf('kp1_identData:%s', $identifier)));
        self::assertSame($data, $redis->get(sprintf('kp2_identData:%s', $identifier)));

        $firstBackend->flush();
        self::assertFalse($firstBackend->get($identifier));
        self::assertFalse($redis->get(sprintf('kp1_identData:%s', $identifier)));
        self::assertSame($data, $secondBackend->get($identifier));
        self::assertSame($data, $redis->get(sprintf('kp2_identData:%s', $identifier)));
    }

    #[Test]
    public function flushByTagOnPrefixedBackendDoesNotDeleteKeysOfSecondPrefixedBackend(): void
    {
        $firstBackend = $this->setUpSubject(['keyPrefix' => 'kp1_']);
        $secondBackend = $this->setUpSubject(['keyPrefix' => 'kp2_']);
        $redis = $this->setUpRedis();
        $redis->flushAll();

        $identifier = StringUtility::getUniqueId('identifier');
        $tagName = 'some-tag';
        $data = 'data';

        $firstBackend->set($identifier, $data, [$tagName]);
        $secondBackend->set($identifier, $data, [$tagName]);
        self::assertSame($data, $firstBackend->get($identifier));
        self::assertSame($data, $secondBackend->get($identifier));
        self::assertSame($data, $redis->get(sprintf('kp1_identData:%s', $identifier)));
        self::assertSame($data, $redis->get(sprintf('kp2_identData:%s', $identifier)));

        $firstBackend->flushByTag($tagName);
        self::assertFalse($firstBackend->get($identifier));
        self::assertFalse($redis->get(sprintf('kp1_identData:%s', $identifier)));
        self::assertSame($data, $secondBackend->get($identifier));
        self::assertSame($data, $redis->get(sprintf('kp2_identData:%s', $identifier)));
    }

    #[Test]
    public function flushByTagsOnPrefixedBackendDoesNotDeleteKeysOfSecondPrefixedBackend(): void
    {
        $firstBackend = $this->setUpSubject(['keyPrefix' => 'kp1_']);
        $secondBackend = $this->setUpSubject(['keyPrefix' => 'kp2_']);
        $redis = $this->setUpRedis();
        $redis->flushAll();

        $identifier = StringUtility::getUniqueId('identifier');
        $tagName = 'some-tag';
        $data = 'data';

        $firstBackend->set($identifier, $data, [$tagName]);
        $secondBackend->set($identifier, $data, [$tagName]);
        self::assertSame($data, $firstBackend->get($identifier));
        self::assertSame($data, $secondBackend->get($identifier));

        $firstBackend->flushByTags([$tagName]);
        self::assertFalse($firstBackend->get($identifier));
        self::assertSame($data, $secondBackend->get($identifier));
    }

    #[Test]
    public function setSavesEntryWithUnlimitedLifeTime(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, 'data', [], 0);
        $lifetimeRegisteredInBackend = $redis->ttl('identData:' . $identifier);
        self::assertSame(31536000, $lifetimeRegisteredInBackend);
    }

    #[Test]
    public function setOverwritesExistingEntryWithNewData(): void
    {
        $subject = $this->setUpSubject();
        $data = 'data 1';
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, $data);
        $otherData = 'data 2';
        $subject->set($identifier, $otherData);
        $fetchedData = $subject->get($identifier);
        self::assertSame($otherData, $fetchedData);
    }

    #[Test]
    public function setOverwritesExistingEntryWithSpecifiedLifetime(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $data = 'data';
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, $data);
        $lifetime = 42;
        $subject->set($identifier, $data, [], $lifetime);
        $lifetimeRegisteredInBackend = $redis->ttl('identData:' . $identifier);
        self::assertSame($lifetime, $lifetimeRegisteredInBackend);
    }

    #[Test]
    public function setOverwritesExistingEntryWithNewDefaultLifetime(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $data = 'data';
        $identifier = StringUtility::getUniqueId('identifier');
        $lifetime = 42;
        $subject->set($identifier, $data, [], $lifetime);
        $newDefaultLifetime = 43;
        $subject->setDefaultLifetime($newDefaultLifetime);
        $subject->set($identifier, $data, [], $newDefaultLifetime);
        $lifetimeRegisteredInBackend = $redis->ttl('identData:' . $identifier);
        self::assertSame($newDefaultLifetime, $lifetimeRegisteredInBackend);
    }

    #[Test]
    public function setOverwritesExistingEntryWithNewUnlimitedLifetime(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $data = 'data';
        $identifier = StringUtility::getUniqueId('identifier');
        $lifetime = 42;
        $subject->set($identifier, $data, [], $lifetime);
        $subject->set($identifier, $data, [], 0);
        $lifetimeRegisteredInBackend = $redis->ttl('identData:' . $identifier);
        self::assertSame(31536000, $lifetimeRegisteredInBackend);
    }

    #[Test]
    public function setSavesSetDataTypeForIdentifierToTagsSet(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, 'data', ['tag']);
        self::assertSame(\Redis::REDIS_SET, $redis->type('identTags:' . $identifier));
    }

    #[Test]
    public function setSavesSpecifiedTagsInIdentifierToTagsSet(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $tags = ['thatTag', 'thisTag'];
        $subject->set($identifier, 'data', $tags);
        $savedTags = $redis->sMembers('identTags:' . $identifier);
        sort($savedTags);
        self::assertSame($tags, $savedTags);
    }

    #[Test]
    public function setRemovesAllPreviouslySetTagsFromIdentifierToTagsSet(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $tags = ['fooTag', 'barTag'];
        $subject->set($identifier, 'data', $tags);
        $subject->set($identifier, 'data', []);
        self::assertSame([], $redis->sMembers('identTags:' . $identifier));
    }

    #[Test]
    public function setRemovesMultiplePreviouslySetTagsFromIdentifierToTagsSet(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $firstTagSet = ['tag1', 'tag2', 'tag3', 'tag4'];
        $subject->set($identifier, 'data', $firstTagSet);
        $secondTagSet = ['tag1', 'tag3'];
        $subject->set($identifier, 'data', $secondTagSet);
        $actualTagSet = $redis->sMembers('identTags:' . $identifier);
        sort($actualTagSet);
        self::assertSame($secondTagSet, $actualTagSet);
    }

    #[Test]
    public function setSavesSetDataTypeForTagToIdentifiersSet(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $tag = 'tag';
        $subject->set($identifier, 'data', [$tag]);
        self::assertSame(\Redis::REDIS_SET, $redis->type('tagIdents:' . $tag));
    }

    #[Test]
    public function setSavesIdentifierInTagToIdentifiersSetOfSpecifiedTag(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $tag = 'thisTag';
        $subject->set($identifier, 'data', [$tag]);
        $savedTagToIdentifiersMemberArray = $redis->sMembers('tagIdents:' . $tag);
        self::assertSame([$identifier], $savedTagToIdentifiersMemberArray);
    }

    #[Test]
    public function setAppendsSecondIdentifierInTagToIdentifiersEntry(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $firstIdentifier = StringUtility::getUniqueId('identifier1-');
        $tag = 'thisTag';
        $subject->set($firstIdentifier, 'data', [$tag]);
        $secondIdentifier = StringUtility::getUniqueId('identifier2-');
        $subject->set($secondIdentifier, 'data', [$tag]);
        $savedTagToIdentifiersMemberArray = $redis->sMembers('tagIdents:' . $tag);
        sort($savedTagToIdentifiersMemberArray);
        $identifierArray = [$firstIdentifier, $secondIdentifier];
        sort($identifierArray);
        self::assertSame([$firstIdentifier, $secondIdentifier], $savedTagToIdentifiersMemberArray);
    }

    #[Test]
    public function setRemovesIdentifierFromTagToIdentifiersEntryIfTagIsOmittedOnConsecutiveSet(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $tag = 'thisTag';
        $subject->set($identifier, 'data', [$tag]);
        $subject->set($identifier, 'data', []);
        $savedTagToIdentifiersMemberArray = $redis->sMembers('tagIdents:' . $tag);
        self::assertSame([], $savedTagToIdentifiersMemberArray);
    }

    #[Test]
    public function setAddsIdentifierInTagToIdentifiersEntryIfTagIsAddedOnConsecutiveSet(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, 'data');
        $tag = 'thisTag';
        $subject->set($identifier, 'data', [$tag]);
        $savedTagToIdentifiersMemberArray = $redis->sMembers('tagIdents:' . $tag);
        self::assertSame([$identifier], $savedTagToIdentifiersMemberArray);
    }

    #[Test]
    public function setSavesCompressedDataWithEnabledCompression(): void
    {
        $subject = $this->setUpSubject([
            'compression' => true,
        ]);
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $data = 'some data ' . microtime();
        $subject->set($identifier, $data);
        $uncompressedStoredData = '';
        try {
            $uncompressedStoredData = @gzuncompress($redis->get('identData:' . $identifier));
        } catch (\Exception $e) {
        }
        self::assertEquals($data, $uncompressedStoredData, 'Original and compressed data don\'t match');
    }

    #[Test]
    public function setSavesPlaintextDataWithEnabledCompressionAndCompressionLevel0(): void
    {
        $subject = $this->setUpSubject([
            'compression' => true,
            'compressionLevel' => 0,
        ]);
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $data = 'some data ' . microtime();
        $subject->set($identifier, $data);
        self::assertGreaterThan(0, substr_count($redis->get('identData:' . $identifier), $data), 'Plaintext data not found');
    }

    #[Test]
    public function hasThrowsExceptionIfIdentifierIsNotAString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1377006653);

        $subject = $this->setUpSubject();
        $subject->has([]);
    }

    #[Test]
    public function hasReturnsFalseForNotExistingEntry(): void
    {
        $subject = $this->setUpSubject();
        $identifier = StringUtility::getUniqueId('identifier');
        self::assertFalse($subject->has($identifier));
    }

    #[Test]
    public function hasReturnsTrueForPreviouslySetEntry(): void
    {
        $subject = $this->setUpSubject();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, 'data');
        self::assertTrue($subject->has($identifier));
    }

    #[Test]
    public function getThrowsExceptionIfIdentifierIsNotAString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        // @todo Add exception code with redis extension

        $subject = $this->setUpSubject();
        $subject->get([]);
    }

    #[Test]
    public function getReturnsPreviouslyCompressedSetEntry(): void
    {
        $subject = $this->setUpSubject([
            'compression' => true,
        ]);
        $data = 'data';
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, $data);
        $fetchedData = $subject->get($identifier);
        self::assertSame($data, $fetchedData);
    }

    #[Test]
    public function getReturnsPreviouslySetEntry(): void
    {
        $subject = $this->setUpSubject();
        $data = 'data';
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, $data);
        $fetchedData = $subject->get($identifier);
        self::assertSame($data, $fetchedData);
    }

    #[Test]
    public function removeThrowsExceptionIfIdentifierIsNotAString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1377006654);

        $subject = $this->setUpSubject();
        $subject->remove([]);
    }

    #[Test]
    public function removeReturnsFalseIfNoEntryWasDeleted(): void
    {
        $subject = $this->setUpSubject();
        self::assertFalse($subject->remove(StringUtility::getUniqueId('identifier')));
    }

    #[Test]
    public function removeReturnsTrueIfAnEntryWasDeleted(): void
    {
        $subject = $this->setUpSubject();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, 'data');
        self::assertTrue($subject->remove($identifier));
    }

    #[Test]
    public function removeDeletesEntryFromCache(): void
    {
        $subject = $this->setUpSubject();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, 'data');
        $subject->remove($identifier);
        self::assertFalse($subject->has($identifier));
    }

    #[Test]
    public function removeDeletesIdentifierToTagEntry(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $tag = 'thisTag';
        $subject->set($identifier, 'data', [$tag]);
        $subject->remove($identifier);
        $result = $redis->exists('identTags:' . $identifier);
        if (is_int($result)) {
            // Since 3.1.4 of phpredis/phpredis the return types has been changed
            $result = (bool)$result;
        }
        self::assertFalse($result);
    }

    #[Test]
    public function removeDeletesIdentifierFromTagToIdentifiersSet(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $tag = 'thisTag';
        $subject->set($identifier, 'data', [$tag]);
        $subject->remove($identifier);
        $tagToIdentifiersMemberArray = $redis->sMembers('tagIdents:' . $tag);
        self::assertSame([], $tagToIdentifiersMemberArray);
    }

    #[Test]
    public function removeDeletesIdentifierFromTagToIdentifiersSetWithMultipleEntries(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $firstIdentifier = StringUtility::getUniqueId('identifier');
        $secondIdentifier = StringUtility::getUniqueId('identifier');
        $tag = 'thisTag';
        $subject->set($firstIdentifier, 'data', [$tag]);
        $subject->set($secondIdentifier, 'data', [$tag]);
        $subject->remove($firstIdentifier);
        $tagToIdentifiersMemberArray = $redis->sMembers('tagIdents:' . $tag);
        self::assertSame([$secondIdentifier], $tagToIdentifiersMemberArray);
    }

    #[Test]
    public function findIdentifiersByTagThrowsExceptionIfTagIsNotAString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1377006655);

        $subject = $this->setUpSubject();
        $subject->findIdentifiersByTag([]);
    }

    #[Test]
    public function findIdentifiersByTagReturnsEmptyArrayForNotExistingTag(): void
    {
        $subject = $this->setUpSubject();
        self::assertSame([], $subject->findIdentifiersByTag('thisTag'));
    }

    #[Test]
    public function findIdentifiersByTagReturnsAllIdentifiersTagedWithSpecifiedTag(): void
    {
        $subject = $this->setUpSubject();
        $firstIdentifier = StringUtility::getUniqueId('identifier1-');
        $secondIdentifier = StringUtility::getUniqueId('identifier2-');
        $thirdIdentifier = StringUtility::getUniqueId('identifier3-');
        $tagsForFirstIdentifier = ['thisTag'];
        $tagsForSecondIdentifier = ['thatTag'];
        $tagsForThirdIdentifier = ['thisTag', 'thatTag'];
        $subject->set($firstIdentifier, 'data', $tagsForFirstIdentifier);
        $subject->set($secondIdentifier, 'data', $tagsForSecondIdentifier);
        $subject->set($thirdIdentifier, 'data', $tagsForThirdIdentifier);
        $expectedResult = [$firstIdentifier, $thirdIdentifier];
        $actualResult = $subject->findIdentifiersByTag('thisTag');
        sort($actualResult);
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function flushRemovesAllEntriesFromCache(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, 'data');
        $subject->flush();
        self::assertSame([], $redis->keys('*'));
    }

    #[Test]
    public function flushByTagThrowsExceptionIfTagIsNotAString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1377006656);

        $subject = $this->setUpSubject();
        $subject->flushByTag([]);
    }

    #[Test]
    public function flushByTagRemovesEntriesTaggedWithSpecifiedTag(): void
    {
        $subject = $this->setUpSubject();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier . 'A', 'data', ['tag1']);
        $subject->set($identifier . 'B', 'data', ['tag2']);
        $subject->set($identifier . 'C', 'data', ['tag1', 'tag2']);
        $subject->flushByTag('tag1');
        $expectedResult = [false, true, false];
        $actualResult = [
            $subject->has($identifier . 'A'),
            $subject->has($identifier . 'B'),
            $subject->has($identifier . 'C'),
        ];
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function flushByTagsRemovesEntriesTaggedWithSpecifiedTags(): void
    {
        $subject = $this->setUpSubject();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier . 'A', 'data', ['tag1']);
        $subject->set($identifier . 'B', 'data', ['tag2']);
        $subject->set($identifier . 'C', 'data', ['tag1', 'tag2']);
        $subject->set($identifier . 'D', 'data', ['tag3']);
        $subject->flushByTags(['tag1', 'tag2']);
        $expectedResult = [false, false, false, true];
        $actualResult = [
            $subject->has($identifier . 'A'),
            $subject->has($identifier . 'B'),
            $subject->has($identifier . 'C'),
            $subject->has($identifier . 'D'),
        ];
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function flushByTagRemovesTemporarySet(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier . 'A', 'data', ['tag1']);
        $subject->set($identifier . 'C', 'data', ['tag1', 'tag2']);
        $subject->flushByTag('tag1');
        self::assertSame([], $redis->keys('temp*'));
    }

    #[Test]
    public function flushByTagRemovesIdentifierToTagsSetOfEntryTaggedWithGivenTag(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $tag = 'tag1';
        $subject->set($identifier, 'data', [$tag]);
        $subject->flushByTag($tag);
        $result = $redis->exists('identTags:' . $identifier);
        if (is_int($result)) {
            // Since 3.1.4 of phpredis/phpredis the return types has been changed
            $result = (bool)$result;
        }
        self::assertFalse($result);
    }

    #[Test]
    public function flushByTagDoesNotRemoveIdentifierToTagsSetOfUnrelatedEntry(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifierToBeRemoved = StringUtility::getUniqueId('identifier');
        $tagToRemove = 'tag1';
        $subject->set($identifierToBeRemoved, 'data', [$tagToRemove]);
        $identifierNotToBeRemoved = StringUtility::getUniqueId('identifier');
        $tagNotToRemove = 'tag2';
        $subject->set($identifierNotToBeRemoved, 'data', [$tagNotToRemove]);
        $subject->flushByTag($tagToRemove);
        self::assertSame([$tagNotToRemove], $redis->sMembers('identTags:' . $identifierNotToBeRemoved));
    }

    #[Test]
    public function flushByTagRemovesTagToIdentifiersSetOfGivenTag(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $tag = 'tag1';
        $subject->set($identifier, 'data', [$tag]);
        $subject->flushByTag($tag);
        $result = $redis->exists('tagIdents:' . $tag);
        if (is_int($result)) {
            // Since 3.1.4 of phpredis/phpredis the return types has been changed
            $result = (bool)$result;
        }
        self::assertFalse($result);
    }

    #[Test]
    public function flushByTagRemovesIdentifiersTaggedWithGivenTagFromTagToIdentifiersSets(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier . 'A', 'data', ['tag1', 'tag2']);
        $subject->set($identifier . 'B', 'data', ['tag1', 'tag2']);
        $subject->set($identifier . 'C', 'data', ['tag2']);
        $subject->flushByTag('tag1');
        self::assertSame([$identifier . 'C'], $redis->sMembers('tagIdents:tag2'));
    }

    #[Test]
    public function collectGarbageDoesNotRemoveNotExpiredIdentifierToDataEntry(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier . 'A', 'data', ['tag']);
        $subject->set($identifier . 'B', 'data', ['tag']);
        $redis->del('identData:' . $identifier . 'A');
        $subject->collectGarbage();
        $result = $redis->exists('identData:' . $identifier . 'B');
        if (is_int($result)) {
            // Since 3.1.4 of phpredis/phpredis the return types has been changed
            $result = (bool)$result;
        }
        self::assertTrue($result);
    }

    #[Test]
    public function collectGarbageRemovesLeftOverIdentifierToTagsSet(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier . 'A', 'data', ['tag']);
        $subject->set($identifier . 'B', 'data', ['tag']);
        $redis->del('identData:' . $identifier . 'A');
        $subject->collectGarbage();
        $expectedResult = [false, true];
        $resultA = $redis->exists('identTags:' . $identifier . 'A');
        $resultB = $redis->exists('identTags:' . $identifier . 'B');
        if (is_int($resultA)) {
            // Since 3.1.4 of phpredis/phpredis the return types has been changed
            $resultA = (bool)$resultA;
        }
        if (is_int($resultB)) {
            // Since 3.1.4 of phpredis/phpredis the return types has been changed
            $resultB = (bool)$resultB;
        }
        $actualResult = [
            $resultA,
            $resultB,
        ];
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function collectGarbageRemovesExpiredIdentifierFromTagsToIdentifierSet(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier . 'A', 'data', ['tag1', 'tag2']);
        $subject->set($identifier . 'B', 'data', ['tag2']);
        $redis->del('identData:' . $identifier . 'A');
        $subject->collectGarbage();
        $expectedResult = [
            [],
            [$identifier . 'B'],
        ];
        $actualResult = [
            $redis->sMembers('tagIdents:tag1'),
            $redis->sMembers('tagIdents:tag2'),
        ];
        self::assertSame($expectedResult, $actualResult);
    }
}
