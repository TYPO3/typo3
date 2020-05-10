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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use function GuzzleHttp\Promise\settle;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
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
    /**
     * @var bool
     */
    protected $useMarkup;

    /**
     * @var FlashMessageQueue
     */
    protected $messageQueue;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var FileDeclaration[]
     */
    protected $fileDeclarations;

    public function __construct(bool $useMarkup = true)
    {
        $this->useMarkup = $useMarkup;

        $fileName = bin2hex(random_bytes(4));
        $folderName = bin2hex(random_bytes(4));
        $this->filePath = Environment::getPublicPath()
            . sprintf('/typo3temp/assets/%s.tmp/', $folderName);
        $this->baseUrl = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST')
            . PathUtility::getAbsoluteWebPath($this->filePath);
        $this->fileDeclarations = $this->initializeFileDeclarations($fileName);
    }

    public function asStatus(): Status
    {
        $messageQueue = $this->getStatus();
        $messages = [];
        foreach ($messageQueue->getAllMessages() as $flashMessage) {
            $messages[] = $flashMessage->getMessage();
        }
        if ($messageQueue->getAllMessages(FlashMessage::ERROR) !== []) {
            $title = 'Potential vulnerabilities';
            $severity = Status::ERROR;
        } elseif ($messageQueue->getAllMessages(FlashMessage::WARNING) !== []) {
            $title = 'Warnings';
            $severity = Status::WARNING;
        }
        return new Status(
            'Server Response on static files',
            $title ?? 'OK',
            $this->wrapList($messages),
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
        return [
            (new FileDeclaration($fileName . '.html'))
                ->withExpectedContentType('text/html')
                ->withExpectedContent('HTML content'),
            (new FileDeclaration($fileName . '.wrong'))
                ->withUnexpectedContentType('text/html')
                ->withExpectedContent('HTML content'),
            (new FileDeclaration($fileName . '.html.wrong'))
                ->withUnexpectedContentType('text/html')
                ->withExpectedContent('HTML content'),
            (new FileDeclaration($fileName . '.1.svg.wrong'))
                ->withBuildFlags(FileDeclaration::FLAG_BUILD_SVG | FileDeclaration::FLAG_BUILD_SVG_DOCUMENT)
                ->withUnexpectedContentType('image/svg+xml')
                ->withExpectedContent('SVG content'),
            (new FileDeclaration($fileName . '.2.svg.wrong'))
                ->withBuildFlags(FileDeclaration::FLAG_BUILD_SVG | FileDeclaration::FLAG_BUILD_SVG_DOCUMENT)
                ->withUnexpectedContentType('image/svg')
                ->withExpectedContent('SVG content'),
            (new FileDeclaration($fileName . '.php.wrong', true))
                ->withBuildFlags(FileDeclaration::FLAG_BUILD_PHP | FileDeclaration::FLAG_BUILD_HTML_DOCUMENT)
                ->withUnexpectedContent('PHP content'),
            (new FileDeclaration($fileName . '.html.txt'))
                ->withExpectedContentType('text/plain')
                ->withUnexpectedContentType('text/html')
                ->withExpectedContent('HTML content'),
            (new FileDeclaration($fileName . '.php.txt', true))
                ->withBuildFlags(FileDeclaration::FLAG_BUILD_PHP | FileDeclaration::FLAG_BUILD_HTML_DOCUMENT)
                ->withUnexpectedContent('PHP content'),
        ];
    }

    protected function buildFileDeclarations(): void
    {
        if (!is_dir($this->filePath)) {
            GeneralUtility::mkdir_deep($this->filePath);
        }
        foreach ($this->fileDeclarations as $fileDeclaration) {
            file_put_contents(
                $this->filePath . $fileDeclaration->getFileName(),
                $fileDeclaration->buildContent()
            );
        }
    }

    protected function purgeFileDeclarations(): void
    {
        GeneralUtility::rmdir($this->filePath, true);
    }

    protected function processFileDeclarations(FlashMessageQueue $messageQueue): void
    {
        $promises = [];
        $client = new Client(['base_uri' => $this->baseUrl]);
        foreach ($this->fileDeclarations as $fileDeclaration) {
            $promises[] = $client->requestAsync('GET', $fileDeclaration->getFileName());
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
        $messageParts = [];
        $mismatches = $fileDeclaration->getMismatches($response);
        if (in_array(FileDeclaration::MISMATCH_UNEXPECTED_CONTENT_TYPE, $mismatches, true)) {
            $messageParts[] = sprintf(
                'unexpected content-type %s',
                $this->wrapValue(
                    $fileDeclaration->getUnexpectedContentType(),
                    '<code>',
                    '</code>'
                )
            );
        }
        if (in_array(FileDeclaration::MISMATCH_EXPECTED_CONTENT_TYPE, $mismatches, true)) {
            $messageParts[] = sprintf(
                'content-type mismatch %s, got %s',
                $this->wrapValue(
                    $fileDeclaration->getExpectedContent(),
                    '<code>',
                    '</code>'
                ),
                $this->wrapValue(
                    $response->getHeaderLine('content-type'),
                    '<code>',
                    '</code>'
                )
            );
        }
        if (in_array(FileDeclaration::MISMATCH_UNEXPECTED_CONTENT, $mismatches, true)) {
            $messageParts[] = sprintf(
                'unexpected content %s',
                $this->wrapValue(
                    $fileDeclaration->getUnexpectedContent(),
                    '<code>',
                    '</code>'
                )
            );
        }
        if (in_array(FileDeclaration::MISMATCH_EXPECTED_CONTENT, $mismatches, true)) {
            $messageParts[] = sprintf(
                'content mismatch %s',
                $this->wrapValue(
                    $fileDeclaration->getExpectedContent(),
                    '<code>',
                    '</code>'
                )
            );
        }
        return $this->wrapList(
            $messageParts,
            $this->baseUrl . $fileDeclaration->getFileName()
        );
    }

    protected function wrapList(array $items, string $label = ''): string
    {
        if ($this->useMarkup) {
            return sprintf(
                '%s<ul>%s</ul>',
                $label,
                implode('', $this->wrapItems($items, '<li>', '</li>'))
            );
        }
        return sprintf(
            '%s%s',
            $label ? $label . ': ' : '',
            implode(', ', $items)
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

    protected function wrapValue(string $value, string $before, string $after): string
    {
        if ($this->useMarkup) {
            return $before . htmlspecialchars($value) . $after;
        }
        return $value;
    }
}
