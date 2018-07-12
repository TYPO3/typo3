<?php
namespace TYPO3\CMS\Core\IO;

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

class PharStreamWrapper
{
    /**
     * Internal stream constants that are not exposed to PHP, but used...
     * @see https://github.com/php/php-src/blob/e17fc0d73c611ad0207cac8a4a01ded38251a7dc/main/php_streams.h
     */
    const STREAM_OPEN_FOR_INCLUDE = 128;

    /**
     * @var resource
     */
    public $context;

    /**
     * @var resource
     */
    protected $internalResource;

    /**
     * @return bool
     */
    public function dir_closedir()
    {
        if (!is_resource($this->internalResource)) {
            return false;
        }

        $this->invokeInternalStreamWrapper(
            'closedir',
            [ $this->internalResource ]
        );
        return !is_resource($this->internalResource);
    }

    /**
     * @param string $path
     * @param int $options
     * @return bool
     */
    public function dir_opendir($path, $options)
    {
        $this->assertPath($path);
        $this->internalResource = $this->invokeInternalStreamWrapper(
            'opendir',
            [ $path, $this->context ]
        );
        return is_resource($this->internalResource);
    }

    /**
     * @return string|false
     */
    public function dir_readdir()
    {
        return $this->invokeInternalStreamWrapper(
            'readdir',
            [ $this->internalResource ]
        );
    }

    /**
     * @return bool
     */
    public function dir_rewinddir()
    {
        if (!is_resource($this->internalResource)) {
            return false;
        }

        $this->invokeInternalStreamWrapper(
            'rewinddir',
            [ $this->internalResource ]
        );
        return is_resource($this->internalResource);
    }

    /**
     * @param string $path
     * @param int $mode
     * @param int $options
     * @return bool
     */
    public function mkdir($path, $mode, $options)
    {
        $this->assertPath($path);
        return $this->invokeInternalStreamWrapper(
            'mkdir',
            [ $path, $mode, (bool)($options & STREAM_MKDIR_RECURSIVE), $this->context ]
        );
    }

    /**
     * @param string $path_from
     * @param string $path_to
     * @return bool
     */
    public function rename($path_from, $path_to)
    {
        $this->assertPath($path_from);
        $this->assertPath($path_to);
        return $this->invokeInternalStreamWrapper(
            'rename',
            [ $path_from, $path_to, $this->context ]
        );
    }

    public function rmdir($path, $options)
    {
        $this->assertPath($path);
        return $this->invokeInternalStreamWrapper(
            'rmdir',
            [ $path, $this->context ]
        );
    }

    /**
     * @param int $cast_as
     */
    public function stream_cast($cast_as)
    {
        throw new PharStreamWrapperException(
            'Method stream_select() cannot be used',
            1530103999
        );
    }

    public function stream_close()
    {
        $this->invokeInternalStreamWrapper(
            'fclose',
            [ $this->internalResource ]
        );
    }

    /**
     * @return bool
     */
    public function stream_eof()
    {
        return $this->invokeInternalStreamWrapper(
            'feof',
            [ $this->internalResource ]
        );
    }

    /**
     * @return bool
     */
    public function stream_flush()
    {
        return $this->invokeInternalStreamWrapper(
            'fflush',
            [ $this->internalResource ]
        );
    }

    /**
     * @param int $operation
     * @return bool
     */
    public function stream_lock($operation)
    {
        return $this->invokeInternalStreamWrapper(
            'flock',
            [ $this->internalResource, $operation ]
        );
    }

    /**
     * @param string $path
     * @param int $option
     * @param string|int $value
     * @return bool
     */
    public function stream_metadata($path, $option, $value)
    {
        $this->assertPath($path);
        if ($option === STREAM_META_TOUCH) {
            return $this->invokeInternalStreamWrapper(
                'touch',
                [ $path, $value ]
            );
        }
        if ($option === STREAM_META_OWNER_NAME || $option === STREAM_META_OWNER) {
            return $this->invokeInternalStreamWrapper(
                'chown',
                [ $path, $value ]
            );
        }
        if ($option === STREAM_META_GROUP_NAME || $option === STREAM_META_GROUP) {
            return $this->invokeInternalStreamWrapper(
                'chgrp',
                [ $path, $value]
            );
        }
        if ($option === STREAM_META_ACCESS) {
            return $this->invokeInternalStreamWrapper(
                'chmod',
                [ $path, $value ]
            );
        }
        return false;
    }

    /**
     * @param string $path
     * @param string $mode
     * @param int $options
     * @param string|null $opened_path
     * @return bool
     */
    public function stream_open(
        $path,
        $mode,
        $options,
        &$opened_path = null
    )
    {
        $this->assertPath($path);
        $arguments = [$path, $mode, (bool)($options & STREAM_USE_PATH)];
        // only add stream context for non include/require calls
        if (!($options & static::STREAM_OPEN_FOR_INCLUDE)) {
            $arguments[] = $this->context;
        // work around https://bugs.php.net/bug.php?id=66569
        // for including files from Phar stream with OPcache enabled
        } else {
            $this->resetOpCache();
        }
        $this->internalResource = $this->invokeInternalStreamWrapper(
            'fopen',
            $arguments
        );
        if (!is_resource($this->internalResource)) {
            return false;
        }
        if ($opened_path !== null) {
            $metaData = stream_get_meta_data($this->internalResource);
            $opened_path = $metaData['uri'];
        }
        return true;
    }

    /**
     * @param int $count
     * @return string
     */
    public function stream_read($count)
    {
        return $this->invokeInternalStreamWrapper(
            'fread',
            [ $this->internalResource, $count ]
        );
    }

    /**
     * @param int $offset
     * @param int $whence
     * @return bool
     */
    public function stream_seek($offset, $whence = SEEK_SET)
    {
        return $this->invokeInternalStreamWrapper(
            'fseek',
            [ $this->internalResource, $offset, $whence ]
        ) !== -1;
    }

    /**
     * @param int $option
     * @param int $arg1
     * @param int $arg2
     * @return bool
     */
    public function stream_set_option($option, $arg1, $arg2)
    {
        if ($option === STREAM_OPTION_BLOCKING) {
            return $this->invokeInternalStreamWrapper(
                'stream_set_blocking',
                [ $this->internalResource, $arg1 ]
            );
        }
        if ($option === STREAM_OPTION_READ_TIMEOUT) {
            return $this->invokeInternalStreamWrapper(
                'stream_set_timeout',
                [ $this->internalResource, $arg1, $arg2 ]
            );
        }
        if ($option === STREAM_OPTION_WRITE_BUFFER) {
            return $this->invokeInternalStreamWrapper(
                'stream_set_write_buffer',
                [ $this->internalResource, $arg2 ]
            ) === 0;
        }
        return false;
    }

    /**
     * @return array
     */
    public function stream_stat()
    {
        return $this->invokeInternalStreamWrapper(
            'fstat',
            [ $this->internalResource ]
        );
    }

    /**
     * @return int
     */
    public function stream_tell()
    {
        return $this->invokeInternalStreamWrapper(
            'ftell',
            [ $this->internalResource ]
        );
    }

    /**
     * @param int $new_size
     * @return bool
     */
    public function stream_truncate($new_size)
    {
        return $this->invokeInternalStreamWrapper(
            'ftruncate',
            [ $this->internalResource, $new_size ]
        );
    }

    /**
     * @param string $data
     * @return int
     */
    public function stream_write($data)
    {
        return $this->invokeInternalStreamWrapper(
            'fwrite',
            [ $this->internalResource, $data ]
        );
    }

    /**
     * @param string $path
     * @return bool
     */
    public function unlink($path)
    {
        $this->assertPath($path);
        return $this->invokeInternalStreamWrapper(
            'unlink',
            [ $path, $this->context ]
        );
    }

    /**
     * @param string $path
     * @param int $flags
     * @return array|false
     */
    public function url_stat($path, $flags)
    {
        $this->assertPath($path);
        $functionName = $flags & STREAM_URL_STAT_QUIET ? '@stat' : 'stat';
        return $this->invokeInternalStreamWrapper(
            $functionName,
            [ $path ]
        );
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function isAllowed($path)
    {
        $path = $this->determineBaseFile($path);
        if (!GeneralUtility::isAbsPath($path)) {
            $path = PATH_site . $path;
        }

        if (GeneralUtility::validPathStr($path)
            && GeneralUtility::isFirstPartOfStr(
                $path,
                PATH_site . 'typo3conf/ext/'
            )
        ) {
            return true;
        }
        return false;
    }

    /**
     * Normalizes a path, removes phar:// prefix, fixes Windows directory
     * separators. Result is without trailing slash.
     *
     * @param string $path
     * @return string
     */
    protected function normalizePath($path)
    {
        return rtrim(
            PathUtility::getCanonicalPath(
                GeneralUtility::fixWindowsFilePath(
                    $this->removePharPrefix($path)
                )
            ),
            '/'
        );
    }

    /**
     * @param string $path
     * @return string
     */
    protected function removePharPrefix($path)
    {
        return preg_replace('#^phar://#i', '', $path);
    }

    /**
     * Determines base file that can be accessed using the regular file system.
     * For e.g. "phar:///home/user/bundle.phar/content.txt" that would result
     * into "/home/user/bundle.phar".
     *
     * @param string $path
     * @return string|null
     */
    protected function determineBaseFile($path)
    {
        $parts = explode('/', $this->normalizePath($path));

        while (count($parts)) {
            $currentPath = implode('/', $parts);
            if (file_exists($currentPath)) {
                return $currentPath;
            }
            array_pop($parts);
        }

        return null;
    }

    /**
     * Determines whether the requested path is the base file.
     *
     * @param string $path
     * @return bool
     * @deprecated Currently not used
     */
    protected function isBaseFile($path)
    {
        $path = $this->normalizePath($path);
        $baseFile = $this->determineBaseFile($path);
        return $path === $baseFile;
    }

    /**
     * Asserts the given path to a Phar file.
     *
     * @param string $path
     * @throws PharStreamWrapperException
     */
    protected function assertPath($path)
    {
        if (!$this->isAllowed($path)) {
            throw new PharStreamWrapperException(
                sprintf('Executing  %s is denied', $path),
                1530103998
            );
        }
    }

    protected function resetOpCache()
    {
        if (function_exists('opcache_reset')
            && function_exists('opcache_get_status')
            && !empty(opcache_get_status()['opcache_enabled'])
        ) {
            opcache_reset();
        }
    }

    /**
     * Invokes commands on the native PHP Phar stream wrapper.
     *
     * @param string $functionName
     * @param array $arguments
     * @return mixed
     */
    protected function invokeInternalStreamWrapper($functionName, array $arguments)
    {
        $silentExecution = $functionName{0} === '@';
        $functionName = ltrim($functionName, '@');
        $this->restoreInternalSteamWrapper();

        if ($silentExecution) {
            $result = @call_user_func_array($functionName, $arguments);
        } else {
            $result = call_user_func_array($functionName, $arguments);
        }

        $this->registerStreamWrapper();
        return $result;
    }

    protected function restoreInternalSteamWrapper()
    {
        stream_wrapper_restore('phar');
    }

    protected function registerStreamWrapper()
    {
        stream_wrapper_unregister('phar');
        stream_wrapper_register('phar', static::class);
    }
}
