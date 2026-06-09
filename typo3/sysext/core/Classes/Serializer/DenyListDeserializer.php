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

namespace TYPO3\CMS\Core\Serializer;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Security\BlockSerializationTrait;
use TYPO3\CMS\Core\Serializer\Exception\DeserializerException;

/**
 * Deserializes a PHP-serialized payload while refusing any class that carries
 * a user-defined __destruct() or an exploitable __wakeup() (one not provided
 * solely by BlockSerializationTrait).
 *
 * The per-class deny/allow decision is made lazily via ReflectionClass at the
 * first encounter of each class name, then cached in cache:core so that
 * reflection is never repeated for the same class within a cache lifetime.
 *
 * Use this instead of a raw unserialize() call when the set of expected classes
 * is not known upfront but dangerous gadget classes must still be excluded.
 *
 * @internal Only to be used by TYPO3 core
 */
#[Autoconfigure(public: true)]
final readonly class DenyListDeserializer
{
    /**
     * @var list<string>
     */
    private array $allowedClassNames;
    private \ReflectionMethod $blockSerializationWakeup;

    public function __construct(
        #[Autowire(service: 'cache.core')]
        private PhpFrontend $cache,
        private HashService $hashService,
        private DeserializationService $deserializationService,
    ) {
        $allowedClassNames = $GLOBALS['TYPO3_CONF_VARS']['SYS']['deserialization']['allowedClassNames'] ?? null;
        $this->allowedClassNames = is_array($allowedClassNames) ? $allowedClassNames : [];
        $this->blockSerializationWakeup = (new \ReflectionClass(BlockSerializationTrait::class))->getMethod('__wakeup');
    }

    /**
     * Deserializes $payload, throwing DeserializerException if any class name
     * found in the payload is a deserialization gadget, or if the payload is
     * syntactically malformed.
     */
    public function deserialize(string $payload): mixed
    {
        $classNames = $this->deserializationService->parseClassNames($payload);
        foreach ($classNames as $className) {
            if ($this->shallClassBeDenied($className)) {
                throw new DeserializerException(
                    'Denied class name "' . $className . '" found in payload',
                    1778594101
                );
            }
        }

        return $this->deserializationService->deserialize($payload, $classNames ?: false);
    }

    private function shallClassBeDenied(string $className): bool
    {
        if (in_array($className, $this->allowedClassNames, true)) {
            return false;
        }

        $cacheKey = 'DenyListDeserializer_' . hash('xxh128', $className);
        if ($this->cache->has($cacheKey)) {
            $entry = $this->cache->require($cacheKey);
            if (is_array($entry)
                && isset($entry['denied'], $entry['hmac'])
                && $this->hashService->validateHmac(
                    $this->createHmacPayload($className, (bool)$entry['denied']),
                    DenyListDeserializer::class,
                    $entry['hmac']
                )
            ) {
                return (bool)$entry['denied'];
            }
            // Tampered or stale entry — fall through to recompute
        }

        $denied = $this->resolveClassDenyStatus($className);
        $hmac = $this->hashService->hmac($this->createHmacPayload($className, $denied), DenyListDeserializer::class);
        $this->cache->set($cacheKey, 'return ' . var_export(['denied' => $denied, 'hmac' => $hmac], true) . ';');
        return $denied;
    }

    private function createHmacPayload(string $className, bool $denied): string
    {
        return $className . ':' . ($denied ? '1' : '0');
    }

    private function resolveClassDenyStatus(string $className): bool
    {
        try {
            $rc = new \ReflectionClass($className);
        } catch (\ReflectionException) {
            // The class does not exist or cannot be reflected (and not instantiated).
            // Thus, the class is allowed, since it cannot be a gadget and would
            // result in a `__PHP_Incomplete_Class` during deserialization.
            return false;
        }
        if ($rc->isInterface() || $rc->isTrait()) {
            return false;
        }
        return $this->getUserDefinedMethod($rc, '__destruct') !== null
            || $this->hasDeniableWakeupMethod($rc);
    }

    /**
     * Returns the method when $methodName is declared in user-defined (non-internal) code
     * somewhere in the class hierarchy. This excludes methods like Exception::__wakeup()
     * that PHP declares internally and that are harmless for deserialization purposes.
     */
    private function getUserDefinedMethod(\ReflectionClass $rc, string $methodName): ?\ReflectionMethod
    {
        if (!$rc->hasMethod($methodName)) {
            return null;
        }
        $method = $rc->getMethod($methodName);
        if ($method->getDeclaringClass()->isInternal()) {
            return null;
        }
        return $method;
    }

    /**
     * Returns true when the class has a user-defined __wakeup() that is NOT
     * BlockSerializationTrait::__wakeup(). Classes whose only __wakeup comes
     * from BlockSerializationTrait are already protected against deserialization
     * (the trait throws unconditionally) and must not be treated as gadgets.
     *
     * Note: for trait methods getDeclaringClass() returns the using class, not the
     * trait — so the origin is identified by comparing the method's source file and line
     * against the trait's own __wakeup declaration.
     */
    private function hasDeniableWakeupMethod(\ReflectionClass $rc): bool
    {
        $method = $this->getUserDefinedMethod($rc, '__wakeup');
        if ($method === null) {
            return false;
        }
        return $method->getFileName() !== $this->blockSerializationWakeup->getFileName()
            || $method->getStartLine() !== $this->blockSerializationWakeup->getStartLine();
    }
}
