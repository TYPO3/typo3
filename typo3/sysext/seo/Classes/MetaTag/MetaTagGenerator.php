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

namespace TYPO3\CMS\Seo\MetaTag;

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
    public function __construct(
        protected MetaTagManagerRegistry $metaTagManagerRegistry,
        protected ImageService $imageService
    ) {
    }

    /**
     * Generate the meta tags that can be set in backend and add them to frontend by using the MetaTag API
     *
     * @param array $params
     */
    public function generate(array $params)
    {
        if (!empty($params['page']['description'])) {
            $manager = $this->metaTagManagerRegistry->getManagerForProperty('description');
            $manager->addProperty('description', $params['page']['description']);
        }

        if (!empty($params['page']['og_title'])) {
            $manager = $this->metaTagManagerRegistry->getManagerForProperty('og:title');
            $manager->addProperty('og:title', $params['page']['og_title']);
        }

        if (!empty($params['page']['og_description'])) {
            $manager = $this->metaTagManagerRegistry->getManagerForProperty('og:description');
            $manager->addProperty('og:description', $params['page']['og_description']);
        }

        if (!empty($params['page']['og_image'])) {
            $fileCollector = GeneralUtility::makeInstance(FileCollector::class);
            $fileCollector->addFilesFromRelation('pages', 'og_image', $params['page']);
            $manager = $this->metaTagManagerRegistry->getManagerForProperty('og:image');

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

        $manager = $this->metaTagManagerRegistry->getManagerForProperty('twitter:card');
        $manager->addProperty('twitter:card', $params['page']['twitter_card'] ?: 'summary');

        if (!empty($params['page']['twitter_title'])) {
            $manager = $this->metaTagManagerRegistry->getManagerForProperty('twitter:title');
            $manager->addProperty('twitter:title', $params['page']['twitter_title']);
        }

        if (!empty($params['page']['twitter_description'])) {
            $manager = $this->metaTagManagerRegistry->getManagerForProperty('twitter:description');
            $manager->addProperty('twitter:description', $params['page']['twitter_description']);
        }

        if (!empty($params['page']['twitter_image'])) {
            $fileCollector = GeneralUtility::makeInstance(FileCollector::class);
            $fileCollector->addFilesFromRelation('pages', 'twitter_image', $params['page']);
            $manager = $this->metaTagManagerRegistry->getManagerForProperty('twitter:image');

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
            $manager = $this->metaTagManagerRegistry->getManagerForProperty('robots');
            $manager->addProperty('robots', implode(',', [$noIndex, $noFollow]));
        }
    }

    /**
     * @param array $fileReferences
     * @return array
     */
    protected function generateSocialImages(array $fileReferences): array
    {
        $socialImages = [];

        /** @var FileReference $file */
        foreach ($fileReferences as $file) {
            $arguments = $file->getProperties();
            $cropVariantCollection = CropVariantCollection::create((string)$arguments['crop']);
            $cropVariant = ($arguments['cropVariant'] ?? false) ?: 'social';
            $cropArea = $cropVariantCollection->getCropArea($cropVariant);
            $crop = $cropArea->makeAbsoluteBasedOnFile($file);

            $processingConfiguration = [
                'crop' => $crop,
                'maxWidth' => 2000,
            ];

            if ($file->getProperty('width') > $processingConfiguration['maxWidth'] || ($cropArea->getHeight() !== 1.0 && $cropArea->getWidth() !== 1.0)) {
                $image = $file->getOriginalFile()->process(
                    ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
                    $processingConfiguration
                );
            } else {
                $image = $file->getOriginalFile();
            }

            $imageUri = $this->imageService->getImageUri($image, true);

            $socialImages[] = [
                'url' => $imageUri,
                'width' => floor($image->getProperty('width')),
                'height' => floor($image->getProperty('height')),
                'alternative' => $arguments['alternative'],
            ];
        }

        return $socialImages;
    }
}
