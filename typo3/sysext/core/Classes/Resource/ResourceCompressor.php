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

namespace TYPO3\CMS\Core\Resource;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

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
     * gzipped versions are only created if $TYPO3_CONF_VARS['BE' or 'FE']['compressionLevel'] is set
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
        if (!is_dir(Environment::getPublicPath() . '/' . $this->targetDirectory)) {
            GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/' . $this->targetDirectory);
        }
        // if enabled, we check whether we should auto-create the .htaccess file
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['generateApacheHtaccess']) {
            // check whether .htaccess exists
            $htaccessPath = Environment::getPublicPath() . '/' . $this->targetDirectory . '.htaccess';
            if (!file_exists($htaccessPath)) {
                GeneralUtility::writeFile($htaccessPath, $this->htaccessTemplate);
            }
        }

        // String 'FE' if in FrontendApplication, else 'BE' (also in CLI without request object)
        // @todo: Usually, the ResourceCompressor similar to PageRenderer does not make sense if there is no request object ... restrict this?
        $applicationType = ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend() ? 'FE' : 'BE';
        // decide whether we should create gzipped versions or not
        $compressionLevel = $GLOBALS['TYPO3_CONF_VARS'][$applicationType]['compressionLevel'];
        // we need zlib for gzencode()
        if (extension_loaded('zlib') && $compressionLevel) {
            $this->createGzipped = true;
            // $compressionLevel can also be TRUE
            if (MathUtility::canBeInterpretedAsInteger($compressionLevel)) {
                $this->gzipCompressionLevel = (int)$compressionLevel;
            }
        }
        $this->setRootPath($applicationType === 'BE' ? Environment::getBackendPath() . '/' : Environment::getPublicPath() . '/');
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
     * @param array $cssFiles CSS files to process
     * @return array CSS files
     */
    public function concatenateCssFiles(array $cssFiles)
    {
        $filesToIncludeByType = ['all' => []];
        foreach ($cssFiles as $key => $fileOptions) {
            // no concatenation allowed for this file, so continue
            if (!empty($fileOptions['excludeFromConcatenation'])) {
                continue;
            }
            $filenameFromMainDir = $this->getFilenameFromMainDir($fileOptions['file']);
            $type = isset($fileOptions['media']) ? strtolower($fileOptions['media']) : 'all';
            if (!isset($filesToIncludeByType[$type])) {
                $filesToIncludeByType[$type] = [];
            }
            if (!empty($fileOptions['forceOnTop'])) {
                array_unshift($filesToIncludeByType[$type], $filenameFromMainDir);
            } else {
                $filesToIncludeByType[$type][] = $filenameFromMainDir;
            }
            // remove the file from the incoming file array
            unset($cssFiles[$key]);
        }
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
                'allWrap' => '',
            ];
            // place the merged stylesheet on top of the stylesheets
            $cssFiles = array_merge($cssFiles, [$targetFile => $concatenatedOptions]);
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
        $concatenatedJsFileIsAsync = false;
        $allFilesToConcatenateAreAsync = true;
        $filesToInclude = [];
        foreach ($jsFiles as $key => $fileOptions) {
            // invalid section found or no concatenation allowed, so continue
            if (empty($fileOptions['section']) || !empty($fileOptions['excludeFromConcatenation']) || !empty($fileOptions['nomodule']) || !empty($fileOptions['defer'])) {
                continue;
            }
            if (!isset($filesToInclude[$fileOptions['section']])) {
                $filesToInclude[$fileOptions['section']] = [];
            }
            $filenameFromMainDir = $this->getFilenameFromMainDir($fileOptions['file']);
            if (!empty($fileOptions['forceOnTop'])) {
                array_unshift($filesToInclude[$fileOptions['section']], $filenameFromMainDir);
            } else {
                $filesToInclude[$fileOptions['section']][] = $filenameFromMainDir;
            }
            if (!empty($fileOptions['async']) && (bool)$fileOptions['async']) {
                $concatenatedJsFileIsAsync = true;
            } else {
                $allFilesToConcatenateAreAsync = false;
            }
            // remove the file from the incoming file array
            unset($jsFiles[$key]);
        }
        if (!empty($filesToInclude)) {
            $defaultTypeAttributeForJavaScript = $this->getJavaScriptFileType();
            foreach ($filesToInclude as $section => $files) {
                $targetFile = $this->createMergedJsFile($files);
                $concatenatedOptions = [
                    'file' => $targetFile,
                    'type' => $defaultTypeAttributeForJavaScript,
                    'section' => $section,
                    'compress' => true,
                    'excludeFromConcatenation' => true,
                    'forceOnTop' => false,
                    'allWrap' => '',
                    'async' => $concatenatedJsFileIsAsync && $allFilesToConcatenateAreAsync,
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
        // we add up the filenames, filemtimes and filesizes to later build a checksum over
        // it and include it in the temporary file name
        $unique = '';
        foreach ($filesToInclude as $key => $filename) {
            if (GeneralUtility::isValidUrl($filename)) {
                // check if it is possibly a local file with fully qualified URL
                if (GeneralUtility::isOnCurrentHost($filename) &&
                    str_starts_with(
                        $filename,
                        $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getSiteUrl()
                    )
                ) {
                    // attempt to turn it into a local file path
                    $localFilename = substr($filename, strlen($GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getSiteUrl()));
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
        if (!file_exists(Environment::getPublicPath() . '/' . $targetFile)) {
            $concatenated = '';
            // concatenate all the files together
            foreach ($filesToInclude as $filename) {
                $filenameAbsolute = GeneralUtility::resolveBackPath($this->rootPath . $filename);
                $filename = PathUtility::stripPathSitePrefix($filenameAbsolute);
                $contents = (string)file_get_contents($filenameAbsolute);
                // remove any UTF-8 byte order mark (BOM) from files
                if (strpos($contents, "\xEF\xBB\xBF") === 0) {
                    $contents = substr($contents, 3);
                }
                // only fix paths if files aren't already in typo3temp (already processed)
                if ($type === 'css' && !str_starts_with($filename, $this->targetDirectory)) {
                    $contents = $this->cssFixRelativeUrlPaths($contents, $filename);
                }
                $concatenated .= LF . $contents;
            }
            // move @charset, @import and @namespace statements to top of new file
            if ($type === 'css') {
                $concatenated = $this->cssFixStatements($concatenated);
            }
            GeneralUtility::writeFile(Environment::getPublicPath() . '/' . $targetFile, $concatenated);
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
        if (!file_exists(Environment::getPublicPath() . '/' . $targetFile) || $this->createGzipped && !file_exists(Environment::getPublicPath() . '/' . $targetFile . '.gzip')) {
            $contents = $this->compressCssString((string)file_get_contents($filenameAbsolute));
            if (!str_contains($filename, $this->targetDirectory)) {
                $contents = $this->cssFixRelativeUrlPaths($contents, $filename);
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
        if (!file_exists(Environment::getPublicPath() . '/' . $targetFile) || $this->createGzipped && !file_exists(Environment::getPublicPath() . '/' . $targetFile . '.gzip')) {
            $contents = (string)file_get_contents($filenameAbsolute);
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
         * - Environment::getPublicPath() = /var/www/html/sites/site1/
         * - $this->rootPath = /var/www/html/sites/site1/typo3
         *
         * The file names passed into this function may be either:
         * - relative to $this->rootPath
         * - relative to Environment::getPublicPath()
         * - relative to docRoot
         */
        $docRoot = GeneralUtility::getIndpEnv('TYPO3_DOCUMENT_ROOT');
        $fileNameWithoutSlash = ltrim($filename, '/');

        // if the file is an absolute reference within the docRoot
        $absolutePath = $docRoot . '/' . $fileNameWithoutSlash;
        // If the $filename stems from a call to PathUtility::getAbsoluteWebPath() it has a leading slash,
        // hence isAbsolutePath() results in true, which is obviously wrong. Check file existence to be sure.
        // Calling is_file without @ for a path starting with '../' causes a PHP Warning when using open_basedir restriction
        if (PathUtility::isAbsolutePath($filename) && @is_file($filename)) {
            $absolutePath = $filename;
        }
        if (@is_file($absolutePath)) {
            $absolutePath = Environment::getPublicPath() . '/' . PathUtility::getAbsoluteWebPath($absolutePath, false);
            if (strpos($absolutePath, $this->rootPath) === 0) {
                // the path is within the current root path, simply strip rootPath off
                return substr($absolutePath, strlen($this->rootPath));
            }
            // the path is not within the root path, strip off the site path, the remaining logic below
            // takes care about adjusting the path correctly.
            $filename = substr($absolutePath, strlen(Environment::getPublicPath() . '/'));
        }
        // if the file exists in the root path, just return the $filename
        if (is_file($this->rootPath . $fileNameWithoutSlash)) {
            return $fileNameWithoutSlash;
        }
        // if the file is from a special TYPO3 internal directory, add the missing typo3/ prefix
        if (is_file((string)realpath(Environment::getBackendPath() . '/' . $filename))) {
            $filename = 'typo3/' . $filename;
        }
        // build the file path relative to the public web path
        if (PathUtility::isExtensionPath($filename)) {
            $file = Environment::getPublicPath() . '/' . PathUtility::getPublicResourceWebPath($filename, false);
        } elseif (strpos($filename, '../') === 0) {
            $file = GeneralUtility::resolveBackPath(Environment::getBackendPath() . '/' . $filename);
        } else {
            $file = Environment::getPublicPath() . '/' . $filename;
        }

        // check if the file exists, and if so, return the path relative to current PHP script
        if (is_file($file)) {
            return rtrim((string)PathUtility::getRelativePathTo($file), '/');
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
            if (str_starts_with($filename, $baseDirectory)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $contents
     * @param string $filename
     * @return string
     */
    protected function cssFixRelativeUrlPaths(string $contents, string $filename): string
    {
        $newDir = '../../../' . PathUtility::dirname($filename) . '/';
        return $this->getPathFixer()->fixRelativeUrlPaths($contents, $newDir);
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
        GeneralUtility::writeFile(Environment::getPublicPath() . '/' . $filename, $contents);
        if ($this->createGzipped) {
            // create compressed version
            GeneralUtility::writeFile(Environment::getPublicPath() . '/' . $filename . '.gzip', (string)gzencode($contents, $this->gzipCompressionLevel));
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
        if ($this->createGzipped && str_contains(GeneralUtility::getIndpEnv('HTTP_ACCEPT_ENCODING'), 'gzip')) {
            $filename .= '.gzip';
        }
        return PathUtility::getRelativePath($this->rootPath, Environment::getPublicPath() . '/') . $filename;
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
        if (!file_exists(Environment::getPublicPath() . '/' . $filename)
            || !hash_equals(md5((string)file_get_contents(Environment::getPublicPath() . '/' . $filename)), md5($externalContent))
        ) {
            GeneralUtility::writeFile(Environment::getPublicPath() . '/' . $filename, $externalContent);
        }
        return $filename;
    }

    public function compressJavaScriptSource(string $javaScriptSourceCode): string
    {
        $fakeThis = null;
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['minifyJavaScript'] ?? [] as $hookMethod) {
            try {
                $parameters = ['script' => $javaScriptSourceCode];
                $javaScriptSourceCode = GeneralUtility::callUserFunction($hookMethod, $parameters, $fakeThis);
            } catch (\Exception $e) {
                GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__)->warning('Error minifying Javascript: {file}, hook: {hook}', [
                    'file' => $javaScriptSourceCode,
                    'hook' => $hookMethod,
                    'exception' => $e,
                ]);
            }
        }
        return $javaScriptSourceCode;
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
        $contents = (string)preg_replace(
            "<($double_quot|$single_quot)|$comment>Ss",
            '$1',
            $contents
        );
        // Remove certain whitespace.
        // There are different conditions for removing leading and trailing
        // whitespace.
        // @see https://php.net/manual/regexp.reference.subpatterns.php
        $contents = (string)preg_replace(
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

    /**
     * Determines the the JavaScript mime type
     *
     * The <script> tag only needs the type if the page is not rendered as HTML5.
     * In TYPO3 Backend or when TSFE is not available we always use HTML5.
     * For TYPO3 Frontend the configured config.doctype is evaluated.
     *
     * @return string
     */
    protected function getJavaScriptFileType(): string
    {
        if (!isset($GLOBALS['TSFE'])
            || !($GLOBALS['TSFE'] instanceof TypoScriptFrontendController)
            || ($GLOBALS['TSFE']->config['config']['doctype'] ?? 'html5') === 'html5'
        ) {
            // Backend, no TSFE, or doctype set to html5
            return '';
        }
        return 'text/javascript';
    }

    protected function getPathFixer(): RelativeCssPathFixer
    {
        return GeneralUtility::makeInstance(RelativeCssPathFixer::class);
    }
}
