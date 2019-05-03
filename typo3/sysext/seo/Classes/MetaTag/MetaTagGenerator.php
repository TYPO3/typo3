<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Seo\MetaTag;

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

use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Frontend\Resource\FileCollector;

/**
 * Class to add the metatags for the SEO fields in core
 *
 * @internal
 */
class MetaTagGenerator
{
    /**
     * Generate the meta tags that can be set in backend and add them to frontend by using the MetaTag API
     *
     * @param array $params
     */
    public function generate(array $params)
    {
        $metaTagManagerRegistry = GeneralUtility::makeInstance(MetaTagManagerRegistry::class);

        if (!empty($params['page']['description'])) {
            $manager = $metaTagManagerRegistry->getManagerForProperty('description');
            $manager->addProperty('description', $params['page']['description']);
        }

        if (!empty($params['page']['og_title'])) {
            $manager = $metaTagManagerRegistry->getManagerForProperty('og:title');
            $manager->addProperty('og:title', $params['page']['og_title']);
        }

        if (!empty($params['page']['og_description'])) {
            $manager = $metaTagManagerRegistry->getManagerForProperty('og:description');
            $manager->addProperty('og:description', $params['page']['og_description']);
        }

        if (!empty($params['page']['og_image'])) {
            $fileCollector = GeneralUtility::makeInstance(FileCollector::class);
            $fileCollector->addFilesFromRelation('pages', 'og_image', $params['page']);
            $manager = $metaTagManagerRegistry->getManagerForProperty('og:image');

            $ogImages = $this->generateSocialImages($fileCollector->getFiles());
            foreach ($ogImages as $ogImage) {
                $subProperties = [];
                $subProperties['url'] = $ogImage['url'];
                $subProperties['width'] = $ogImage['width'];
                $subProperties['height'] = $ogImage['height'];

                if (!empty($ogImage['alternative'])) {
                    $subProperties['alt'] = $ogImage['alternative'];
                }

                $manager->addProperty(
                    'og:image',
                    $ogImage['url'],
                    $subProperties
                );
            }
        }

        /*
         * Set type of twitter card to summary. This value can be overridden by TypoScript or the MetaTag API by
         * using the replace option. In v10 this will be a page property
        */
        $manager = $metaTagManagerRegistry->getManagerForProperty('twitter:card');
        $manager->addProperty('twitter:card', 'summary');

        if (!empty($params['page']['twitter_title'])) {
            $manager = $metaTagManagerRegistry->getManagerForProperty('twitter:title');
            $manager->addProperty('twitter:title', $params['page']['twitter_title']);
        }

        if (!empty($params['page']['twitter_description'])) {
            $manager = $metaTagManagerRegistry->getManagerForProperty('twitter:description');
            $manager->addProperty('twitter:description', $params['page']['twitter_description']);
        }

        if (!empty($params['page']['twitter_image'])) {
            $fileCollector = GeneralUtility::makeInstance(FileCollector::class);
            $fileCollector->addFilesFromRelation('pages', 'twitter_image', $params['page']);
            $manager = $metaTagManagerRegistry->getManagerForProperty('twitter:image');

            $twitterImages = $this->generateSocialImages($fileCollector->getFiles());
            foreach ($twitterImages as $twitterImage) {
                $subProperties = [];

                if (!empty($twitterImage['alternative'])) {
                    $subProperties['alt'] = $twitterImage['alternative'];
                }

                $manager->addProperty(
                    'twitter:image',
                    $twitterImage['url'],
                    $subProperties
                );
            }
        }

        $noIndex = ((bool)$params['page']['no_index']) ? 'noindex' : 'index';
        $noFollow = ((bool)$params['page']['no_follow']) ? 'nofollow' : 'follow';

        if ($noIndex === 'noindex' || $noFollow === 'nofollow') {
            $manager = $metaTagManagerRegistry->getManagerForProperty('robots');
            $manager->addProperty('robots', implode(',', [$noIndex, $noFollow]));
        }
    }

    /**
     * @param array $fileReferences
     * @return array
     */
    protected function generateSocialImages(array $fileReferences): array
    {
        $imageService = GeneralUtility::makeInstance(ImageService::class);

        $socialImages = [];

        /** @var FileReference $file */
        foreach ($fileReferences as $file) {
            $arguments = $file->getProperties();
            $cropVariantCollection = CropVariantCollection::create((string)$arguments['crop']);
            $cropVariant = $arguments['cropVariant'] ?: 'social';
            $cropArea = $cropVariantCollection->getCropArea($cropVariant);
            $crop = $cropArea->makeAbsoluteBasedOnFile($file);

            $cropInformation = $crop->asArray();

            $processingConfiguration = [
                'crop' => $crop
            ];

            $processedImage = $file->getOriginalFile()->process(
                ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
                $processingConfiguration
            );

            $imageUri = $imageService->getImageUri($processedImage, true);

            $socialImages[] = [
                'url' => $imageUri,
                'width' => floor($cropInformation['width']),
                'height' => floor($cropInformation['height']),
                'alternative' => $arguments['alternative'],
            ];
        }

        return $socialImages;
    }
}
