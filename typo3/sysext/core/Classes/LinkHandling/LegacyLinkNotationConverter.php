<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\LinkHandling;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class to resolve and convert the "old" link information (email, external url, file, page etc)
 * to a URL or new format for migration
 *
 * @internal
 */
class LegacyLinkNotationConverter
{

    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    /**
     * Part of the typolink construction functionality, called by typoLink()
     * Used to resolve "legacy"-based typolinks.
     *
     * Tries to get the type of the link from the link parameter
     * could be
     *  - "mailto" an email address
     *  - "url" external URL
     *  - "file" a local file (checked AFTER getPublicUrl() is called)
     *  - "page" a page (integer or alias)
     *
     * Does NOT check if the page exists or the file exists.
     *
     * @param string $linkParameter could be "fileadmin/myfile.jpg", "info@typo3.org", "13" or "http://www.typo3.org"
     *
     * @return array
     */
    public function resolve(string $linkParameter): array
    {
        if (stripos(rawurldecode(trim($linkParameter)), 'phar://') === 0) {
            throw new \RuntimeException(
                'phar scheme not allowed as soft reference target',
                1530030673
            );
        }

        $result = [];

        // Resolve FAL-api "file:UID-of-sys_file-record" and "file:combined-identifier"
        if (stripos($linkParameter, 'file:') === 0) {
            $result = $this->getFileOrFolderObjectFromMixedIdentifier(substr($linkParameter, 5));
        } elseif (GeneralUtility::validEmail(parse_url($linkParameter, PHP_URL_PATH))) {
            $result['type'] = LinkService::TYPE_EMAIL;
            $result['email'] = $linkParameter;
        } elseif (strpos($linkParameter, ':') !== false) {
            // Check for link-handler keyword
            list($linkHandlerKeyword, $linkHandlerValue) = explode(':', $linkParameter, 2);
            $result['type'] = strtolower(trim($linkHandlerKeyword));
            $result['url'] = $linkParameter;
            $result['value'] = $linkHandlerValue;
            if ($result['type'] === LinkService::TYPE_RECORD) {
                list($a['identifier'], $tableAndUid) = explode(':', $linkHandlerValue, 2);
                $tableAndUid = explode(':', $tableAndUid);
                if (count($tableAndUid) > 1) {
                    $a['table'] = $tableAndUid[0];
                    $a['uid'] = $tableAndUid[1];
                } else {
                    // this case can happen if there is the very old linkhandler syntax, which was only record:<table>:<uid>
                    $a['table'] = $a['identifier'];
                    $a['uid'] = $tableAndUid[0];
                }
                $result = array_merge($result, $a);
            }
        } else {
            // special handling without a scheme
            $isLocalFile = 0;
            $fileChar = (int)strpos($linkParameter, '/');
            $urlChar = (int)strpos($linkParameter, '.');

            $isIdOrAlias = MathUtility::canBeInterpretedAsInteger($linkParameter);
            $matches = [];
            // capture old RTE links relative to TYPO3_mainDir
            if (preg_match('#../(?:index\\.php)?\\?id=([^&]+)#', $linkParameter, $matches)) {
                $linkParameter = $matches[1];
                $isIdOrAlias = true;
            }
            $containsSlash = false;
            if (!$isIdOrAlias) {
                // Detects if a file is found in site-root and if so it will be treated like a normal file.
                list($rootFileDat) = explode('?', rawurldecode($linkParameter));
                $containsSlash = strpos($rootFileDat, '/') !== false;
                $pathInfo = pathinfo($rootFileDat);
                $fileExtension = strtolower($pathInfo['extension'] ?? '');
                if (!$containsSlash
                    && trim($rootFileDat)
                    && (
                        @is_file(Environment::getPublicPath() . '/' . $rootFileDat)
                        || $fileExtension === 'php'
                        || $fileExtension === 'html'
                        || $fileExtension === 'htm'
                    )
                ) {
                    $isLocalFile = 1;
                } elseif ($containsSlash) {
                    // Adding this so realurl directories are linked right (non-existing).
                    $isLocalFile = 2;
                }
            }

            // url (external): If doubleSlash or if a '.' comes before a '/'.
            if (!$isIdOrAlias && $isLocalFile !== 1 && $urlChar && (!$containsSlash || $urlChar < $fileChar)) {
                $result['type'] = LinkService::TYPE_URL;
                $result['url'] = 'http://' . $linkParameter;
            // file (internal) or folder
            } elseif ($containsSlash || $isLocalFile) {
                $result = $this->getFileOrFolderObjectFromMixedIdentifier($linkParameter);
            } else {
                // Integer or alias (alias is without slashes or periods or commas, that is
                // 'nospace,alphanum_x,lower,unique' according to definition in $GLOBALS['TCA']!)
                $result = $this->resolvePageRelatedParameters($linkParameter);
            }
        }

        return $result;
    }

    /**
     * Internal method to do some magic to get a page parts, additional params, fragment / section hash
     *
     * @param string $data the input variable, can be "mypage,23" with fragments, keys
     *
     * @return array the result array with the page type set
     */
    protected function resolvePageRelatedParameters(string $data): array
    {
        $result = ['type' => LinkService::TYPE_PAGE];
        if (strpos($data, '#') !== false) {
            list($data, $result['fragment']) = explode('#', $data, 2);
        }
        // check for additional parameters
        if (strpos($data, '?') !== false) {
            list($data, $result['parameters']) = explode('?', $data, 2);
        } elseif (strpos($data, '&') !== false) {
            list($data, $result['parameters']) = explode('&', $data, 2);
        }
        if (empty($data)) {
            $result['pageuid'] = 'current';
        } elseif ($data[0] === '#') {
            $result['pageuid'] = 'current';
            $result['fragment'] = substr($data, 1);
        } elseif (strpos($data, ',') !== false) {
            $data = rtrim($data, ',');
            list($result['pageuid'], $result['pagetype']) = explode(',', $data, 2);
        } elseif (strpos($data, '/') !== false) {
            $data = explode('/', trim($data, '/'));
            $result['pageuid'] = array_shift($data);
            foreach ($data as $k => $item) {
                if ($data[$k] % 2 === 0 && !empty($data[$k + 1])) {
                    $result['page' . $data[$k]] = $data[$k + 1];
                }
            }
        } else {
            $result['pageuid'] = $data;
        }

        // expect an alias
        if (!MathUtility::canBeInterpretedAsInteger($result['pageuid']) && $result['pageuid'] !== 'current') {
            $result['pagealias'] = $result['pageuid'];
            unset($result['pageuid']);
        }

        return $result;
    }

    /**
     * Internal method that fetches a file or folder object based on the file or folder combined identifier
     *
     * @param string $mixedIdentifier can be something like "2" (file uid), "fileadmin/i/like.png" or "2:/myidentifier/"
     *
     * @return array the result with the type (file or folder) set
     */
    protected function getFileOrFolderObjectFromMixedIdentifier(string $mixedIdentifier): array
    {
        $result = [];
        try {
            $fileIdentifier = $mixedIdentifier;
            $fragment = null;
            if (strpos($fileIdentifier, '#') !== false) {
                [$fileIdentifier, $fragment] = explode('#', $fileIdentifier, 2);
            }
            $fileOrFolderObject = $this->getResourceFactory()->retrieveFileOrFolderObject($fileIdentifier);
            // Link to a folder or file
            if ($fileOrFolderObject instanceof File) {
                $result['type'] = LinkService::TYPE_FILE;
                $result['file'] = $fileOrFolderObject;
                if ($fragment) {
                    $result['fragment'] = $fragment;
                }
            } elseif ($fileOrFolderObject instanceof Folder) {
                $result['type'] = LinkService::TYPE_FOLDER;
                $result['folder'] = $fileOrFolderObject;
                if ($fragment) {
                    $result['fragment'] = $fragment;
                }
            } else {
                $result['type'] = LinkService::TYPE_UNKNOWN;
                $result['file'] = $mixedIdentifier;
            }
        } catch (\RuntimeException $e) {
            // Element wasn't found
            $result['type'] = LinkService::TYPE_UNKNOWN;
            $result['file'] = $mixedIdentifier;
        } catch (ResourceDoesNotExistException $e) {
            // Resource was not found
            $result['type'] = LinkService::TYPE_UNKNOWN;
            $result['file'] = $mixedIdentifier;
        }

        return $result;
    }

    /**
     * Initializes the resource factory (only once)
     *
     * @return ResourceFactory
     */
    protected function getResourceFactory(): ResourceFactory
    {
        if (!$this->resourceFactory) {
            $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        }
        return $this->resourceFactory;
    }
}
