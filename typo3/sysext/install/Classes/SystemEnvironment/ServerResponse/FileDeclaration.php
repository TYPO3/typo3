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

namespace TYPO3\CMS\Install\SystemEnvironment\ServerResponse;

use Psr\Http\Message\ResponseInterface;

/**
 * Declares contents on server response expectations on a static file.
 *
 * @internal should only be used from within TYPO3 Core
 */
class FileDeclaration
{
    public const FLAG_BUILD_HTML = 1;
    public const FLAG_BUILD_PHP = 2;
    public const FLAG_BUILD_SVG = 4;
    public const FLAG_BUILD_HTML_DOCUMENT = 64;
    public const FLAG_BUILD_SVG_DOCUMENT = 128;

    /**
     * @var FileLocation
     */
    protected $fileLocation;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var bool
     */
    protected $fail;

    /**
     * @var string|null
     */
    protected $expectedContentType;

    /**
     * @var string|null
     */
    protected $unexpectedContentType;

    /**
     * @var string|null
     */
    protected $expectedContent;

    /**
     * @var string|null
     */
    protected $unexpectedContent;

    /**
     * @var \Closure
     */
    protected $handler;

    /**
     * @var int
     */
    protected $buildFlags = self::FLAG_BUILD_HTML | self::FLAG_BUILD_HTML_DOCUMENT;

    public function __construct(FileLocation $fileLocation, string $fileName, bool $fail = false)
    {
        $this->fileLocation = $fileLocation;
        $this->fileName = $fileName;
        $this->fail = $fail;
    }

    public function buildContent(): string
    {
        $content = '';
        if ($this->buildFlags & self::FLAG_BUILD_HTML) {
            $content .= '<div>HTML content</div>';
        }
        if ($this->buildFlags & self::FLAG_BUILD_PHP) {
            // base64 encoded representation of 'PHP content'
            $content .= '<div><?php echo base64_decode(\'UEhQIGNvbnRlbnQ=\');?></div>';
        }
        if ($this->buildFlags & self::FLAG_BUILD_SVG) {
            $content .= '<text id="test" x="0" y="0">SVG content</text>';
        }
        if ($this->buildFlags & self::FLAG_BUILD_SVG_DOCUMENT) {
            return sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg">%s</svg>',
                $content
            );
        }
        return sprintf(
            '<!DOCTYPE html><html lang="en"><body>%s</body></html>',
            $content
        );
    }

    public function matches(ResponseInterface $response): bool
    {
        return $this->getMismatches($response) === [];
    }

    /**
     * @param ResponseInterface $response
     * @return StatusMessage[]
     */
    public function getMismatches(ResponseInterface $response): array
    {
        $mismatches = [];
        if ($this->handler instanceof \Closure) {
            $result = $this->handler->call($this, $response);
            if ($result !== null) {
                $mismatches[] = $result;
            }
            return $mismatches;
        }

        $body = (string)$response->getBody();
        $contentType = $response->getHeaderLine('content-type');
        if ($this->expectedContent !== null && !str_contains($body, $this->expectedContent)) {
            $mismatches[] = new StatusMessage(
                'content mismatch %s',
                $this->expectedContent,
                $body
            );
        }
        if ($this->unexpectedContent !== null && str_contains($body, $this->unexpectedContent)) {
            $mismatches[] = new StatusMessage(
                'unexpected content %s',
                $this->unexpectedContent,
                $body
            );
        }
        if ($this->expectedContentType !== null
            && strpos($contentType . ';', $this->expectedContentType . ';') !== 0) {
            $mismatches[] = new StatusMessage(
                'content-type mismatch %s, got %s',
                $this->expectedContentType,
                $contentType
            );
        }
        if ($this->unexpectedContentType !== null
            && strpos($contentType . ';', $this->unexpectedContentType . ';') === 0) {
            $mismatches[] = new StatusMessage(
                'unexpected content-type %s',
                $this->unexpectedContentType,
                $contentType
            );
        }
        return $mismatches;
    }

    public function withExpectedContentType(string $contentType): self
    {
        $target = clone $this;
        $target->expectedContentType = $contentType;
        return $target;
    }

    public function withUnexpectedContentType(string $contentType): self
    {
        $target = clone $this;
        $target->unexpectedContentType = $contentType;
        return $target;
    }

    public function withExpectedContent(string $content): self
    {
        $target = clone $this;
        $target->expectedContent = $content;
        return $target;
    }

    public function withUnexpectedContent(string $content): self
    {
        $target = clone $this;
        $target->unexpectedContent = $content;
        return $target;
    }

    public function withHandler(\Closure $handler): self
    {
        $target = clone $this;
        $target->handler = $handler;
        return $target;
    }

    public function withBuildFlags(int $buildFlags): self
    {
        $target = clone $this;
        $target->buildFlags = $buildFlags;
        return $target;
    }

    /**
     * @return FileLocation
     */
    public function getFileLocation(): FileLocation
    {
        return $this->fileLocation;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->fileLocation->getBaseUrl() . $this->fileName;
    }

    /**
     * @return bool
     */
    public function shallFail(): bool
    {
        return $this->fail;
    }

    /**
     * @return string|null
     */
    public function getExpectedContentType(): ?string
    {
        return $this->expectedContentType;
    }

    /**
     * @return string|null
     */
    public function getUnexpectedContentType(): ?string
    {
        return $this->unexpectedContentType;
    }

    /**
     * @return string|null
     */
    public function getExpectedContent(): ?string
    {
        return $this->expectedContent;
    }

    /**
     * @return string|null
     */
    public function getUnexpectedContent(): ?string
    {
        return $this->unexpectedContent;
    }
}
