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

namespace TYPO3\CMS\Core\LinkHandling;

use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Interface for classes which are transforming a tag link hrefs for folders, in order to
 * use FAL to store them in database, which means that folders can be moved in the fileadmin
 * without breaking folder links in the frontend/backend
 */
class FolderLinkHandler implements LinkHandlingInterface
{

    /**
     * The Base URN
     * @var string
     */
    protected $baseUrn = 't3://folder';

    /**
     * The resource factory to resolve
     * @var \TYPO3\CMS\Core\Resource\ResourceFactory
     */
    protected $resourceFactory;

    /**
     * Returns a link notation to a folder
     *
     * @param array $parameters
     *
     * @return string
     */
    public function asString(array $parameters): string
    {
        // the magic with prepending slash if it is missing will not work on windows
        return $this->baseUrn . '?storage=' . $parameters['folder']->getStorage()->getUid() .
        '&identifier=' . urlencode('/' . ltrim($parameters['folder']->getIdentifier(), '/'));
    }

    /**
     * Get a folder object inside the array data from the string
     *
     * @param array $data with the "folder" property containing a Folder object
     *
     * @return array
     */
    public function resolveHandlerData(array $data): array
    {
        $combinedIdentifier = ($data['storage'] ?? '0') . ':' . $data['identifier'];
        try {
            $folder = $this->getResourceFactory()->getFolderObjectFromCombinedIdentifier($combinedIdentifier);
        } catch (FolderDoesNotExistException|InsufficientFolderAccessPermissionsException $e) {
            $folder = null;
        }
        return ['folder' => $folder];
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
