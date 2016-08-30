<?php
namespace TYPO3\CMS\Core\Tests;

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

/**
 * Stream wrapper for the file:// protocol
 *
 * Implementation details:
 * Due to the nature of PHP, it is not possible to switch to the default handler
 * other then restoring the default handler for file:// and registering it again
 * around each call.
 * It is important that the default handler is restored to allow autoloading (including)
 * of files during the test run.
 * For each method allowed to pass paths, the passed path is checked against the
 * the list of paths to overlay and rewritten if needed.
 *
 * = Usage =
 * <code title="Add use statements">
 * use org\bovigo\vfs\vfsStream;
 * use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;
 * </code>
 *
 * <code title="Usage in test">
 * $root = \org\bovigo\vfs\vfsStream::setup('root');
 * $subfolder = \org\bovigo\vfs\vfsStream::newDirectory('fileadmin');
 * $root->addChild($subfolder);
 * // Load fixture files and folders from disk
 * \org\bovigo\vfs\vfsStream::copyFromFileSystem(__DIR__ . '/Fixture/Files', $subfolder, 1024*1024);
 * FileStreamWrapper::init(PATH_site);
 * FileStreamWrapper::registerOverlayPath('fileadmin', 'vfs://root/fileadmin');
 *
 * // Use file functions as usual
 * mkdir(PATH_site . 'fileadmin/test/');
 * $file = PATH_site . 'fileadmin/test/Foo.bar';
 * file_put_contents($file, 'Baz');
 * $content = file_get_contents($file);
 * $this->assertSame('Baz', $content);
 *
 * $this->assertEqual(**array(file system structure as array**), vfsStream::inspect(new vfsStreamStructureVisitor())->getStructure());
 *
 * FileStreamWrapper::destroy();
 * </code>
 *
 * @see http://www.php.net/manual/en/class.streamwrapper.php
 */
class FileStreamWrapper
{
    /**
     * @var resource
     */
    protected $dirHandle = null;

    /**
     * @var resource
     */
    protected $fileHandle = null;

    /**
     * Switch whether class has already been registered as stream wrapper or not
     *
     * @type bool
     */
    protected static $registered = false;

    /**
     * Array of paths to overlay
     *
     * @var array
     */
    protected static $overlayPaths = [];

    /**
     * The first part of each (absolute) path that shall be ignored
     *
     * @var string
     */
    protected static $rootPath = '';

    /**
     * Initialize the stream wrapper with a root path and register itself
     *
     * @param $rootPath
     * @return void
     */
    public static function init($rootPath)
    {
        self::$rootPath = rtrim(str_replace('\\', '/', $rootPath), '/') . '/';
        self::register();
    }

    /**
     * Unregister the stream wrapper and reset all static members to their default values
     * @return void
     */
    public static function destroy()
    {
        self::$overlayPaths = [];
        self::$rootPath = '';
        if (self::$registered) {
            self::restore();
        }
    }

    /**
     * Register a path relative to the root path (set in init) to be overlaid
     *
     * @param string $overlay Relative path to the root folder
     * @param string $replace The path that should replace the overlay path
     * @param bool $createFolder TRUE of the folder should be created (mkdir)
     * @return void
     */
    public static function registerOverlayPath($overlay, $replace, $createFolder = true)
    {
        $overlay = trim(str_replace('\\', '/', $overlay), '/') . '/';
        $replace = rtrim(str_replace('\\', '/', $replace), '/') . '/';
        self::$overlayPaths[$overlay] = $replace;
        if ($createFolder) {
            mkdir($replace);
        }
    }

    /**
     * Checks and overlays a path
     *
     * @param string $path The path to check
     * @return string The potentially overlaid path
     */
    protected static function overlayPath($path)
    {
        $path = str_replace('\\', '/', $path);
        $hasOverlay = false;
        if (strpos($path, self::$rootPath) !== 0) {
            // Path is not below root path, ignore it
            return $path;
        }

        $newPath = ltrim(substr($path, strlen(self::$rootPath)), '/');
        foreach (self::$overlayPaths as $overlay => $replace) {
            if (strpos($newPath, $overlay) === 0) {
                $newPath = $replace . substr($newPath, strlen($overlay));
                $hasOverlay = true;
                break;
            }
        }
        return $hasOverlay ? $newPath : $path;
    }

    /**
     * Method to register the stream wrapper
     *
     * If the stream is already registered the method returns silently. If there
     * is already another stream wrapper registered for the scheme used by
     * file:// scheme a \BadFunctionCallException will be thrown.
     *
     * @throws \BadFunctionCallException
     * @return void
     */
    protected static function register()
    {
        if (self::$registered) {
            return;
        }

        if (@stream_wrapper_unregister('file') === false) {
            throw new \BadFunctionCallException('Cannot unregister file:// stream wrapper.', 1396340331);
        }
        if (@stream_wrapper_register('file', __CLASS__) === false) {
            throw new \BadFunctionCallException('A handler has already been registered for the file:// scheme.', 1396340332);
        }

        self::$registered = true;
    }

    /**
     * Restore the file handler
     *
     * @return void
     */
    protected static function restore()
    {
        if (!self::$registered) {
            return;
        }
        if (@stream_wrapper_restore('file') === false) {
            throw new \BadFunctionCallException('Cannot restore the default file:// stream handler.', 1396340333);
        }
        self::$registered = false;
    }

    /*
     * The following list of functions is implemented as of
     * @see http://www.php.net/manual/en/streamwrapper.dir-closedir.php
     */

    /**
     * Close the directory
     *
     * @return bool
     */
    public function dir_closedir()
    {
        if ($this->dirHandle === null) {
            return false;
        } else {
            self::restore();
            closedir($this->dirHandle);
            self::register();
            $this->dirHandle = null;
            return true;
        }
    }

    /**
     * Opens a directory for reading
     *
     * @param string $path
     * @param int $options
     * @return bool
     */
    public function dir_opendir($path, $options = 0)
    {
        if ($this->dirHandle !== null) {
            return false;
        }
        self::restore();
        $path = self::overlayPath($path);
        $this->dirHandle = opendir($path);
        self::register();
        return $this->dirHandle !== false;
    }

    /**
     * Read a single filename of a directory
     *
     * @return string|bool
     */
    public function dir_readdir()
    {
        if ($this->dirHandle === null) {
            return false;
        }
        self::restore();
        $success = readdir($this->dirHandle);
        self::register();
        return $success;
    }

    /**
     * Reset directory name pointer
     *
     * @return bool
     */
    public function dir_rewinddir()
    {
        if ($this->dirHandle === null) {
            return false;
        }
        self::restore();
        rewinddir($this->dirHandle);
        self::register();
        return true;
    }

    /**
     * Create a directory
     *
     * @param string $path
     * @param int $mode
     * @param int $options
     * @return bool
     */
    public function mkdir($path, $mode, $options = 0)
    {
        self::restore();
        $path = self::overlayPath($path);
        $success = mkdir($path, $mode, (bool)($options & STREAM_MKDIR_RECURSIVE));
        self::register();
        return $success;
    }

    /**
     * Rename a file
     *
     * @param string $pathFrom
     * @param string $pathTo
     * @return bool
     */
    public function rename($pathFrom, $pathTo)
    {
        self::restore();
        $pathFrom = self::overlayPath($pathFrom);
        $pathTo = self::overlayPath($pathTo);
        $success = rename($pathFrom, $pathTo);
        self::register();
        return $success;
    }

    /**
     * Remove a directory
     *
     * @param string $path
     * @return bool
     */
    public function rmdir($path)
    {
        self::restore();
        $path = self::overlayPath($path);
        $success = rmdir($path);
        self::register();
        return $success;
    }

    /**
     * Retrieve the underlying resource
     *
     * @param int $castAs Can be STREAM_CAST_FOR_SELECT when stream_select()
     * is calling stream_cast() or STREAM_CAST_AS_STREAM when stream_cast()
     * is called for other uses.
     * @return resource|bool
     */
    public function stream_cast($castAs)
    {
        if ($this->fileHandle !== null && $castAs & STREAM_CAST_AS_STREAM) {
            return $this->fileHandle;
        } else {
            return false;
        }
    }

    /**
     * Close a file
     *
     */
    public function stream_close()
    {
        self::restore();
        if ($this->fileHandle !== null) {
            fclose($this->fileHandle);
            $this->fileHandle = null;
        }
        self::register();
    }

    /**
     * Test for end-of-file on a file pointer
     *
     * @return bool
     */
    public function stream_eof()
    {
        if ($this->fileHandle === null) {
            return false;
        }
        self::restore();
        $success = feof($this->fileHandle);
        self::register();
        return $success;
    }

    /**
     * Flush the output
     *
     * @return bool
     */
    public function stream_flush()
    {
        if ($this->fileHandle === null) {
            return false;
        }
        self::restore();
        $success = fflush($this->fileHandle);
        self::register();
        return $success;
    }

    /**
     * Advisory file locking
     *
     * @param int $operation
     * @return bool
     */
    public function stream_lock($operation)
    {
        if ($this->fileHandle === null) {
            return false;
        }
        self::restore();
        $success = flock($this->fileHandle, $operation);
        self::register();
        return $success;
    }

    /**
     * Change file options
     *
     * @param string $path
     * @param int $options
     * @param mixed $value
     * @return bool
     */
    public function stream_metadata($path, $options, $value)
    {
        self::restore();
        $path = self::overlayPath($path);
        switch ($options) {
            case STREAM_META_TOUCH:
                $success = touch($path, $value[0], $value[1]);
                break;
            case STREAM_META_OWNER_NAME:
                // Fall through
            case STREAM_META_OWNER:
                $success = chown($path, $value);
                break;
            case STREAM_META_GROUP_NAME:
                // Fall through
            case STREAM_META_GROUP:
                $success = chgrp($path, $value);
                break;
            case STREAM_META_ACCESS:
                $success = chmod($path, $value);
                break;
            default:
                $success = false;
        }
        self::register();
        return $success;
    }

    /**
     * Open a file
     *
     * @param string $path
     * @param string $mode
     * @param int $options
     * @param string &$opened_path
     * @return bool
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        if ($this->fileHandle !== null) {
            return false;
        }
        self::restore();
        $path = self::overlayPath($path);
        $this->fileHandle = fopen($path, $mode, (bool)($options & STREAM_USE_PATH));
        self::register();
        return $this->fileHandle !== false;
    }

    /**
     * Read from a file
     *
     * @param int $length
     * @return string
     */
    public function stream_read($length)
    {
        if ($this->fileHandle === null) {
            return false;
        }
        self::restore();
        $content = fread($this->fileHandle, $length);
        self::register();
        return $content;
    }

    /**
     * Seek to specific location in a stream
     *
     * @param int $offset
     * @param int $whence = SEEK_SET
     * @return bool
     */
    public function stream_seek($offset, $whence = SEEK_SET)
    {
        if ($this->fileHandle === null) {
            return false;
        }
        self::restore();
        $success = fseek($this->fileHandle, $offset, $whence);
        self::register();
        return $success;
    }

    /**
     * Change stream options (not implemented)
     *
     * @param int $option
     * @param int $arg1
     * @param int $arg2
     * @return bool
     */
    public function stream_set_option($option, $arg1, $arg2)
    {
        return false;
    }

    /**
     * Retrieve information about a file resource
     *
     * @return array
     */
    public function stream_stat()
    {
        if ($this->fileHandle === null) {
            return false;
        }
        self::restore();
        $stats = fstat($this->fileHandle);
        self::register();
        return $stats;
    }

    /**
     * Retrieve the current position of a stream
     *
     * @return int
     */
    public function stream_tell()
    {
        if ($this->fileHandle === null) {
            return false;
        }
        self::restore();
        $position = ftell($this->fileHandle);
        self::register();
        return $position;
    }

    /**
     * Truncates a file to the given size
     *
     * @param int $size Truncate to this size
     * @return bool
     */
    public function stream_truncate($size)
    {
        if ($this->fileHandle === null) {
            return false;
        }
        self::restore();
        $success = ftruncate($this->fileHandle, $size);
        self::register();
        return $success;
    }

    /**
     * Write to stream
     *
     * @param string $data
     * @return int
     */
    public function stream_write($data)
    {
        if ($this->fileHandle === null) {
            return false;
        }
        self::restore();
        $length = fwrite($this->fileHandle, $data);
        self::register();
        return $length;
    }

    /**
     * Unlink a file
     *
     * @param string $path
     * @return bool
     */
    public function unlink($path)
    {
        self::restore();
        $path = self::overlayPath($path);
        $success = unlink($path);
        self::register();
        return $success;
    }

    /**
     * Retrieve information about a file
     *
     * @param string $path
     * @param int $flags
     * @return array
     */
    public function url_stat($path, $flags)
    {
        self::restore();
        $path = self::overlayPath($path);
        if ($flags & STREAM_URL_STAT_LINK) {
            $information = @lstat($path);
        } else {
            $information = @stat($path);
        }
        self::register();
        return $information;
    }
}
