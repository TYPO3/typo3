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

namespace TYPO3\CMS\Backend\Backend\Avatar;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Avatar Provider used for rendering avatars based on local files (based on FAL), stored in the be_users.avatar
 * relation field with sys_file_reference.
 */
class DefaultAvatarProvider implements AvatarProviderInterface
{
    /**
     * Return an Image object for rendering the avatar, based on a FAL-based file
     *
     * @param array $backendUser be_users record
     * @param int $size
     * @return Image|null
     */
    public function getImage(array $backendUser, $size)
    {
        $fileUid = $this->getAvatarFileUid($backendUser['uid']);
        if ($fileUid === 0) {
            // Early return if there is no valid image file UID
            return null;
        }
        // Get file object
        try {
            $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($fileUid);
            $processedImage = $file->process(
                ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
                ['width' => $size . 'c', 'height' => $size . 'c']
            );

            $image = GeneralUtility::makeInstance(
                Image::class,
                $processedImage->getPublicUrl(),
                $processedImage->getProperty('width'),
                $processedImage->getProperty('height')
            );
        } catch (FileDoesNotExistException $e) {
            // No image found
            $image = null;
        }

        return $image;
    }

    /**
     * Get the sys_file UID of the avatar of the given backend user ID
     *
     * @param int $backendUserId the UID of the be_users record
     * @return int the sys_file UID or 0 if none found
     */
    protected function getAvatarFileUid($backendUserId)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $fileUid = $queryBuilder
            ->select('uid_local')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter('be_users', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter('avatar', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'table_local',
                    $queryBuilder->createNamedParameter('sys_file', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter((int)$backendUserId, \PDO::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();

        return (int)$fileUid;
    }
}
