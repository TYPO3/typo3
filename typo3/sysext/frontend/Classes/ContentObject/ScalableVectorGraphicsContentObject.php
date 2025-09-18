<?php

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

namespace TYPO3\CMS\Frontend\ContentObject;

use TYPO3\CMS\Core\SystemResource\Exception\SystemResourceDoesNotExistException;
use TYPO3\CMS\Core\SystemResource\Exception\SystemResourceException;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\SystemResource\Type\PublicResourceInterface;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;

/**
 * Contains SVG content object.
 */
class ScalableVectorGraphicsContentObject extends AbstractContentObject
{
    public function __construct(
        protected readonly SystemResourceFactory $resourceFactory,
        protected readonly SystemResourcePublisherInterface $resourcePublisher,
    ) {}

    /**
     * Rendering the cObject, SVG
     *
     * @param array $conf Array of TypoScript properties
     */
    public function render($conf = []): string
    {
        $renderMode = $this->cObj->stdWrapValue('renderMode', $conf);

        if ($renderMode === 'inline') {
            return $this->renderInline($conf);
        }

        return $this->renderObject($conf);
    }

    protected function renderInline(array $conf): string
    {
        $resource = $this->resolveResource($conf);
        [$width, $height, $isDefaultWidth, $isDefaultHeight] = $this->getDimensions($conf);

        $content = $svgContent = '';
        if ($resource instanceof SystemResourceInterface) {
            try {
                $svgContent = $resource->getContents();
            } catch (SystemResourceDoesNotExistException) {
            }
        }
        if ($svgContent !== '') {
            $svgContent = preg_replace('/<script[\s\S]*?>[\s\S]*?<\/script>/i', '', $svgContent) ?? '';
            $svgElement = simplexml_load_string($svgContent);

            $domXml = dom_import_simplexml($svgElement);
            if (!$isDefaultWidth) {
                $domXml->setAttribute('width', $width);
            }
            if (!$isDefaultHeight) {
                $domXml->setAttribute('height', $height);
            }
            // remove xml version tag
            $content = $domXml->ownerDocument->saveXML($domXml->ownerDocument->documentElement);
        } else {
            $value = $this->cObj->stdWrapValue('value', $conf);
            if (!empty($value)) {
                $content = [];
                $content[] = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="' . (int)$width . '" height="' . (int)$height . '">';
                $content[] = $value;
                $content[] = '</svg>';
                $content = implode(LF, $content);
            }
        }
        if (isset($conf['stdWrap.'])) {
            $content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
        }
        return $content;
    }

    /**
     * Render the SVG as <object> tag
     */
    protected function renderObject(array $conf): string
    {
        $resource = $this->resolveResource($conf);
        [$width, $height] = $this->getDimensions($conf);
        $content = [];
        if ($resource !== null) {
            $uri = $this->resourcePublisher->generateUri($resource, $this->request);
            $content[] = '<!--[if IE]>';
            $content[] = '  <object src="' . htmlspecialchars($uri) . '" classid="image/svg+xml" width="' . (int)$width . '" height="' . (int)$height . '">';
            $content[] = '<![endif]-->';
            $content[] = '<!--[if !IE]>-->';
            $content[] = '  <object data="' . htmlspecialchars($uri) . '" type="image/svg+xml" width="' . (int)$width . '" height="' . (int)$height . '">';
            $content[] = '<!--<![endif]-->';
            $content[] = '</object>';
        }
        $content = implode(LF, $content);
        if (isset($conf['stdWrap.'])) {
            $content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
        }
        return $content;
    }

    protected function resolveResource(array $conf): ?PublicResourceInterface
    {
        try {
            $resourceIdentifier = (string)$this->cObj->stdWrapValue('src', $conf);
            return $this->resourceFactory->createPublicResource($resourceIdentifier);
        } catch (SystemResourceException) {
            return null;
        }
    }

    protected function getDimensions(array $conf): array
    {
        $isDefaultWidth = false;
        $isDefaultHeight = false;
        $width = $this->cObj->stdWrapValue('width', $conf);
        $height = $this->cObj->stdWrapValue('height', $conf);

        if (empty($width)) {
            $isDefaultWidth = true;
            $width = 600;
        }
        if (empty($height)) {
            $isDefaultHeight = true;
            $height = 400;
        }

        return [$width, $height, $isDefaultWidth, $isDefaultHeight];
    }
}
