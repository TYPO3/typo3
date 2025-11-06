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

namespace TYPO3\CMS\Backend\Controller;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Backend\QrCodeSize;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\MimeTypeDetector;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal This is a concrete controller implementation and is not part of TYPO3 Core API.
 */
#[AsController]
readonly class QrCodeController
{
    public function __construct(
        protected ResponseFactoryInterface $responseFactory,
        protected StreamFactoryInterface $streamFactory,
        protected MimeTypeDetector $mimeTypeDetector,
        protected GraphicalFunctions $graphicalFunctions,
    ) {}

    public function getQrCodeAction(ServerRequestInterface $request): ResponseInterface
    {
        $content = $request->getQueryParams()['content'] ?? '';
        if ($content === '') {
            throw new \InvalidArgumentException('Content of the QR Code cannot be empty', 1762811499);
        }

        $size = QrCodeSize::tryFrom((string)($request->getQueryParams()['size'] ?? '')) ?? QrCodeSize::MEDIUM;
        $svg = $this->getQrCodeSvg($content, $size);
        $response = $this->responseFactory->createResponse();

        return $response
            ->withHeader('Content-Type', 'image/svg+xml')
            ->withBody($this->streamFactory->createStream($svg));
    }

    public function downloadAction(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $content = (string)($body['content'] ?? '');
        if ($content === '') {
            throw new \InvalidArgumentException('Content of the QR Code cannot be empty', 1762811481);
        }

        $size = QrCodeSize::tryFrom((string)($body['size'] ?? '')) ?? QrCodeSize::SMALL;
        $format = (string)($body['format'] ?? 'svg');
        $svgContent = $this->getQrCodeSvg($content, $size);
        $path = Environment::getVarPath() . '/transient/';
        GeneralUtility::mkdir_deep($path);

        $fileName = $path . 'qrcode-' . $size->getSize() . 'px-' . sha1($svgContent);
        $svgFilePath = $fileName . '.svg';

        if ($format === 'png') {
            $pixelGraphicFilePath = $fileName . '.png';
            $this->createSvg($svgFilePath, $svgContent);
            $filePath = $this->convertTo($svgFilePath, $pixelGraphicFilePath, 'png', $size);
        } elseif ($format === 'svg') {
            $filePath = $this->createSvg($svgFilePath, $svgContent);
        } else {
            throw new \InvalidArgumentException('The suffix "' . $format . '" is not supported.', 1762718268);
        }

        return $this->sendFile($filePath, $format);
    }

    /**
     * Send file to the browser to download
     */
    private function sendFile(string $filePath, string $format = 'svg'): ResponseInterface
    {
        $mimeType = $this->mimeTypeDetector->getMimeTypesForFileExtension($format);
        $response = $this->responseFactory->createResponse();
        $fileContent = file_get_contents($filePath);
        $response->getBody()->write($fileContent);

        return $response
            ->withHeader('Content-Type', $mimeType[0] ?? 'image/svg+xml')
            ->withHeader('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"')
            ->withHeader('Content-Length', (string)strlen($fileContent));
    }

    private function getQrCodeSvg(string $content, QrCodeSize $size = QrCodeSize::MEDIUM): string
    {
        $qrCodeRenderer = new ImageRenderer(new RendererStyle($size->getSize(), 2), new SvgImageBackEnd());

        return (new Writer($qrCodeRenderer))->writeString($content);
    }

    private function createSvg(string $file, string $content): string
    {
        if (file_exists($file)) {
            return $file;
        }

        if (!GeneralUtility::writeFile($file, $content, true)) {
            throw new \RuntimeException('Unable to write file ' . $file, 1762718307);
        }

        return $file;
    }

    /**
     * Create a pixel based image file from a given SVG file
     */
    private function convertTo(string $sourcePath, string $targetPath, string $format, QrCodeSize $size = QrCodeSize::SMALL): string
    {
        if (file_exists($targetPath)) {
            return $targetPath;
        }

        $result = $this->graphicalFunctions->resize(
            $sourcePath,
            $format,
            (string)$size->getSize(),
            (string)$size->getSize(),
            '',
            [],
            true
        );

        if ($result) {
            $pngFile = $result->getRealPath();
            if ($pngFile && is_file($pngFile) && rename($pngFile, $targetPath)) {
                return $targetPath;
            }
        }

        throw new \RuntimeException('Failed create ' . strtoupper($format) . ' ' . $targetPath . ' from SVG ' . $sourcePath . ' file.', 1762718351);
    }
}
