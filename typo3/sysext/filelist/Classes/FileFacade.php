<?php
namespace TYPO3\CMS\Filelist;

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

use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FileFacade
 *
 * This class is meant to be a wrapper for Resource\File objects, which do not
 * provide necessary methods needed in the views of the filelist extension. It
 * is a first approach to get rid of the FileList class that mixes up PHP,
 * HTML and JavaScript.
 * @internal this is a concrete TYPO3 hook implementation and solely used for EXT:filelist and not part of TYPO3's Core API.
 */
class FileFacade
{
    /**
     * Cache to count the number of references for each file
     *
     * @var array
     */
    protected static $referenceCounts = [];

    /**
     * @var \TYPO3\CMS\Core\Resource\FileInterface
     */
    protected $resource;

    /**
     * @var \TYPO3\CMS\Core\Imaging\IconFactory
     */
    protected $iconFactory;

    /**
     * @param \TYPO3\CMS\Core\Resource\FileInterface $resource
     * @internal Do not use outside of EXT:filelist!
     */
    public function __construct(FileInterface $resource)
    {
        $this->resource = $resource;
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * @return string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function getIcon(): string
    {
        trigger_error('FileFacade->getIcon() will be removed in TYPO3 v10.0, use ViewHelper <core:iconForResource /> instead.', E_USER_DEPRECATED);
        $title = htmlspecialchars($this->resource->getName() . ' [' . (int)$this->resource->getProperty('uid') . ']');
        return '<span title="' . $title . '">' . $this->iconFactory->getIconForResource($this->resource, Icon::SIZE_SMALL) . '</span>';
    }

    /**
     * @return \TYPO3\CMS\Core\Resource\FileInterface
     */
    public function getResource(): FileInterface
    {
        return $this->resource;
    }

    /**
     * @return bool
     */
    public function getIsEditable(): bool
    {
        return $this->getIsWritable()
            && GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'], $this->resource->getExtension());
    }

    /**
     * @return bool
     */
    public function getIsMetadataEditable(): bool
    {
        return $this->resource->isIndexed() && $this->getIsWritable() && $this->getBackendUser()->check('tables_modify', 'sys_file_metadata');
    }

    /**
     * @return int
     */
    public function getMetadataUid(): int
    {
        $uid = 0;
        $method = '_getMetadata';
        if (is_callable([$this->resource, $method])) {
            $metadata = call_user_func([$this->resource, $method]);

            if (isset($metadata['uid'])) {
                $uid = (int)$metadata['uid'];
            }
        }

        return $uid;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->resource->getName();
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        $method = 'getReadablePath';
        if (is_callable([$this->resource->getParentFolder(), $method])) {
            return call_user_func([$this->resource->getParentFolder(), $method]);
        }

        return '';
    }

    /**
     * @return string|null
     */
    public function getPublicUrl()
    {
        return $this->resource->getPublicUrl(true);
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return strtoupper($this->resource->getExtension());
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->resource->getStorage()->getUid() . ':' . $this->resource->getIdentifier();
    }

    /**
     * @return string
     */
    public function getLastModified(): string
    {
        return BackendUtility::date($this->resource->getModificationTime());
    }

    /**
     * @return string
     */
    public function getSize(): string
    {
        return GeneralUtility::formatSize($this->resource->getSize(), htmlspecialchars($this->getLanguageService()->getLL('byteSizeUnits')));
    }

    /**
     * @return bool
     */
    public function getIsReadable()
    {
        $method = 'checkActionPermission';
        if (is_callable([$this->resource, $method])) {
            return call_user_func_array([$this->resource, $method], ['read']);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getIsWritable()
    {
        $method = 'checkActionPermission';
        if (is_callable([$this->resource, $method])) {
            return call_user_func_array([$this->resource, $method], ['write']);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getIsReplaceable()
    {
        $method = 'checkActionPermission';
        if (is_callable([$this->resource, $method])) {
            return call_user_func_array([$this->resource, $method], ['replace']);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getIsRenamable()
    {
        $method = 'checkActionPermission';
        if (is_callable([$this->resource, $method])) {
            return call_user_func_array([$this->resource, $method], ['rename']);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isCopyable()
    {
        $method = 'checkActionPermission';
        if (is_callable([$this->resource, $method])) {
            return call_user_func_array([$this->resource, $method], ['copy']);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isCuttable()
    {
        $method = 'checkActionPermission';
        if (is_callable([$this->resource, $method])) {
            return call_user_func_array([$this->resource, $method], ['move']);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getIsDeletable()
    {
        $method = 'checkActionPermission';
        if (is_callable([$this->resource, $method])) {
            return call_user_func_array([$this->resource, $method], ['delete']);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isSelected()
    {
        $fullIdentifier = $this->getIdentifier();
        $md5 = GeneralUtility::shortMD5($fullIdentifier);

        /** @var Clipboard $clipboard */
        $clipboard = GeneralUtility::makeInstance(Clipboard::class);
        $clipboard->initializeClipboard();

        $isSel = $clipboard->isSelected('_FILE', $md5);

        if ($isSel) {
            return $isSel;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getIsImage()
    {
        return GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], strtolower($this->getExtension()));
    }

    /**
     * Fetch, cache and return the number of references of a file
     *
     * @return int
     */
    public function getReferenceCount(): int
    {
        $uid = (int)$this->resource->getProperty('uid');

        if ($uid <= 0) {
            return 0;
        }

        if (!isset(static::$referenceCounts[$uid])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');
            $count = $queryBuilder->count('*')
                ->from('sys_refindex')
                ->where(
                    $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq(
                        'ref_table',
                        $queryBuilder->createNamedParameter('sys_file', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'ref_uid',
                        $queryBuilder->createNamedParameter($this->resource->getProperty('uid'), \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->neq(
                        'tablename',
                        $queryBuilder->createNamedParameter('sys_file_metadata', \PDO::PARAM_STR)
                    )
                )
                ->execute()
                ->fetchColumn();

            static::$referenceCounts[$uid] = $count;
        }

        return static::$referenceCounts[$uid];
    }

    /**
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (is_callable([$this->resource, $method])) {
            return call_user_func_array([$this->resource, $method], $arguments);
        }

        return null;
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
