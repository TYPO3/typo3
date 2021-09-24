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

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AudioTagRenderer
 */
class AudioTagRenderer implements FileRendererInterface
{
    /**
     * Mime types that can be used in the HTML Video tag
     *
     * @var array
     */
    protected $possibleMimeTypes = ['audio/mpeg', 'audio/wav', 'audio/ogg'];

    /**
     * Returns the priority of the renderer
     * This way it is possible to define/overrule a renderer
     * for a specific file type/context.
     * For example create a video renderer for a certain storage/driver type.
     * Should be between 1 and 100, 100 is more important than 1
     *
     * @return int
     */
    public function getPriority()
    {
        return 1;
    }

    /**
     * Check if given File(Reference) can be rendered
     *
     * @param FileInterface $file File or FileReference to render
     * @return bool
     */
    public function canRender(FileInterface $file)
    {
        return in_array($file->getMimeType(), $this->possibleMimeTypes, true);
    }

    /**
     * Render for given File(Reference) HTML output
     *
     * @param FileInterface $file
     * @param int|string $width TYPO3 known format; examples: 220, 200m or 200c
     * @param int|string $height TYPO3 known format; examples: 220, 200m or 200c
     * @param array $options controls = TRUE/FALSE (default TRUE), autoplay = TRUE/FALSE (default FALSE), loop = TRUE/FALSE (default FALSE)
     * @param bool $usedPathsRelativeToCurrentScript See $file->getPublicUrl()
     * @return string
     */
    public function render(FileInterface $file, $width, $height, array $options = [], $usedPathsRelativeToCurrentScript = false)
    {
        // TODO $usedPathsRelativeToCurrentScript here as well
        // If autoplay isn't set manually check if $file is a FileReference take autoplay from there
        if (!isset($options['autoplay']) && $file instanceof FileReference) {
            $autoplay = $file->getProperty('autoplay');
            if ($autoplay !== null) {
                $options['autoplay'] = $autoplay;
            }
        }

        $additionalAttributes = [];
        if (isset($options['additionalAttributes']) && is_array($options['additionalAttributes'])) {
            $additionalAttributes[] = GeneralUtility::implodeAttributes($options['additionalAttributes'], true, true);
        }
        if (isset($options['data']) && is_array($options['data'])) {
            array_walk($options['data'], static function (&$value, $key) {
                $value = 'data-' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
            });
            $additionalAttributes[] = implode(' ', $options['data']);
        }
        if (!isset($options['controls']) || !empty($options['controls'])) {
            $additionalAttributes[] = 'controls';
        }
        if (!empty($options['autoplay'])) {
            $additionalAttributes[] = 'autoplay';
        }
        if (!empty($options['muted'])) {
            $additionalAttributes[] = 'muted';
        }
        if (!empty($options['loop'])) {
            $additionalAttributes[] = 'loop';
        }
        foreach (['class', 'dir', 'id', 'lang', 'style', 'title', 'accesskey', 'tabindex', 'onclick', 'preload', 'controlsList'] as $key) {
            if (!empty($options[$key])) {
                $additionalAttributes[] = $key . '="' . htmlspecialchars($options[$key]) . '"';
            }
        }

        return sprintf(
            '<audio%s><source src="%s" type="%s"></audio>',
            empty($additionalAttributes) ? '' : ' ' . implode(' ', $additionalAttributes),
            htmlspecialchars((string)$file->getPublicUrl()),
            $file->getMimeType()
        );
    }
}
