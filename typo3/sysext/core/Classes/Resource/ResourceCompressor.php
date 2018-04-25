<?php
namespace TYPO3\CMS\Core\Resource;

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
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Compressor
 * This merges and compresses CSS and JavaScript files of the TYPO3 Backend.
 */
class ResourceCompressor
{
    /**
     * @var string
     */
    protected $targetDirectory = 'typo3temp/assets/compressed/';

    /**
     * @var string
     */
    protected $rootPath = '';

    /**
     * gzipped versions are only created if $TYPO3_CONF_VARS[TYPO3_MODE]['compressionLevel'] is set
     *
     * @var bool
     */
    protected $createGzipped = false;

    /**
     * @var int
     */
    protected $gzipCompressionLevel = -1;

    /**
     * @var string
     */
    protected $htaccessTemplate = '<FilesMatch "\\.(js|css)(\\.gzip)?$">
	<IfModule mod_expires.c>
		ExpiresActive on
		ExpiresDefault "access plus 7 days"
	</IfModule>
	FileETag MTime Size
</FilesMatch>';

    /**
     * Constructor
     */
    public function __construct()
    {
        // we check for existence of our targetDirectory
        if (!is_dir(PATH_site . $this->targetDirectory)) {
            GeneralUtility::mkdir_deep(PATH_site . $this->targetDirectory);
        }
        // if enabled, we check whether we should auto-create the .htaccess file
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['generateApacheHtaccess']) {
            // check whether .htaccess exists
            $htaccessPath = PATH_site . $this->targetDirectory . '.htaccess';
            if (!file_exists($htaccessPath)) {
                GeneralUtility::writeFile($htaccessPath, $this->htaccessTemplate);
            }
        }
        // decide whether we should create gzipped versions or not
        $compressionLevel = $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['compressionLevel'];
        // we need zlib for gzencode()
        if (extension_loaded('zlib') && $compressionLevel) {
            $this->createGzipped = true;
            // $compressionLevel can also be TRUE
            if (MathUtility::canBeInterpretedAsInteger($compressionLevel)) {
                $this->gzipCompressionLevel = (int)$compressionLevel;
            }
        }
        $this->setRootPath(TYPO3_MODE === 'BE' ? PATH_typo3 : PATH_site);
    }

    /**
     * Sets absolute path to working directory
     *
     * @param string $rootPath Absolute path
     */
    public function setRootPath($rootPath)
    {
        if (is_string($rootPath)) {
            $this->rootPath = $rootPath;
        }
    }

    /**
     * Concatenates the Stylesheet files
     *
     * Options:
     * baseDirectories If set, only include files below one of the base directories
     *
     * @param array $cssFiles CSS files to process
     * @param array $options Additional options
     * @return array CSS files
     */
    public function concatenateCssFiles(array $cssFiles, array $options = [])
    {
        $filesToIncludeByType = ['all' => []];
        foreach ($cssFiles as $key => $fileOptions) {
            // no concatenation allowed for this file, so continue
            if (!empty($fileOptions['excludeFromConcatenation'])) {
                continue;
            }
            $filenameFromMainDir = $this->getFilenameFromMainDir($fileOptions['file']);
            // if $options['baseDirectories'] set, we only include files below these directories
            if (
                !isset($options['baseDirectories'])
                || $this->checkBaseDirectory(
                    $filenameFromMainDir,
                    array_merge($options['baseDirectories'], [$this->targetDirectory])
                )
            ) {
                $type = isset($fileOptions['media']) ? strtolower($fileOptions['media']) : 'all';
                if (!isset($filesToIncludeByType[$type])) {
                    $filesToIncludeByType[$type] = [];
                }
                if ($fileOptions['forceOnTop']) {
                    array_unshift($filesToIncludeByType[$type], $filenameFromMainDir);
                } else {
                    $filesToIncludeByType[$type][] = $filenameFromMainDir;
                }
                // remove the file from the incoming file array
                unset($cssFiles[$key]);
            }
        }
        if (!empty($filesToIncludeByType)) {
            foreach ($filesToIncludeByType as $mediaOption => $filesToInclude) {
                if (empty($filesToInclude)) {
                    continue;
                }
                $targetFile = $this->createMergedCssFile($filesToInclude);
                $concatenatedOptions = [
                    'file' => $targetFile,
                    'rel' => 'stylesheet',
                    'media' => $mediaOption,
                    'compress' => true,
                    'excludeFromConcatenation' => true,
                    'forceOnTop' => false,
                    'allWrap' => ''
                ];
                // place the merged stylesheet on top of the stylesheets
                $cssFiles = array_merge($cssFiles, [$targetFile => $concatenatedOptions]);
            }
        }
        return $cssFiles;
    }

    /**
     * Concatenates the JavaScript files
     *
     * @param array $jsFiles JavaScript files to process
     * @return array JS files
     */
    public function concatenateJsFiles(array $jsFiles)
    {
        $filesToInclude = [];
        foreach ($jsFiles as $key => $fileOptions) {
            // invalid section found or no concatenation allowed, so continue
            if (empty($fileOptions['section']) || !empty($fileOptions['excludeFromConcatenation'])) {
                continue;
            }
            if (!isset($filesToInclude[$fileOptions['section']])) {
                $filesToInclude[$fileOptions['section']] = [];
            }
            $filenameFromMainDir = $this->getFilenameFromMainDir($fileOptions['file']);
            if ($fileOptions['forceOnTop']) {
                array_unshift($filesToInclude[$fileOptions['section']], $filenameFromMainDir);
            } else {
                $filesToInclude[$fileOptions['section']][] = $filenameFromMainDir;
            }
            // remove the file from the incoming file array
            unset($jsFiles[$key]);
        }
        if (!empty($filesToInclude)) {
            foreach ($filesToInclude as $section => $files) {
                $targetFile = $this->createMergedJsFile($files);
                $concatenatedOptions = [
                    'file' => $targetFile,
                    'type' => 'text/javascript',
                    'section' => $section,
                    'compress' => true,
                    'excludeFromConcatenation' => true,
                    'forceOnTop' => false,
                    'allWrap' => ''
                ];
                // place the merged javascript on top of the JS files
                $jsFiles = array_merge([$targetFile => $concatenatedOptions], $jsFiles);
            }
        }
        return $jsFiles;
    }

    /**
     * Creates a merged CSS file
     *
     * @param array $filesToInclude Files which should be merged, paths relative to root path
     * @return mixed Filename of the merged file
     */
    protected function createMergedCssFile(array $filesToInclude)
    {
        return $this->createMergedFile($filesToInclude, 'css');
    }

    /**
     * Creates a merged JS file
     *
     * @param array $filesToInclude Files which should be merged, paths relative to root path
     * @return mixed Filename of the merged file
     */
    protected function createMergedJsFile(array $filesToInclude)
    {
        return $this->createMergedFile($filesToInclude, 'js');
    }

    /**
     * Creates a merged file with given file type
     *
     * @param array $filesToInclude Files which should be merged, paths relative to root path
     * @param string $type File type
     *
     * @throws \InvalidArgumentException
     * @return mixed Filename of the merged file
     */
    protected function createMergedFile(array $filesToInclude, $type = 'css')
    {
        // Get file type
        $type = strtolower(trim($type, '. '));
        if (empty($type)) {
            throw new \InvalidArgumentException('No valid file type given for files to be merged.', 1308957498);
        }
        // we add up the filenames, filemtimes and filsizes to later build a checksum over
        // it and include it in the temporary file name
        $unique = '';
        foreach ($filesToInclude as $key => $filename) {
            if (GeneralUtility::isValidUrl($filename)) {
                // check if it is possibly a local file with fully qualified URL
                if (GeneralUtility::isOnCurrentHost($filename) &&
                    GeneralUtility::isFirstPartOfStr(
                        $filename,
                        GeneralUtility::getIndpEnv('TYPO3_SITE_URL')
                    )
                ) {
                    // attempt to turn it into a local file path
                    $localFilename = substr($filename, strlen(GeneralUtility::getIndpEnv('TYPO3_SITE_URL')));
                    if (@is_file(GeneralUtility::resolveBackPath($this->rootPath . $localFilename))) {
                        $filesToInclude[$key] = $localFilename;
                    } else {
                        $filesToInclude[$key] = $this->retrieveExternalFile($filename);
                    }
                } else {
                    $filesToInclude[$key] = $this->retrieveExternalFile($filename);
                }
                $filename = $filesToInclude[$key];
            }
            $filenameAbsolute = GeneralUtility::resolveBackPath($this->rootPath . $filename);
            if (@file_exists($filenameAbsolute)) {
                $fileStatus = stat($filenameAbsolute);
                $unique .= $filenameAbsolute . $fileStatus['mtime'] . $fileStatus['size'];
            } else {
                $unique .= $filenameAbsolute;
            }
        }
        $targetFile = $this->targetDirectory . 'merged-' . md5($unique) . '.' . $type;
        // if the file doesn't already exist, we create it
        if (!file_exists(PATH_site . $targetFile)) {
            $concatenated = '';
            // concatenate all the files together
            foreach ($filesToInclude as $filename) {
                $filenameAbsolute = GeneralUtility::resolveBackPath($this->rootPath . $filename);
                $filename = PathUtility::stripPathSitePrefix($filenameAbsolute);
                $contents = file_get_contents($filenameAbsolute);
                // remove any UTF-8 byte order mark (BOM) from files
                if (strpos($contents, "\xEF\xBB\xBF") === 0) {
                    $contents = substr($contents, 3);
                }
                // only fix paths if files aren't already in typo3temp (already processed)
                if ($type === 'css' && !GeneralUtility::isFirstPartOfStr($filename, $this->targetDirectory)) {
                    $contents = $this->cssFixRelativeUrlPaths($contents, PathUtility::dirname($filename) . '/');
                }
                $concatenated .= LF . $contents;
            }
            // move @charset, @import and @namespace statements to top of new file
            if ($type === 'css') {
                $concatenated = $this->cssFixStatements($concatenated);
            }
            GeneralUtility::writeFile(PATH_site . $targetFile, $concatenated);
        }
        return $targetFile;
    }

    /**
     * Compress multiple css files
     *
     * @param array $cssFiles The files to compress (array key = filename), relative to requested page
     * @return array The CSS files after compression (array key = new filename), relative to requested page
     */
    public function compressCssFiles(array $cssFiles)
    {
        $filesAfterCompression = [];
        foreach ($cssFiles as $key => $fileOptions) {
            // if compression is enabled
            if ($fileOptions['compress']) {
                $filename = $this->compressCssFile($fileOptions['file']);
                $fileOptions['compress'] = false;
                $fileOptions['file'] = $filename;
                $filesAfterCompression[$filename] = $fileOptions;
            } else {
                $filesAfterCompression[$key] = $fileOptions;
            }
        }
        return $filesAfterCompression;
    }

    /**
     * Compresses a CSS file
     *
     * Options:
     * baseDirectories If set, only include files below one of the base directories
     *
     * removes comments and whitespaces
     * Adopted from https://github.com/drupal/drupal/blob/8.0.x/core/lib/Drupal/Core/Asset/CssOptimizer.php
     *
     * @param string $filename Source filename, relative to requested page
     * @return string Compressed filename, relative to requested page
     */
    public function compressCssFile($filename)
    {
        // generate the unique name of the file
        $filenameAbsolute = GeneralUtility::resolveBackPath($this->rootPath . $this->getFilenameFromMainDir($filename));
        if (@file_exists($filenameAbsolute)) {
            $fileStatus = stat($filenameAbsolute);
            $unique = $filenameAbsolute . $fileStatus['mtime'] . $fileStatus['size'];
        } else {
            $unique = $filenameAbsolute;
        }
        // make sure it is again the full filename
        $filename = PathUtility::stripPathSitePrefix($filenameAbsolute);

        $pathinfo = PathUtility::pathinfo($filenameAbsolute);
        $targetFile = $this->targetDirectory . $pathinfo['filename'] . '-' . md5($unique) . '.css';
        // only create it, if it doesn't exist, yet
        if (!file_exists(PATH_site . $targetFile) || $this->createGzipped && !file_exists(PATH_site . $targetFile . '.gzip')) {
            $contents = $this->compressCssString(file_get_contents($filenameAbsolute));
            if (strpos($filename, $this->targetDirectory) === false) {
                $contents = $this->cssFixRelativeUrlPaths($contents, PathUtility::dirname($filename) . '/');
            }
            $this->writeFileAndCompressed($targetFile, $contents);
        }
        return $this->returnFileReference($targetFile);
    }

    /**
     * Compress multiple javascript files
     *
     * @param array $jsFiles The files to compress (array key = filename), relative to requested page
     * @return array The js files after compression (array key = new filename), relative to requested page
     */
    public function compressJsFiles(array $jsFiles)
    {
        $filesAfterCompression = [];
        foreach ($jsFiles as $fileName => $fileOptions) {
            // If compression is enabled
            if ($fileOptions['compress']) {
                $compressedFilename = $this->compressJsFile($fileOptions['file']);
                $fileOptions['compress'] = false;
                $fileOptions['file'] = $compressedFilename;
                $filesAfterCompression[$compressedFilename] = $fileOptions;
            } else {
                $filesAfterCompression[$fileName] = $fileOptions;
            }
        }
        return $filesAfterCompression;
    }

    /**
     * Compresses a javascript file
     *
     * @param string $filename Source filename, relative to requested page
     * @return string Filename of the compressed file, relative to requested page
     */
    public function compressJsFile($filename)
    {
        // generate the unique name of the file
        $filenameAbsolute = GeneralUtility::resolveBackPath($this->rootPath . $this->getFilenameFromMainDir($filename));
        if (@file_exists($filenameAbsolute)) {
            $fileStatus = stat($filenameAbsolute);
            $unique = $filenameAbsolute . $fileStatus['mtime'] . $fileStatus['size'];
        } else {
            $unique = $filenameAbsolute;
        }
        $pathinfo = PathUtility::pathinfo($filename);
        $targetFile = $this->targetDirectory . $pathinfo['filename'] . '-' . md5($unique) . '.js';
        // only create it, if it doesn't exist, yet
        if (!file_exists(PATH_site . $targetFile) || $this->createGzipped && !file_exists(PATH_site . $targetFile . '.gzip')) {
            $contents = file_get_contents($filenameAbsolute);
            $this->writeFileAndCompressed($targetFile, $contents);
        }
        return $this->returnFileReference($targetFile);
    }

    /**
     * Finds the relative path to a file, relative to the root path.
     *
     * @param string $filename the name of the file
     * @return string the path to the file relative to the root path ($this->rootPath)
     */
    protected function getFilenameFromMainDir($filename)
    {
        /*
         * The various paths may have those values (e.g. if TYPO3 is installed in a subdir)
         * - docRoot = /var/www/html/
         * - PATH_site = /var/www/html/sites/site1/
         * - $this->rootPath = /var/www/html/sites/site1/typo3
         *
         * The file names passed into this function may be either:
         * - relative to $this->rootPath
         * - relative to PATH_site
         * - relative to docRoot
         */
        $docRoot = GeneralUtility::getIndpEnv('TYPO3_DOCUMENT_ROOT');
        $fileNameWithoutSlash = ltrim($filename, '/');

        // if the file is an absolute reference within the docRoot
        $absolutePath = $docRoot . '/' . $fileNameWithoutSlash;
        // Calling is_file without @ for a path starting with '../' causes a PHP Warning when using open_basedir restriction
        if (@is_file($absolutePath)) {
            if (strpos($absolutePath, $this->rootPath) === 0) {
                // the path is within the current root path, simply strip rootPath off
                return substr($absolutePath, strlen($this->rootPath));
            }
            // the path is not within the root path, strip off the site path, the remaining logic below
            // takes care about adjusting the path correctly.
            $filename = substr($absolutePath, strlen(PATH_site));
        }
        // if the file exists in the root path, just return the $filename
        if (is_file($this->rootPath . $fileNameWithoutSlash)) {
            return $fileNameWithoutSlash;
        }
        // if the file is from a special TYPO3 internal directory, add the missing typo3/ prefix
        if (is_file(realpath(PATH_site . TYPO3_mainDir . $filename))) {
            $filename = TYPO3_mainDir . $filename;
        }
        // build the file path relatively to the PATH_site
        if (strpos($filename, 'EXT:') === 0) {
            $file = GeneralUtility::getFileAbsFileName($filename);
        } elseif (strpos($filename, '../') === 0) {
            $file = GeneralUtility::resolveBackPath(PATH_typo3 . $filename);
        } else {
            $file = PATH_site . $filename;
        }

        // check if the file exists, and if so, return the path relative to PATH_thisScript
        if (is_file($file)) {
            return rtrim(PathUtility::getRelativePathTo($file), '/');
        }
        // none of above conditions were met, fallback to default behaviour
        return $filename;
    }

    /**
     * Decides whether a file comes from one of the baseDirectories
     *
     * @param string $filename Filename
     * @param array $baseDirectories Base directories
     * @return bool File belongs to a base directory or not
     */
    protected function checkBaseDirectory($filename, array $baseDirectories)
    {
        foreach ($baseDirectories as $baseDirectory) {
            // check, if $filename starts with base directory
            if (GeneralUtility::isFirstPartOfStr($filename, $baseDirectory)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Fixes the relative paths inside of url() references in CSS files
     *
     * @param string $contents Data to process
     * @param string $oldDir Directory of the original file, relative to TYPO3_mainDir
     * @return string Processed data
     */
    protected function cssFixRelativeUrlPaths($contents, $oldDir)
    {
        $newDir = '../../../' . $oldDir;
        // Replace "url()" paths
        if (stripos($contents, 'url') !== false) {
            $regex = '/url(\\(\\s*["\']?(?!\\/)([^"\']+)["\']?\\s*\\))/iU';
            $contents = $this->findAndReplaceUrlPathsByRegex($contents, $regex, $newDir, '(\'|\')');
        }
        // Replace "@import" paths
        if (stripos($contents, '@import') !== false) {
            $regex = '/@import\\s*(["\']?(?!\\/)([^"\']+)["\']?)/i';
            $contents = $this->findAndReplaceUrlPathsByRegex($contents, $regex, $newDir, '"|"');
        }
        return $contents;
    }

    /**
     * Finds and replaces all URLs by using a given regex
     *
     * @param string $contents Data to process
     * @param string $regex Regex used to find URLs in content
     * @param string $newDir Path to prepend to the original file
     * @param string $wrap Wrap around replaced values
     * @return string Processed data
     */
    protected function findAndReplaceUrlPathsByRegex($contents, $regex, $newDir, $wrap = '|')
    {
        $matches = [];
        $replacements = [];
        $wrap = explode('|', $wrap);
        preg_match_all($regex, $contents, $matches);
        foreach ($matches[2] as $matchCount => $match) {
            // remove '," or white-spaces around
            $match = trim($match, '\'" ');
            // we must not rewrite paths containing ":" or "url(", e.g. data URIs (see RFC 2397)
            if (strpos($match, ':') === false && !preg_match('/url\\s*\\(/i', $match)) {
                $newPath = GeneralUtility::resolveBackPath($newDir . $match);
                $replacements[$matches[1][$matchCount]] = $wrap[0] . $newPath . $wrap[1];
            }
        }
        // replace URL paths in content
        if (!empty($replacements)) {
            $contents = str_replace(array_keys($replacements), array_values($replacements), $contents);
        }
        return $contents;
    }

    /**
     * Moves @charset, @import and @namespace statements to the top of
     * the content, because they must occur before all other CSS rules
     *
     * @param string $contents Data to process
     * @return string Processed data
     */
    protected function cssFixStatements($contents)
    {
        $matches = [];
        $comment = LF . '/* moved by compressor */' . LF;
        // nothing to do, so just return contents
        if (stripos($contents, '@charset') === false && stripos($contents, '@import') === false && stripos($contents, '@namespace') === false) {
            return $contents;
        }
        $regex = '/@(charset|import|namespace)\\s*(url)?\\s*\\(?\\s*["\']?[^"\'\\)]+["\']?\\s*\\)?\\s*;/i';
        preg_match_all($regex, $contents, $matches);
        if (!empty($matches[0])) {
            // Ensure correct order of @charset, @namespace and @import
            $charset = '';
            $namespaces = [];
            $imports = [];
            foreach ($matches[1] as $index => $keyword) {
                switch ($keyword) {
                    case 'charset':
                        if (empty($charset)) {
                            $charset = $matches[0][$index];
                        }
                        break;
                    case 'namespace':
                        $namespaces[] = $matches[0][$index];
                        break;
                    case 'import':
                        $imports[] = $matches[0][$index];
                        break;
                }
            }

            $namespaces = !empty($namespaces) ? implode('', $namespaces) . $comment : '';
            $imports = !empty($imports) ? implode('', $imports) . $comment : '';
            // remove existing statements
            $contents = str_replace($matches[0], '', $contents);
            // add statements to the top of contents in the order they occur in original file
            $contents =
                $charset
                . $comment
                . $namespaces
                . $imports
                . trim($contents);
        }
        return $contents;
    }

    /**
     * Writes $contents into file $filename together with a gzipped version into $filename.gz
     *
     * @param string $filename Target filename
     * @param string $contents File contents
     */
    protected function writeFileAndCompressed($filename, $contents)
    {
        // write uncompressed file
        GeneralUtility::writeFile(PATH_site . $filename, $contents);
        if ($this->createGzipped) {
            // create compressed version
            GeneralUtility::writeFile(PATH_site . $filename . '.gzip', gzencode($contents, $this->gzipCompressionLevel));
        }
    }

    /**
     * Decides whether a client can deal with gzipped content or not and returns the according file name,
     * based on HTTP_ACCEPT_ENCODING
     *
     * @param string $filename File name
     * @return string $filename suffixed with '.gzip' or not - dependent on HTTP_ACCEPT_ENCODING
     */
    protected function returnFileReference($filename)
    {
        // if the client accepts gzip and we can create gzipped files, we give him compressed versions
        if ($this->createGzipped && strpos(GeneralUtility::getIndpEnv('HTTP_ACCEPT_ENCODING'), 'gzip') !== false) {
            $filename .= '.gzip';
        }
        return PathUtility::getRelativePath($this->rootPath, PATH_site) . $filename;
    }

    /**
     * Retrieves an external file and stores it locally.
     *
     * @param string $url
     * @return string Temporary local filename for the externally-retrieved file
     */
    protected function retrieveExternalFile($url)
    {
        $externalContent = GeneralUtility::getUrl($url);
        $filename = $this->targetDirectory . 'external-' . md5($url);
        // Write only if file does not exist OR md5 of the content is not the same as fetched one
        if (!file_exists(PATH_site . $filename)
            || (md5($externalContent) !== md5(file_get_contents(PATH_site . $filename)))
        ) {
            GeneralUtility::writeFile(PATH_site . $filename, $externalContent);
        }
        return $filename;
    }

    /**
     * Compress a CSS string by removing comments and whitespace characters
     *
     * @param string $contents
     * @return string
     */
    protected function compressCssString($contents)
    {
        // Perform some safe CSS optimizations.
        // Regexp to match comment blocks.
        $comment = '/\*[^*]*\*+(?:[^/*][^*]*\*+)*/';
        // Regexp to match double quoted strings.
        $double_quot = '"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"';
        // Regexp to match single quoted strings.
        $single_quot = "'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'";
        // Strip all comment blocks, but keep double/single quoted strings.
        $contents = preg_replace(
            "<($double_quot|$single_quot)|$comment>Ss",
            '$1',
            $contents
        );
        // Remove certain whitespace.
        // There are different conditions for removing leading and trailing
        // whitespace.
        // @see http://php.net/manual/regexp.reference.subpatterns.php
        $contents = preg_replace(
            '<
				# Strip leading and trailing whitespace.
				\s*([@{};,])\s*
				# Strip only leading whitespace from:
				# - Closing parenthesis: Retain "@media (bar) and foo".
				| \s+([\)])
				# Strip only trailing whitespace from:
				# - Opening parenthesis: Retain "@media (bar) and foo".
				# - Colon: Retain :pseudo-selectors.
				| ([\(:])\s+
				>xS',
            // Only one of the three capturing groups will match, so its reference
            // will contain the wanted value and the references for the
            // two non-matching groups will be replaced with empty strings.
            '$1$2$3',
            $contents
        );
        // End the file with a new line.
        $contents = trim($contents);
        // Ensure file ends in newline.
        $contents .= LF;
        return $contents;
    }
}
