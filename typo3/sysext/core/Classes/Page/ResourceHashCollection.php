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

namespace TYPO3\CMS\Core\Page;

use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\HashProxy;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\HashType;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\HashValue;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\SystemResource\Type\StaticResourceInterface;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\Core\SystemResource\Type\UriResource;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * @internal
 */
final readonly class ResourceHashCollection
{
    public const AUTO = 'auto';

    public function __construct(
        private LoggerInterface $logger,
        private SystemResourceFactory $systemResourceFactory,
        #[Autowire(service: 'cache.assets')]
        private ?FrontendInterface $assetsCache = null,
    ) {}

    public function fetchResourceHash(string|UriInterface|StaticResourceInterface $value, HashType $type = HashType::sha256): ?HashValue
    {
        if (is_string($value)) {
            $value = $this->resolveResourceValue($value);
        }
        if (empty($value)) {
            return null;
        }
        try {
            if ($value instanceof UriInterface || $value instanceof UriResource) {
                return HashValue::parse(
                    HashProxy::urls((string)$value)->withType($type)->compile($this->assetsCache)
                );
            }
            if ($value instanceof SystemResourceInterface) {
                return HashValue::parse(
                    HashProxy::resource((string)$value)->withType($type)->compile($this->assetsCache)
                );
            }
            return null;
        } catch (\Throwable $t) {
            $this->logger->error('Could not add resource hash: {exceptionMessage}', [
                'value' => $value,
                'exceptionMessage' => $t->getMessage(),
                'exceptionCode' => $t->getCode(),
            ]);
            return null;
        }
    }

    public function resolveResourceValue(string $value): UriInterface|StaticResourceInterface|null
    {
        if (PathUtility::hasProtocolAndScheme($value)) {
            try {
                return new Uri($value);
            } catch (\Exception) {
                return null;
            }
        }
        if (PathUtility::isExtensionPath($value)) {
            return $this->systemResourceFactory->createResource($value);
        }
        return null;
    }
}
