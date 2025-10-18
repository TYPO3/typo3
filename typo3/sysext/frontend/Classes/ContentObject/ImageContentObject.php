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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\Event\ModifyImageSourceCollectionEvent;
use TYPO3\CMS\Frontend\Page\FrontendUrlPrefix;

/**
 * Contains IMAGE class object.
 */
class ImageContentObject extends AbstractContentObject
{
    public function __construct(
        protected readonly MarkerBasedTemplateService $markerTemplateService,
    ) {}

    /**
     * Rendering the cObject, IMAGE
     *
     * @param array|mixed $conf Array of TypoScript properties
     * @return string Output
     */
    public function render($conf = [])
    {
        if (!empty($conf['if.']) && !$this->cObj->checkIf($conf['if.'])) {
            return '';
        }

        $theValue = $this->cImage($conf['file'] ?? '', is_array($conf) ? $conf : []);
        if (isset($conf['stdWrap.'])) {
            $theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
        }
        return $theValue;
    }

    /**
     * Returns a <img> tag with the image file defined by $file and processed according to the properties in the TypoScript array.
     * Mostly this function is a sub-function to the IMAGE function which renders the IMAGE cObject in TypoScript.
     *
     * @param string|File|FileReference|null $file File TypoScript resource
     * @param array $conf TypoScript configuration properties
     * @return string HTML <img> tag, (possibly wrapped in links and other HTML) if any image found.
     */
    protected function cImage($file, array $conf): string
    {
        $imageResource = $this->cObj->getImgResource($file, $conf['file.'] ?? []);
        if ($imageResource === null) {
            return '';
        }
        // $info['originalFile'] will be set, when the file is processed by FAL.
        // In that case the URL is final and we must not add a prefix
        if ($imageResource->getOriginalFile() === null && is_file($imageResource->getFullPath())) {
            $absRefPrefix = GeneralUtility::makeInstance(FrontendUrlPrefix::class)->getUrlPrefix($this->request);
            $source = $absRefPrefix . str_replace('%2F', '/', rawurlencode($imageResource->getPublicUrl()));
        } else {
            $source = $imageResource->getPublicUrl();
        }
        GeneralUtility::makeInstance(AssetCollector::class)->addMedia(
            $source,
            $imageResource->getLegacyImageResourceInformation()
        );

        $layoutKey = (string)$this->cObj->stdWrapValue('layoutKey', $conf);
        $imageTagTemplate = $this->getImageTagTemplate($layoutKey, $conf);
        $sourceCollection = $this->getImageSourceCollection($layoutKey, $conf, $file);

        $altParam = $this->getAltParam($conf);
        $params = $this->cObj->stdWrapValue('params', $conf);
        if ($params !== '' && $params[0] !== ' ') {
            $params = ' ' . $params;
        }

        $imageTagValues = [
            'width' =>  $imageResource->getWidth(),
            'height' => $imageResource->getHeight(),
            'src' => htmlspecialchars($source),
            'params' => $params,
            'altParams' => $altParam,
            'sourceCollection' => $sourceCollection,
            'selfClosingTagSlash' => $this->getPageRenderer()->getDocType()->isXmlCompliant() ? ' /' : '',
        ];

        $theValue = $this->markerTemplateService->substituteMarkerArray($imageTagTemplate, $imageTagValues, '###|###', true, true);

        $linkWrap = (string)$this->cObj->stdWrapValue('linkWrap', $conf);
        if ($linkWrap !== '') {
            $theValue = $this->linkWrap($theValue, $linkWrap);
        } elseif ($conf['imageLinkWrap'] ?? false) {
            $originalFile = urldecode($imageResource->getFullPath());
            $theValue = $this->cObj->imageLinkWrap($theValue, $originalFile, $conf['imageLinkWrap.']);
        }
        $wrap = $this->cObj->stdWrapValue('wrap', $conf);
        if ((string)$wrap !== '') {
            $theValue = $this->cObj->wrap($theValue, $conf['wrap']);
        }
        return $theValue;
    }

    /**
     * Returns the html-template for rendering the image-Tag if no template is defined via typoscript the
     * default <img> tag template is returned
     *
     * @param string $layoutKey rendering key
     * @param array $conf TypoScript configuration properties
     */
    protected function getImageTagTemplate($layoutKey, $conf): string
    {
        if ($layoutKey && isset($conf['layout.']) && isset($conf['layout.'][$layoutKey . '.'])) {
            return $this->cObj->stdWrapValue('element', $conf['layout.'][$layoutKey . '.']);
        }
        return '<img src="###SRC###" width="###WIDTH###" height="###HEIGHT###" ###PARAMS### ###ALTPARAMS### ###SELFCLOSINGTAGSLASH###>';
    }

    /**
     * Render alternate sources for the image tag. If no source collection is given an empty string is returned.
     *
     * @param string $layoutKey rendering key
     * @param array $conf TypoScript configuration properties
     * @param string|File|FileReference|null $file
     * @return string
     */
    protected function getImageSourceCollection(string $layoutKey, array $conf, $file)
    {
        $sourceCollection = '';
        if ($layoutKey
            && isset($conf['sourceCollection.']) && $conf['sourceCollection.']
            && (
                isset($conf['layout.'][$layoutKey . '.']['source']) && $conf['layout.'][$layoutKey . '.']['source']
                || isset($conf['layout.'][$layoutKey . '.']['source.']) && $conf['layout.'][$layoutKey . '.']['source.']
            )
        ) {
            // find active sourceCollection
            $activeSourceCollections = [];
            foreach ($conf['sourceCollection.'] as $sourceCollectionKey => $sourceCollectionConfiguration) {
                if (str_ends_with($sourceCollectionKey, '.')) {
                    if (empty($sourceCollectionConfiguration['if.']) || $this->cObj->checkIf($sourceCollectionConfiguration['if.'])) {
                        $activeSourceCollections[] = $sourceCollectionConfiguration;
                    }
                }
            }

            // apply option split to configurations
            $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
            $srcLayoutOptionSplitted = $typoScriptService->explodeConfigurationForOptionSplit((array)$conf['layout.'][$layoutKey . '.'], count($activeSourceCollections));
            $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);

            // render sources
            foreach ($activeSourceCollections as $key => $sourceConfiguration) {
                $sourceLayout = $this->cObj->stdWrapValue('source', $srcLayoutOptionSplitted[$key] ?? []);

                $sourceRenderConfiguration = [
                    'file' => $file,
                    'file.' => $conf['file.'] ?? null,
                ];

                $imageQuality = $this->cObj->stdWrapValue('quality', $sourceConfiguration ?? []);
                if ($imageQuality) {
                    $sourceRenderConfiguration['file.']['params'] = '-quality ' . (int)$imageQuality;
                }

                $pixelDensity = (int)$this->cObj->stdWrapValue('pixelDensity', $sourceConfiguration, 1);
                $dimensionKeys = ['width', 'height', 'maxW', 'minW', 'maxH', 'minH', 'maxWidth', 'maxHeight', 'XY'];
                foreach ($dimensionKeys as $dimensionKey) {
                    $dimension = (string)$this->cObj->stdWrapValue($dimensionKey, $sourceConfiguration);
                    if ($dimension === '') {
                        $dimension = (string)$this->cObj->stdWrapValue($dimensionKey, $conf['file.'] ?? []);
                    }
                    if ($dimension !== '') {
                        if (str_contains($dimension, 'c') && ($dimensionKey === 'width' || $dimensionKey === 'height')) {
                            $dimensionParts = explode('c', $dimension, 2);
                            $dimension = ((int)$dimensionParts[0] * $pixelDensity) . 'c';
                            if ($dimensionParts[1]) {
                                $dimension .= $dimensionParts[1];
                            }
                        } elseif ($dimensionKey === 'XY') {
                            $dimensionParts = GeneralUtility::intExplode(',', $dimension);
                            $dimension = $dimensionParts[0] * $pixelDensity;
                            if ($dimensionParts[1]) {
                                $dimension .= ',' . $dimensionParts[1] * $pixelDensity;
                            }
                        } else {
                            $dimension = (int)$dimension * $pixelDensity;
                        }
                        $sourceRenderConfiguration['file.'][$dimensionKey] = $dimension;
                        // Remove the stdWrap properties for dimension as they have been processed already above.
                        unset($sourceRenderConfiguration['file.'][$dimensionKey . '.']);
                    }
                }
                $imageResource = $this->cObj->getImgResource($sourceRenderConfiguration['file'], $sourceRenderConfiguration['file.']);
                if ($imageResource !== null) {
                    $sourceConfiguration['width'] = $imageResource->getWidth();
                    $sourceConfiguration['height'] = $imageResource->getHeight();

                    $urlPrefix = '';
                    // Prepend 'absRefPrefix' to file path only if file was not processed by FAL, e.g. GIFBUILDER
                    if ($imageResource->getOriginalFile() === null && is_file($imageResource->getFullPath())) {
                        $urlPrefix = GeneralUtility::makeInstance(FrontendUrlPrefix::class)->getUrlPrefix($this->request);
                    }

                    $sourceConfiguration['src'] = htmlspecialchars($urlPrefix . $imageResource->getPublicUrl());
                    $sourceConfiguration['selfClosingTagSlash'] = $this->getPageRenderer()->getDocType()->isXmlCompliant() ? ' /' : '';

                    $oneSourceCollection = $this->markerTemplateService->substituteMarkerArray($sourceLayout, $sourceConfiguration, '###|###', true, true);

                    $sourceCollection .= $eventDispatcher->dispatch(
                        new ModifyImageSourceCollectionEvent($oneSourceCollection, $sourceCollection, (array)$sourceConfiguration, $sourceRenderConfiguration, $this->cObj)
                    )->getSourceCollection();
                }
            }
        }
        return $sourceCollection;
    }

    /**
     * Wraps the input string by the $wrap value and implements the "linkWrap" data type as well.
     *
     * The "linkWrap" data type means that this function will find any integer encapsulated
     * in {} (curly braces) in the first wrap part and substitute it with the corresponding page
     * uid from the rootline where the found integer is pointing to the key in the rootline.
     *
     * @param string $content Input string
     * @param string $wrap A string where the first two parts separated by "|" (vertical line) will be wrapped around the input string
     */
    protected function linkWrap(string $content, string $wrap): string
    {
        $wrapArr = explode('|', $wrap);
        if (preg_match('/\\{([0-9]*)\\}/', $wrapArr[0], $reg)) {
            $localRootLine = $this->request->getAttribute('frontend.page.information')->getLocalRootLine();
            $uid = $localRootLine[$reg[1]]['uid'] ?? null;
            if ($uid) {
                $wrapArr[0] = str_replace($reg[0], $uid, $wrapArr[0]);
            }
        }
        return trim($wrapArr[0]) . $content . trim($wrapArr[1] ?? '');
    }

    /**
     * An abstraction method which creates an alt or title parameter for an HTML img, applet, area or input element and the FILE content element.
     * From the $conf array it implements the properties "altText" and "titleText"
     *
     * @param array $conf TypoScript configuration properties
     * @return string Parameter string containing alt and title parameters (if any)
     */
    protected function getAltParam(array $conf): string
    {
        $altText = trim((string)$this->cObj->stdWrapValue('altText', $conf));
        $titleText = trim((string)$this->cObj->stdWrapValue('titleText', $conf));

        // "alt":
        $altParam = ' alt="' . htmlspecialchars($altText) . '"';
        // "title":
        $emptyTitleHandling = $this->cObj->stdWrapValue('emptyTitleHandling', $conf);
        // Choices: 'keepEmpty' | 'useAlt' | 'removeAttr'
        if ($titleText || $emptyTitleHandling === 'keepEmpty') {
            $altParam .= ' title="' . htmlspecialchars($titleText) . '"';
        } elseif (!$titleText && $emptyTitleHandling === 'useAlt') {
            $altParam .= ' title="' . htmlspecialchars($altText) . '"';
        }
        return $altParam;
    }
}
