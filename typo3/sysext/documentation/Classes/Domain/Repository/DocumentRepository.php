<?php
namespace TYPO3\CMS\Documentation\Domain\Repository;

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

/**
 * An extension helper repository to be used in ext:documentation context
 */
class DocumentRepository
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Finds all documents.
     *
     * @return \TYPO3\CMS\Documentation\Domain\Model\Document[]
     */
    public function findAll()
    {
        $documents = $this->findSphinxDocuments();
        $openOfficeDocuments = $this->findOpenOfficeDocuments();

        // Add OpenOffice documents if there is not already an existing, non OpenOffice version
        foreach ($openOfficeDocuments as $documentKey => $document) {
            if (!isset($documents[$documentKey])) {
                $documents[$documentKey] = $document;
            }
        }

        return $documents;
    }

    /**
     * Finds documents by language, always falls back to 'default' (English).
     *
     * @param string $language
     * @return \TYPO3\CMS\Documentation\Domain\Model\Document[]
     */
    public function findByLanguage($language)
    {
        $allDocuments = $this->findAll();

        // Initialize the dependency of languages
        $languageDependencies = [];
        /** @var $locales \TYPO3\CMS\Core\Localization\Locales */
        $locales = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Localization\Locales::class);
        // Language is found. Configure it:
        $shortLanguage = $language;
        if (!in_array($shortLanguage, $locales->getLocales()) && strpos($shortLanguage, '_') !== false) {
            list($shortLanguage, $_) = explode('_', $shortLanguage);
        }
        if (in_array($shortLanguage, $locales->getLocales())) {
            $languageDependencies[] = $language;
            if ($language !== $shortLanguage) {
                $languageDependencies[] = $shortLanguage;
            }
            foreach ($locales->getLocaleDependencies($shortLanguage) as $languageDependency) {
                $languageDependencies[] = $languageDependency;
            }
        }
        if ($language !== 'default') {
            $languageDependencies[] = 'default';
        }

        foreach ($allDocuments as $document) {
            // Remove every unwanted translation
            $selectedTranslation = null;
            $highestPriorityLanguageIndex = count($languageDependencies);

            $translations = $document->getTranslations();
            foreach ($translations as $translation) {
                $languageIndex = array_search($translation->getLanguage(), $languageDependencies);
                if ($languageIndex !== false) {
                    if ($languageIndex < $highestPriorityLanguageIndex) {
                        $selectedTranslation = $translation;
                        $highestPriorityLanguageIndex = $languageIndex;
                    }
                } else {
                    // No exact translation found, perhaps another locale would fit as well. E.g., when requesting
                    // a documentation as fr_CA but only fr_FR exists
                    if (strpos($translation->getLanguage(), '_') !== false) {
                        list($translationLanguage, $_) = explode('_', $translation->getLanguage());
                        $languageIndex = array_search($translationLanguage, $languageDependencies);
                        if ($languageIndex !== false && $languageIndex < $highestPriorityLanguageIndex) {
                            $selectedTranslation = $translation;
                            $highestPriorityLanguageIndex = $languageIndex;
                        }
                    }
                }
            }

            $newTranslations = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
            $document->setTranslations($newTranslations);
            if ($selectedTranslation !== null) {
                $document->addTranslation($selectedTranslation);
            }
        }

        return $allDocuments;
    }

    /**
     * Retrieves Sphinx documents.
     *
     * @return array
     */
    protected function findSphinxDocuments()
    {
        $basePath = 'typo3conf/Documentation/';

        $documents = [];
        $documentKeys = GeneralUtility::get_dirs(PATH_site . $basePath);
        // Early return in case no document keys were found
        if (!is_array($documentKeys)) {
            return $documents;
        }

        foreach ($documentKeys as $documentKey) {
            $icon = \TYPO3\CMS\Documentation\Utility\MiscUtility::getIcon($documentKey);

            /** @var \TYPO3\CMS\Documentation\Domain\Model\Document $document */
            $document = $this->objectManager->get(\TYPO3\CMS\Documentation\Domain\Model\Document::class)
                ->setPackageKey($documentKey)
                ->setIcon($icon);

            $languagePath = $basePath . $documentKey . '/';
            $languages = GeneralUtility::get_dirs(PATH_site . $languagePath);
            foreach ($languages as $language) {
                $metadata = $this->getMetadata($documentKey, $language);
                if (!empty($metadata['extensionKey'])) {
                    $document->setExtensionKey($metadata['extensionKey']);
                }

                /** @var \TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation $documentTranslation */
                $documentTranslation = $this->objectManager->get(\TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation::class)
                    ->setLanguage($language)
                    ->setTitle($metadata['title'])
                    ->setDescription($metadata['description']);

                $formatPath = $languagePath . $language . '/';
                $formats = GeneralUtility::get_dirs(PATH_site . $formatPath);
                foreach ($formats as $format) {
                    $documentFile = '';
                    switch ($format) {
                        case 'html':
                            // Try to find a valid index file
                            $indexFiles = ['Index.html', 'index.html', 'index.htm'];
                            foreach ($indexFiles as $indexFile) {
                                if (file_exists(PATH_site . $formatPath . $format . '/' . $indexFile)) {
                                    $documentFile = $indexFile;
                                    break;
                                }
                            }
                            break;
                        case 'pdf':
                            // Retrieve first PDF
                            $files = GeneralUtility::getFilesInDir(PATH_site . $formatPath . $format, 'pdf');
                            if (is_array($files) && !empty($files)) {
                                $documentFile = current($files);
                            }
                            break;
                    }
                    if (!empty($documentFile)) {
                        /** @var \TYPO3\CMS\Documentation\Domain\Model\DocumentFormat $documentFormat */
                        $documentFormat = $this->objectManager->get(\TYPO3\CMS\Documentation\Domain\Model\DocumentFormat::class)
                            ->setFormat($format)
                            ->setPath($formatPath . $format . '/' . $documentFile);

                        $documentTranslation->addFormat($documentFormat);
                    }
                }

                if (!empty($documentTranslation->getFormats())) {
                    $document->addTranslation($documentTranslation);
                    $documents[$documentKey] = $document;
                }
            }
        }

        return $documents;
    }

    /**
     * Retrieves OpenOffice documents (manual.sxw).
     *
     * @return array
     */
    protected function findOpenOfficeDocuments()
    {
        $documents = [];
        $language = 'default';

        foreach ($GLOBALS['TYPO3_LOADED_EXT'] as $extensionKey => $extensionData) {
            $path = $extensionData['siteRelPath'] . 'doc/';
            if (is_file(PATH_site . $path . 'manual.sxw')) {
                $documentKey = 'typo3cms.extensions.' . $extensionKey;
                $icon = \TYPO3\CMS\Documentation\Utility\MiscUtility::getIcon($documentKey);

                /** @var \TYPO3\CMS\Documentation\Domain\Model\Document $document */
                $document = $this->objectManager->get(\TYPO3\CMS\Documentation\Domain\Model\Document::class)
                    ->setPackageKey($documentKey)
                    ->setExtensionKey($extensionKey)
                    ->setIcon($icon);

                $metadata = $this->getMetadata($documentKey, $language);
                /** @var \TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation $documentTranslation */
                $documentTranslation = $this->objectManager->get(\TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation::class)
                    ->setLanguage($language)
                    ->setTitle($metadata['title'])
                    ->setDescription($metadata['description']);

                /** @var \TYPO3\CMS\Documentation\Domain\Model\DocumentFormat $documentFormat */
                $documentFormat = $this->objectManager->get(\TYPO3\CMS\Documentation\Domain\Model\DocumentFormat::class)
                    ->setFormat('sxw')
                    ->setPath($path . 'manual.sxw');

                $documentTranslation->addFormat($documentFormat);
                $document->addTranslation($documentTranslation);
                $documents[$documentKey] = $document;
            }
        }

        return $documents;
    }

    /**
     * Returns metadata associated to a given document key.
     *
     * @param string $documentKey
     * @param string $language
     * @return array
     */
    protected function getMetadata($documentKey, $language)
    {
        $documentPath = PATH_site . 'typo3conf/Documentation/' . $documentKey . '/' . $language . '/';
        $metadata = [
            'title' => $documentKey,
            'description' => '',
        ];
        if (GeneralUtility::isFirstPartOfStr($documentKey, 'typo3cms.extensions.')) {
            $extensionKey = substr($documentKey, 20);
            if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extensionKey)) {
                $metadata = \TYPO3\CMS\Documentation\Utility\MiscUtility::getExtensionMetaData($extensionKey);
            }
        } elseif (is_file($documentPath . 'composer.json')) {
            $info = json_decode(file_get_contents($documentPath . 'composer.json'), true);
            if (is_array($info)) {
                $metadata['title'] = $info['name'];
                $metadata['description'] = $info['description'];
            }
        }
        return $metadata;
    }
}
