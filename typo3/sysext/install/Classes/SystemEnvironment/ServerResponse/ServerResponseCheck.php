<?php

declare(strict_types = 1);

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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use function GuzzleHttp\Promise\settle;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\SystemEnvironment\CheckInterface;
use TYPO3\CMS\Reports\Status;

/**
 * Checks how use web server is interpreting static files concerning
 * their `content-type` and evaluated content in HTTP responses.
 *
 * @internal should only be used from within TYPO3 Core
 */
class ServerResponseCheck implements CheckInterface
{
    protected const WRAP_FLAT = 1;
    protected const WRAP_NESTED = 2;

    /**
     * @var bool
     */
    protected $useMarkup;

    /**
     * @var FlashMessageQueue
     */
    protected $messageQueue;

    /**
     * @var FileLocation
     */
    protected $assetLocation;

    /**
     * @var FileLocation
     */
    protected $fileadminLocation;

    /**
     * @var FileDeclaration[]
     */
    protected $fileDeclarations;

    public function __construct(bool $useMarkup = true)
    {
        $this->useMarkup = $useMarkup;

        $fileName = bin2hex(random_bytes(4));
        $folderName = bin2hex(random_bytes(4));
        $this->assetLocation = new FileLocation(sprintf('/typo3temp/assets/%s.tmp/', $folderName));
        $fileadminDir = rtrim($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] ?? 'fileadmin', '/');
        $this->fileadminLocation = new FileLocation(sprintf('/%s/%s.tmp/', $fileadminDir, $folderName));
        $this->fileDeclarations = $this->initializeFileDeclarations($fileName);
    }

    public function asStatus(): Status
    {
        $messageQueue = $this->getStatus();
        $messages = [];
        foreach ($messageQueue->getAllMessages() as $flashMessage) {
            $messages[] = $flashMessage->getMessage();
        }
        $detailsLink = sprintf(
            '<p><a href="%s" rel="noreferrer" target="_blank">%s</a></p>',
            'https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/9.5.x/Feature-91354-IntegrateServerResponseSecurityChecks.html',
            'Please see documentation for further details...'
        );
        if ($messageQueue->getAllMessages(FlashMessage::ERROR) !== []) {
            $title = 'Potential vulnerabilities';
            $label = $detailsLink;
            $severity = Status::ERROR;
        } elseif ($messageQueue->getAllMessages(FlashMessage::WARNING) !== []) {
            $title = 'Warnings';
            $label = $detailsLink;
            $severity = Status::WARNING;
        }
        return new Status(
            'Server Response on static files',
            $title ?? 'OK',
            $this->wrapList($messages, $label ?? '', self::WRAP_NESTED),
            $severity ?? Status::OK
        );
    }

    public function getStatus(): FlashMessageQueue
    {
        $messageQueue = new FlashMessageQueue('install-server-response-check');
        if (PHP_SAPI === 'cli-server') {
            $messageQueue->addMessage(
                new FlashMessage(
                    'Skipped for PHP_SAPI=cli-server',
                    'Checks skipped',
                    FlashMessage::WARNING
                )
            );
            return $messageQueue;
        }
        try {
            $this->buildFileDeclarations();
            $this->processFileDeclarations($messageQueue);
            $this->finishMessageQueue($messageQueue);
        } finally {
            $this->purgeFileDeclarations();
        }
        return $messageQueue;
    }

    protected function initializeFileDeclarations(string $fileName): array
    {
        $cspClosure = function (ResponseInterface $response): ?StatusMessage {
            $cspHeader = new ContentSecurityPolicyHeader(
                $response->getHeaderLine('content-security-policy')
            );

            if ($cspHeader->isEmpty()) {
                return new StatusMessage(
                    'missing Content-Security-Policy for this location'
                );
            }
            if (!$cspHeader->mitigatesCrossSiteScripting()) {
                return new StatusMessage(
                    'weak Content-Security-Policy for this location "%s"',
                    $response->getHeaderLine('content-security-policy')
                );
            }
            return null;
        };

        return [
            (new FileDeclaration($this->assetLocation, $fileName . '.html'))
                ->withExpectedContentType('text/html')
                ->withExpectedContent('HTML content'),
            (new FileDeclaration($this->assetLocation, $fileName . '.wrong'))
                ->withUnexpectedContentType('text/html')
                ->withExpectedContent('HTML content'),
            (new FileDeclaration($this->assetLocation, $fileName . '.html.wrong'))
                ->withUnexpectedContentType('text/html')
                ->withExpectedContent('HTML content'),
            (new FileDeclaration($this->assetLocation, $fileName . '.1.svg.wrong'))
                ->withBuildFlags(FileDeclaration::FLAG_BUILD_SVG | FileDeclaration::FLAG_BUILD_SVG_DOCUMENT)
                ->withUnexpectedContentType('image/svg+xml')
                ->withExpectedContent('SVG content'),
            (new FileDeclaration($this->assetLocation, $fileName . '.2.svg.wrong'))
                ->withBuildFlags(FileDeclaration::FLAG_BUILD_SVG | FileDeclaration::FLAG_BUILD_SVG_DOCUMENT)
                ->withUnexpectedContentType('image/svg')
                ->withExpectedContent('SVG content'),
            (new FileDeclaration($this->assetLocation, $fileName . '.php.wrong', true))
                ->withBuildFlags(FileDeclaration::FLAG_BUILD_PHP | FileDeclaration::FLAG_BUILD_HTML_DOCUMENT)
                ->withUnexpectedContent('PHP content'),
            (new FileDeclaration($this->assetLocation, $fileName . '.html.txt'))
                ->withExpectedContentType('text/plain')
                ->withUnexpectedContentType('text/html')
                ->withExpectedContent('HTML content'),
            (new FileDeclaration($this->assetLocation, $fileName . '.php.txt', true))
                ->withBuildFlags(FileDeclaration::FLAG_BUILD_PHP | FileDeclaration::FLAG_BUILD_HTML_DOCUMENT)
                ->withUnexpectedContent('PHP content'),
            (new FileDeclaration($this->fileadminLocation, $fileName . '.html'))
                ->withBuildFlags(FileDeclaration::FLAG_BUILD_HTML_DOCUMENT)
                ->withHandler($cspClosure),
            (new FileDeclaration($this->fileadminLocation, $fileName . '.svg'))
                ->withBuildFlags(FileDeclaration::FLAG_BUILD_SVG | FileDeclaration::FLAG_BUILD_SVG_DOCUMENT)
                ->withHandler($cspClosure),
        ];
    }

    protected function buildFileDeclarations(): void
    {
        foreach ($this->fileDeclarations as $fileDeclaration) {
            $filePath = $fileDeclaration->getFileLocation()->getFilePath();
            if (!is_dir($filePath)) {
                GeneralUtility::mkdir_deep($filePath);
            }
            file_put_contents(
                $filePath . $fileDeclaration->getFileName(),
                $fileDeclaration->buildContent()
            );
        }
    }

    protected function purgeFileDeclarations(): void
    {
        GeneralUtility::rmdir($this->assetLocation->getFilePath(), true);
        GeneralUtility::rmdir($this->fileadminLocation->getFilePath(), true);
    }

    protected function processFileDeclarations(FlashMessageQueue $messageQueue): void
    {
        $promises = [];
        $client = new Client(['timeout' => 10]);
        foreach ($this->fileDeclarations as $fileDeclaration) {
            $promises[] = $client->requestAsync('GET', $fileDeclaration->getUrl());
        }
        foreach (settle($promises)->wait() as $index => $response) {
            $fileDeclaration = $this->fileDeclarations[$index];
            if (($response['reason'] ?? null) instanceof BadResponseException) {
                $messageQueue->addMessage(
                    new FlashMessage(
                        sprintf(
                            '(%d): %s',
                            $response['reason']->getCode(),
                            $response['reason']->getRequest()->getUri()
                        ),
                        'HTTP warning',
                        FlashMessage::WARNING
                    )
                );
                continue;
            }
            if (!($response['value'] ?? null) instanceof ResponseInterface || $fileDeclaration->matches($response['value'])) {
                continue;
            }
            $messageQueue->addMessage(
                new FlashMessage(
                    $this->createMismatchMessage($fileDeclaration, $response['value']),
                    'Unexpected server response',
                    $fileDeclaration->shallFail() ? FlashMessage::ERROR : FlashMessage::WARNING
                )
            );
        }
    }

    protected function finishMessageQueue(FlashMessageQueue $messageQueue): void
    {
        if ($messageQueue->getAllMessages(FlashMessage::WARNING) !== []
            || $messageQueue->getAllMessages(FlashMessage::ERROR) !== []) {
            return;
        }
        $messageQueue->addMessage(
            new FlashMessage(
                sprintf('All %d files processed correctly', count($this->fileDeclarations)),
                'Expected server response',
                FlashMessage::OK
            )
        );
    }

    protected function createMismatchMessage(FileDeclaration $fileDeclaration, ResponseInterface $response): string
    {
        $messageParts = array_map(
            function (StatusMessage $mismatch): string {
                return vsprintf(
                    $mismatch->getMessage(),
                    $this->wrapValues($mismatch->getValues(), '<code>', '</code>')
                );
            },
            $fileDeclaration->getMismatches($response)
        );
        return $this->wrapList($messageParts, $fileDeclaration->getUrl(), self::WRAP_FLAT);
    }

    protected function wrapList(array $items, string $label, int $style): string
    {
        if (!$this->useMarkup) {
            return sprintf(
                '%s%s',
                $label ? $label . ': ' : '',
                implode(', ', $items)
            );
        }
        if ($style === self::WRAP_NESTED) {
            return sprintf(
                '%s<ul>%s</ul>',
                $label,
                implode('', $this->wrapItems($items, '<li>', '</li>'))
            );
        }
        return sprintf(
            '<p>%s%s</p>',
            $label,
            implode('', $this->wrapItems($items, '<br>', ''))
        );
    }

    protected function wrapItems(array $items, string $before, string $after): array
    {
        return array_map(
            function (string $item) use ($before, $after): string {
                return $before . $item . $after;
            },
            array_filter($items)
        );
    }

    protected function wrapValues(array $values, string $before, string $after): array
    {
        return array_map(
            function (string $value) use ($before, $after): string {
                return $this->wrapValue($value, $before, $after);
            },
            array_filter($values)
        );
    }

    protected function wrapValue(string $value, string $before, string $after): string
    {
        if ($this->useMarkup) {
            return $before . htmlspecialchars($value) . $after;
        }
        return $value;
    }
}
