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

namespace TYPO3\CMS\Core\Resource;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Model representing an absolute or relative path in the local file system
 * @internal
 */
class LocalPath
{
    public const TYPE_ABSOLUTE = 1;
    public const TYPE_RELATIVE = 2;

    protected string $raw;
    protected ?string $relative = null;
    protected string $absolute;
    protected int $type;

    public function __construct(string $value, int $type)
    {
        if ($type !== self::TYPE_ABSOLUTE && $type !== self::TYPE_RELATIVE) {
            throw new \LogicException(sprintf('Unexpected type "%d"', $type), 1625826491);
        }

        // @todo `../` is erased here, check again if this is a valid scenario
        // value and absolute have leading and trailing slash, e.g. '/some/path/'
        $value = '/' . trim(PathUtility::getCanonicalPath($value), '/');
        $value .= $value !== '/' ? '/' : '';
        $this->raw = $value;
        $this->type = $type;

        $publicPath = Environment::getPublicPath();
        if ($type === self::TYPE_RELATIVE) {
            $this->relative = $value;
            $this->absolute = PathUtility::getCanonicalPath($publicPath . $value) . '/';
        } elseif ($type === self::TYPE_ABSOLUTE) {
            $this->absolute = $value;
            $this->relative = str_starts_with($value, $publicPath)
                ? substr($value, strlen($publicPath))
                : null;
        }
    }

    /**
     * Resolves the base path of a local driver configuration: an absolute
     * path is used as is, a relative path with the legacy "pathType" option
     * set to "relative" is resolved against the public path, any other
     * relative path against the project path.
     *
     * @return self|null null when the configuration has no base path
     */
    public static function fromDriverConfiguration(array $configuration): ?self
    {
        $basePath = (string)($configuration['basePath'] ?? '');
        if ($basePath === '') {
            return null;
        }
        if (($configuration['pathType'] ?? '') === 'relative') {
            return new self($basePath, self::TYPE_RELATIVE);
        }
        if (PathUtility::isAbsolutePath($basePath)) {
            return new self($basePath, self::TYPE_ABSOLUTE);
        }
        return new self(Environment::getProjectPath() . '/' . $basePath, self::TYPE_ABSOLUTE);
    }

    /**
     * @return string normalized path as provided
     */
    public function getRaw(): string
    {
        return $this->raw;
    }

    /**
     * @return string|null (calculated) relative path to public path - `null` if outside public path
     */
    public function getRelative(): ?string
    {
        return $this->relative;
    }

    /**
     * @return string (calculated) absolute path
     */
    public function getAbsolute(): string
    {
        return $this->absolute;
    }

    public function isAbsolute(): bool
    {
        return $this->type === self::TYPE_ABSOLUTE;
    }

    public function isRelative(): bool
    {
        return $this->type === self::TYPE_RELATIVE;
    }
}
