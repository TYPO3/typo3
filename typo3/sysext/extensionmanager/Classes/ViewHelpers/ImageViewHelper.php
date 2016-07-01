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

/**
 * Render an img tag for given image src. If $src doesn't exist and
 * $fallbackImage is given check if that file exists and render img tag.
 *
 * If no existing file is found no tag is rendered.
 *
 * = Examples =
 *
 * <code title="Default">
 *     <em:image src="EXT:myext/Resources/Public/typo3_logo.png" alt="alt text" />
 * </code>
 * <output>
 *     <img alt="alt text" src="../typo3conf/ext/myext/Resources/Public/typo3_logo.png" />
 * </output>
 *
 * <code title="non existing image">
 *     <f:image src="NonExistingImage.png" alt="foo" />
 * </code>
 * <output>
 * </output>
 *
 * @internal
 */
class ImageViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
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
        $this->registerArgument('src', 'string', '', true);
        $this->registerArgument('width', 'int', 'width of the image');
        $this->registerArgument('height', 'int', 'height of the image');
        $this->registerArgument('fallbackImage', 'string', 'an optional fallback image if the $src image cannot be loaded', false, '');
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('alt', 'string', 'Specifies an alternate text for an image', false);
    }

    /**
     * Resizes a given image (if required) and renders the respective img tag
     *
     * @return string rendered tag.
     */
    public function render()
    {
        $src = $this->arguments['src'];
        $width = $this->arguments['width'];
        $height = $this->arguments['height'];
        $fallbackImage = $this->arguments['fallbackImage'];

        $content = '';
        $uri = $this->getImageUri($src);

        if (empty($uri) && $fallbackImage !== '') {
            $uri = $this->getImageUri($fallbackImage);
        }

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
