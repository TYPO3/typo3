<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Renders the distribution image
 *
 * @internal
 */
class DistributionImageViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'img';

    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('extensionkey', 'string', '', true);
        $this->registerArgument('width', 'int', 'width of the image');
        $this->registerArgument('height', 'int', 'height of the image');
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('alt', 'string', 'Specifies an alternate text for an image', false);
    }

    /**
     * Renders the destribution preview image for the given extension
     *
     * @return string rendered tag.
     */
    public function render()
    {
        $extensionKey = $this->arguments['extensionkey'];
        $width = $this->arguments['width'];
        $height = $this->arguments['height'];
        $content = '';

        $src = $this->findImage($extensionKey);
        $uri = $this->getImageUri($src);

        if (!empty($uri)) {
            if ($width) {
                $this->tag->addAttribute('width', (int)$width);
            }
            if ($height) {
                $this->tag->addAttribute('height', (int)$height);
            }
            $this->tag->addAttribute('src', $uri);
            $content = $this->tag->render();
        }

        return $content;
    }

    /**
     * Find the distrubution image
     *
     * @param string $extensionKey
     * @return string
     */
    protected function findImage($extensionKey)
    {
        $paths = [];
        $paths[] = 'EXT:' . $extensionKey . '/Resources/Public/Images/Distribution.svg';
        $paths[] = 'EXT:' . $extensionKey . '/Resources/Public/Images/Distribution.png';
        $paths[] = 'EXT:extensionmanager/Resources/Public/Images/Distribution.svg';

        foreach ($paths as $path) {
            $absFileName = GeneralUtility::getFileAbsFileName($path);
            if (file_exists($absFileName)) {
                return $absFileName;
            }
        }

        return '';
    }

    /**
     * Get image uri
     *
     * @param string $src
     * @return string
     */
    protected function getImageUri($src)
    {
        $uri = GeneralUtility::getFileAbsFileName($src);
        if ($uri !== '' && !file_exists($uri)) {
            $uri = '';
        }
        if ($uri !== '') {
            $uri = '../' . PathUtility::stripPathSitePrefix($uri);
        }
        return $uri;
    }
}
