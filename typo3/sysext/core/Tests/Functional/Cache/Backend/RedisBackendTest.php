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

use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Cache\Backend\RedisBackend;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for the cache to redis backend
 *
 * Warning:
 * These functional tests use and flush redis database numbers 0 and 1 on the
 * redis host specified by environment variable typo3RedisHost
 *
 * @requires extension redis
 */
class RedisBackendTest extends FunctionalTestCase
{
    use ProphecyTrait;

    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        if (!getenv('typo3TestingRedisHost')) {
            self::markTestSkipped('environment variable "typo3TestingRedisHost" must be set to run this test');
        }
        // Note we assume that if that typo3TestingRedisHost env is set, we can use that for testing,
        // there is no test to see if the daemon is actually up and running. Tests will fail if env
        // is set but daemon is down.

        parent::setUp();
    }

    /**
     * Sets up the redis cache backend used for testing
     */
    protected function setUpSubject(array $backendOptions = []): RedisBackend
    {
        // We know this env is set, otherwise setUp() would skip the tests
        $backendOptions['hostname'] = getenv('typo3TestingRedisHost');
        // If typo3TestingRedisPort env is set, use it, otherwise fall back to standard port
        $env = getenv('typo3TestingRedisPort');
        $backendOptions['port'] = is_string($env) ? (int)$env : 6379;

        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        $GLOBALS['TYPO3_CONF_VARS']['LOG'] = 'only needed for logger initialisation';
        $subject = new RedisBackend('Testing', $backendOptions);
        $subject->setLogger($this->prophesize(LoggerInterface::class)->reveal());
        $subject->setCache($frontendProphecy->reveal());
        $subject->initializeObject();
        $subject->flush();
        return $subject;
    }

    /**
     * Sets up a test-internal redis connection to check implementation details
     */
    protected function setUpRedis(): \Redis
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

    /**
     * @test
     */
    public function setDatabaseThrowsExceptionIfGivenDatabaseNumberIsNotAnInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1279763057);

        $this->setUpSubject(['database' => 'foo']);
    }

    /**
     * @test
     */
    public function setDatabaseThrowsExceptionIfGivenDatabaseNumberIsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1279763534);

        $this->setUpSubject(['database' => -1]);
    }

    /**
     * @test
     */
    public function setCompressionThrowsExceptionIfCompressionParameterIsNotOfTypeBoolean(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1289679153);

        $this->setUpSubject(['compression' => 'foo']);
    }

    /**
     * @test
     */
    public function setCompressionLevelThrowsExceptionIfCompressionLevelIsNotInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1289679154);

        $this->setUpSubject(['compressionLevel' => 'foo']);
    }

    /**
     * @test
     */
    public function setCompressionLevelThrowsExceptionIfCompressionLevelIsNotBetweenMinusOneAndNine(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1289679155);

        $this->setUpSubject(['compressionLevel' => 11]);
    }

    /**
     * @test
     */
    public function setConnectionTimeoutThrowsExceptionIfConnectionTimeoutIsNotInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1487849315);

        $this->setUpSubject(['connectionTimeout' => 'foo']);
    }

    /**
     * @test
     */
    public function setConnectionTimeoutThrowsExceptionIfConnectionTimeoutIsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1487849326);

        $this->setUpSubject(['connectionTimeout' => -1]);
    }

    /**
     * @test
     */
    public function setThrowsExceptionIfIdentifierIsNotAString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1377006651);

        $subject = $this->setUpSubject();
        $subject->set([], 'data');
    }

    /**
     * @test
     */
    public function setThrowsExceptionIfDataIsNotAString(): void
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionCode(1279469941);

        $subject = $this->setUpSubject();
        $subject->set(StringUtility::getUniqueId('identifier'), []);
    }

    /**
     * @test
     */
    public function setThrowsExceptionIfLifetimeIsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1279487573);

        $subject = $this->setUpSubject();
        $subject->set(StringUtility::getUniqueId('identifier'), 'data', [], -42);
    }

    /**
     * @test
     */
    public function setThrowsExceptionIfLifetimeIsNotNullOrAnInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1279488008);

        $subject = $this->setUpSubject();
        $subject->set(StringUtility::getUniqueId('identifier'), 'data', [], []);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function setSavesStringDataTypeForIdentifierToDataEntry(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, 'data');
        self::assertSame(\Redis::REDIS_STRING, $redis->type('identData:' . $identifier));
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function setSavesEntryWithUnlimitedLifeTime(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, 'data', [], 0);
        $lifetimeRegisteredInBackend = $redis->ttl('identData:' . $identifier);
        self::assertSame(31536000, $lifetimeRegisteredInBackend);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function setSavesSetDataTypeForIdentifierToTagsSet(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, 'data', ['tag']);
        self::assertSame(\Redis::REDIS_SET, $redis->type('identTags:' . $identifier));
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function setSavesSetDataTypeForTagToIdentifiersSet(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $tag = 'tag';
        $subject->set($identifier, 'data', [$tag]);
        self::assertSame(\Redis::REDIS_SET, $redis->type('tagIdents:' . $tag));
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function hasThrowsExceptionIfIdentifierIsNotAString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1377006653);

        $subject = $this->setUpSubject();
        $subject->has([]);
    }

    /**
     * @test
     */
    public function hasReturnsFalseForNotExistingEntry(): void
    {
        $subject = $this->setUpSubject();
        $identifier = StringUtility::getUniqueId('identifier');
        self::assertFalse($subject->has($identifier));
    }

    /**
     * @test
     */
    public function hasReturnsTrueForPreviouslySetEntry(): void
    {
        $subject = $this->setUpSubject();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, 'data');
        self::assertTrue($subject->has($identifier));
    }

    /**
     * @test
     */
    public function getThrowsExceptionIfIdentifierIsNotAString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        // @todo Add exception code with redis extension

        $subject = $this->setUpSubject();
        $subject->get([]);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function getReturnsPreviouslySetEntry(): void
    {
        $subject = $this->setUpSubject();
        $data = 'data';
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, $data);
        $fetchedData = $subject->get($identifier);
        self::assertSame($data, $fetchedData);
    }

    /**
     * @test
     */
    public function removeThrowsExceptionIfIdentifierIsNotAString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1377006654);

        $subject = $this->setUpSubject();
        $subject->remove([]);
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfNoEntryWasDeleted(): void
    {
        $subject = $this->setUpSubject();
        self::assertFalse($subject->remove(StringUtility::getUniqueId('identifier')));
    }

    /**
     * @test
     */
    public function removeReturnsTrueIfAnEntryWasDeleted(): void
    {
        $subject = $this->setUpSubject();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, 'data');
        self::assertTrue($subject->remove($identifier));
    }

    /**
     * @test
     */
    public function removeDeletesEntryFromCache(): void
    {
        $subject = $this->setUpSubject();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, 'data');
        $subject->remove($identifier);
        self::assertFalse($subject->has($identifier));
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function findIdentifiersByTagThrowsExceptionIfTagIsNotAString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1377006655);

        $subject = $this->setUpSubject();
        $subject->findIdentifiersByTag([]);
    }

    /**
     * @test
     */
    public function findIdentifiersByTagReturnsEmptyArrayForNotExistingTag(): void
    {
        $subject = $this->setUpSubject();
        self::assertSame([], $subject->findIdentifiersByTag('thisTag'));
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function flushRemovesAllEntriesFromCache(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier, 'data');
        $subject->flush();
        self::assertSame([], $redis->getKeys('*'));
    }

    /**
     * @test
     */
    public function flushByTagThrowsExceptionIfTagIsNotAString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1377006656);

        $subject = $this->setUpSubject();
        $subject->flushByTag([]);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function flushByTagRemovesTemporarySet(): void
    {
        $subject = $this->setUpSubject();
        $redis = $this->setUpRedis();
        $identifier = StringUtility::getUniqueId('identifier');
        $subject->set($identifier . 'A', 'data', ['tag1']);
        $subject->set($identifier . 'C', 'data', ['tag1', 'tag2']);
        $subject->flushByTag('tag1');
        self::assertSame([], $redis->getKeys('temp*'));
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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
