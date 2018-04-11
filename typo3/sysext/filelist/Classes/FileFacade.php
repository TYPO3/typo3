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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FileFacade
 *
 * This class is meant to be a wrapper for Resource\File objects, which do not
 * provide necessary methods needed in the views of the filelist extension. It
 * is a first approach to get rid of the FileList class that mixes up PHP,
 * HTML and JavaScript.
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
     */
    public function getIcon()
    {
        $title = htmlspecialchars($this->resource->getName() . ' [' . (int)$this->resource->getProperty('uid') . ']');
        return '<span title="' . $title . '">' . $this->iconFactory->getIconForResource($this->resource, Icon::SIZE_SMALL) . '</span>';
    }

    /**
     * @return \TYPO3\CMS\Core\Resource\FileInterface
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return bool
     */
    public function getIsEditable()
    {
        return $this->getIsWritable()
            && GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'], $this->resource->getExtension());
    }

    /**
     * @return bool
     */
    public function getIsMetadataEditable()
    {
        return $this->resource->isIndexed() && $this->getIsWritable() && $this->getBackendUser()->check('tables_modify', 'sys_file_metadata');
    }

    /**
     * @return int
     */
    public function getMetadataUid()
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
    public function getName()
    {
        return $this->resource->getName();
    }

    /**
     * @return string
     */
    public function getPath()
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
    public function getExtension()
    {
        return strtoupper($this->resource->getExtension());
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->resource->getStorage()->getUid() . ':' . $this->resource->getIdentifier();
    }

    /**
     * @return string
     */
    public function getLastModified()
    {
        return BackendUtility::date($this->resource->getModificationTime());
    }

    /**
     * @return string
     */
    public function getSize()
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
    public function getIsImage()
    {
        return GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], strtolower($this->getExtension()));
    }

    /**
     * Fetch, cache and return the number of references of a file
     *
     * @return int
     */
    public function getReferenceCount()
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
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
