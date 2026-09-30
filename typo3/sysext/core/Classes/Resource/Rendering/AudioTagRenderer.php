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

namespace TYPO3\CMS\Core\Resource\Rendering;

use TYPO3\CMS\Core\Attribute\AsFileRenderer;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsFileRenderer]
class AudioTagRenderer implements FileRendererInterface
{
    /**
     * Mime types that can be used in the HTML Video tag
     *
     * @var array
     */
    protected $possibleMimeTypes = ['audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/ogg'];

    /**
     * Check if given File(Reference) can be rendered
     *
     * @param FileInterface $file File or FileReference to render
     */
    public function canRender(FileInterface $file): bool
    {
        $mimeType = strtolower(trim(explode(';', $file->getMimeType(), 2)[0]));
        return in_array($mimeType, $this->possibleMimeTypes, true);
    }

    /**
     * Render for given File(Reference) HTML output
     *
     * @param int|string $width TYPO3 known format; examples: 220, 200m or 200c
     * @param int|string $height TYPO3 known format; examples: 220, 200m or 200c
     * @param array $options controls = TRUE/FALSE (default TRUE), autoplay = TRUE/FALSE (default FALSE), loop = TRUE/FALSE (default FALSE)
     */
    public function render(FileInterface $file, int|string $width, int|string $height, array $options = []): string
    {
        // If autoplay isn't set manually check if $file is a FileReference take autoplay from there
        if (!isset($options['autoplay']) && $file instanceof FileReference) {
            $autoplay = $file->getProperty('autoplay');
            if ($autoplay !== null) {
                $options['autoplay'] = $autoplay;
            }
        }

        $attributes = [];
        if (isset($options['additionalAttributes']) && is_array($options['additionalAttributes'])) {
            $attributes = $options['additionalAttributes'];
        }
        if (isset($options['data']) && is_array($options['data'])) {
            foreach ($options['data'] as $key => $value) {
                $attributes['data-' . $key] ??= $value;
            }
        }
        if (!isset($options['controls']) || !empty($options['controls'])) {
            $attributes['controls'] ??= true;
        }
        if (!empty($options['autoplay'])) {
            $attributes['autoplay'] ??= true;
        }
        if (!empty($options['muted'])) {
            $attributes['muted'] ??= true;
        }
        if (!empty($options['loop'])) {
            $attributes['loop'] ??= true;
        }
        foreach (['class', 'dir', 'id', 'lang', 'style', 'title', 'accesskey', 'tabindex', 'onclick', 'preload', 'controlsList'] as $key) {
            if (!empty($options[$key])) {
                $attributes[$key] ??= $options[$key];
            }
        }

        $attributesString = GeneralUtility::implodeAttributes($attributes, false, true, true);

        return sprintf(
            '<audio%s><source src="%s" type="%s"></audio>',
            $attributesString === '' ? '' : ' ' . $attributesString,
            htmlspecialchars((string)$file->getPublicUrl()),
            $file->getMimeType()
        );
    }
}
