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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\SystemResource\Exception\CanNotResolveSystemResourceException;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\Publishing\UriGenerationOptions;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\SystemResource\Type\PublicResourceInterface;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\Core\Type\DocType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * This class merges and compresses CSS and JavaScript files of the TYPO3 Frontend.
 * It should never be used for TYPO3 Backend.
 */
class ResourceCompressor
{
    /**
     * @var string
     */
    protected $targetDirectory = 'typo3temp/assets/compressed/';

    /**
     * gzipped versions are only created if $TYPO3_CONF_VARS['BE' or 'FE']['compressionLevel'] is set
     *
     * @var bool
     */
    protected $createGzipped = false;

    protected string $gzipFileExtension = '.gz';

    /**
     * @var int
     */
    protected $gzipCompressionLevel = -1;

    /**
     * @var string
     */
    protected $htaccessTemplate = '<FilesMatch "\\.(js|css)(\\.gz)?$">
	<IfModule mod_expires.c>
		ExpiresActive on
		ExpiresDefault "access plus 7 days"
	</IfModule>
	FileETag MTime Size
</FilesMatch>';

    protected bool $initialized = false;

    public function __construct(
        private readonly SystemResourceFactory $resourceFactory,
        private readonly SystemResourcePublisherInterface $resourcePublisher,
    ) {}

    protected function initialize(): void
    {
        if ($this->initialized) {
            return;
        }
        // we check the existence of our targetDirectory
        if (!is_dir(Environment::getPublicPath() . '/' . $this->targetDirectory)) {
            GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/' . $this->targetDirectory);
        }
        // if enabled, we check whether we should auto-create the .htaccess file
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['generateApacheHtaccess']) {
            // check whether .htaccess exists
            $htaccessPath = Environment::getPublicPath() . '/' . $this->targetDirectory . '.htaccess';
            if (!file_exists($htaccessPath)) {
                GeneralUtility::writeFile($htaccessPath, $this->htaccessTemplate, true);
            }
        }
        // decide whether we should create gzipped versions or not
        $compressionLevel = $GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel'];
        // we need zlib for gzencode()
        if (extension_loaded('zlib') && $compressionLevel) {
            $this->createGzipped = true;
            // $compressionLevel can also be TRUE
            if (MathUtility::canBeInterpretedAsInteger($compressionLevel)) {
                $this->gzipCompressionLevel = (int)$compressionLevel;
            }
        }
        $this->initialized = true;
    }

    /**
     * Concatenates the Stylesheet files
     *
     * @param array $cssFiles CSS files to process
     * @return array CSS files
     */
    public function concatenateCssFiles(array $cssFiles)
    {
        $this->initialize();
        $filesToIncludeByType = ['all' => []];
        foreach ($cssFiles as $key => $fileOptions) {
            // no concatenation allowed for this file, so continue
            if (!empty($fileOptions['excludeFromConcatenation'])) {
                continue;
            }
            $type = isset($fileOptions['media']) ? strtolower($fileOptions['media']) : 'all';
            if (!isset($filesToIncludeByType[$type])) {
                $filesToIncludeByType[$type] = [];
            }
            if (!empty($fileOptions['forceOnTop'])) {
                array_unshift($filesToIncludeByType[$type], $fileOptions['file']);
            } else {
                $filesToIncludeByType[$type][] = $fileOptions['file'];
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
        $this->initialize();
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
            if (!empty($fileOptions['forceOnTop'])) {
                array_unshift($filesToInclude[$fileOptions['section']], $fileOptions['file']);
            } else {
                $filesToInclude[$fileOptions['section']][] = $fileOptions['file'];
            }
            if ($fileOptions['async'] ?? false) {
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
        return $this->createMergedFile($filesToInclude);
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
        foreach ($filesToInclude as $key => $fileToMerge) {
            try {
                $filename = null;
                $resource = $this->resourceFactory->createPublicResource($fileToMerge);
            } catch (CanNotResolveSystemResourceException) {
                $resource = null;
                $filename = $this->getFilenameFromMainDir($fileToMerge);
            }
            if ($resource instanceof SystemResourceInterface) {
                $filesToInclude[$key] = $resource;
                $unique .= $resource->getHash();
            } else {
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
                        if (@is_file(Environment::getPublicPath() . '/' . $localFilename)) {
                            $filesToInclude[$key] = $localFilename;
                        } else {
                            $filesToInclude[$key] = $this->retrieveExternalFile($filename);
                        }
                    } else {
                        $filesToInclude[$key] = $this->retrieveExternalFile($filename);
                    }
                    $filename = $filesToInclude[$key];
                }
                $filenameAbsolute = Environment::getPublicPath() . '/' . $filename;
                if (@file_exists($filenameAbsolute)) {
                    $fileStatus = stat($filenameAbsolute);
                    $unique .= $filenameAbsolute . $fileStatus['mtime'] . $fileStatus['size'];
                } else {
                    $unique .= $filenameAbsolute;
                }
            }
        }
        $targetFile = $this->targetDirectory . 'merged-' . md5($unique) . '.' . $type;
        // if the file doesn't already exist, we create it
        if (!file_exists(Environment::getPublicPath() . '/' . $targetFile)) {
            $concatenated = '';
            // concatenate all the files together
            foreach ($filesToInclude as $fileToMerge) {
                if ($fileToMerge instanceof SystemResourceInterface && $fileToMerge instanceof PublicResourceInterface) {
                    $contents = $fileToMerge->getContents();
                    // @todo: We make an "URL" relative to public dir here for CSS processing.
                    //        check whether this can be done differently
                    //        This should be done asap, because other implementations of SystemResourcePublisherInterface
                    //        might not evaluate the uriPrefix options
                    $fileUrl = ltrim((string)$this->resourcePublisher->generateUri($fileToMerge, null, new UriGenerationOptions(uriPrefix: '', cacheBusting: false)), '/');
                } else {
                    $filename = $fileToMerge;
                    $filenameAbsolute = Environment::getPublicPath() . '/' . $filename;
                    // @todo: We make an "URL" relative to public dir here for CSS processing.
                    //        check whether this can be done differently
                    //        This should be done asap, because other implementations of SystemResourcePublisherInterface
                    //        might not evaluate the uriPrefix options
                    $fileUrl = PathUtility::getAbsoluteWebPath($filenameAbsolute, false);
                    $contents = (string)file_get_contents($filenameAbsolute);
                    // remove any UTF-8 byte order mark (BOM) from files
                    if (str_starts_with($contents, "\xEF\xBB\xBF")) {
                        $contents = substr($contents, 3);
                    }
                }
                // only fix paths if files aren't already in typo3temp (already processed)
                if ($type === 'css' && !str_starts_with($fileUrl, $this->targetDirectory)) {
                    $contents = $this->cssFixRelativeUrlPaths($contents, $fileUrl);
                }
                $concatenated .= LF . $contents;
            }
            // move @charset, @import and @namespace statements to top of new file
            if ($type === 'css') {
                $concatenated = $this->cssFixStatements($concatenated);
            }
            GeneralUtility::writeFile(Environment::getPublicPath() . '/' . $targetFile, $concatenated, true);
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
        $this->initialize();
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
     * @param string $cssFile Source filename, relative to requested page
     * @return string Compressed filename, relative to requested page
     */
    public function compressCssFile($cssFile)
    {
        $this->initialize();
        try {
            $resource = $this->resourceFactory->createPublicResource($cssFile);
        } catch (CanNotResolveSystemResourceException) {
            $resource = null;
        }
        if ($resource instanceof SystemResourceInterface) {
            $filename = $resource->getNameWithoutExtension();
            $contents = $resource->getContents();
            $hash = $resource->getHash();
            // @todo: We make an "URL" relative to public dir here for CSS processing.
            //        check whether this can be done differently
            $cssUrl = ltrim((string)$this->resourcePublisher->generateUri($resource, null, new UriGenerationOptions(uriPrefix: '', cacheBusting: false)), '/');
        } else {
            $filename = PathUtility::pathinfo($cssFile)['filename'];
            $filenameAbsolute = Environment::getPublicPath() . '/' . $this->getFilenameFromMainDir($cssFile);
            if (@file_exists($filenameAbsolute)) {
                $contents = file_get_contents($filenameAbsolute);
                $fileStatus = stat($filenameAbsolute);
                $hash = md5($filenameAbsolute . $fileStatus['mtime'] . $fileStatus['size']);
            } else {
                $hash = md5($filenameAbsolute);
                $contents = '';
            }
            // make sure it is again the full filename
            $cssUrl = PathUtility::getAbsoluteWebPath($filenameAbsolute, false);
        }

        $targetFile = $this->targetDirectory . $filename . '-' . $hash . '.css';
        if (!file_exists(Environment::getPublicPath() . '/' . $targetFile . ($this->createGzipped ? $this->gzipFileExtension : ''))) {
            if (!str_contains($cssUrl, $this->targetDirectory)) {
                $contents = $this->cssFixRelativeUrlPaths($contents, $cssUrl);
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
        $this->initialize();
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
     * @param string $jsFile Source filename, relative to requested page
     * @return string Filename of the compressed file, relative to requested page
     */
    public function compressJsFile($jsFile)
    {
        $this->initialize();
        try {
            $resource = $this->resourceFactory->createResource($jsFile);
        } catch (CanNotResolveSystemResourceException) {
            $resource = null;
        }
        if ($resource instanceof SystemResourceInterface) {
            $filename = $resource->getNameWithoutExtension();
            $fileContents = $resource->getContents();
            $hash = $resource->getHash();
        } else {
            $filename = PathUtility::pathinfo($jsFile)['filename'];
            $filenameAbsolute = Environment::getPublicPath() . '/' . $this->getFilenameFromMainDir($jsFile);
            if (@file_exists($filenameAbsolute)) {
                $fileContents = file_get_contents($filenameAbsolute);
                $fileStatus = stat($filenameAbsolute);
                $hash = md5($filenameAbsolute . $fileStatus['mtime'] . $fileStatus['size']);
            } else {
                $hash = md5($filenameAbsolute);
                $fileContents = '';
            }
        }
        $targetFile = $this->targetDirectory . $filename . '-' . $hash . '.js';
        // only create it, if it doesn't exist, yet
        if (!file_exists(Environment::getPublicPath() . '/' . $targetFile . ($this->createGzipped ? $this->gzipFileExtension : ''))
        ) {
            $this->writeFileAndCompressed($targetFile, $fileContents);
        }
        return $this->returnFileReference($targetFile);
    }

    /**
     * Finds the relative path to a file, relative to the public path.
     *
     * @param string $filename the name of the file
     * @return string the path to the file relative to the public path
     */
    protected function getFilenameFromMainDir($filename)
    {
        /*
         * The various paths may have those values (e.g. if TYPO3 is installed in a subdir)
         * - docRoot = /var/www/html/
         * - Environment::getPublicPath() = /var/www/html/sites/site1/
         *
         * The file names passed into this function may be either:
         * - relative to Environment::getPublicPath()
         * - relative to docRoot
         */
        $docRoot = GeneralUtility::getIndpEnv('TYPO3_DOCUMENT_ROOT');
        $fileNameWithoutSlash = ltrim($filename, '/');

        // If the $filename stems from a call to PathUtility::getAbsoluteWebPath() it has a leading slash,
        // hence isAbsolutePath() results in true, which is obviously wrong. Check file existence to be sure.
        // Calling is_file without @ for a path starting with '../' causes a PHP Warning when using open_basedir restriction
        if (PathUtility::isAbsolutePath($filename) && @is_file($filename)) {
            $absolutePath = $filename;
        } else {
            // if the file is an absolute reference within the docRoot
            $absolutePath = $docRoot . '/' . $fileNameWithoutSlash;
        }
        if (@is_file($absolutePath)) {
            return PathUtility::getAbsoluteWebPath($absolutePath, false);
        }
        // if the file exists in the root path, just return the $filename
        if (is_file(Environment::getPublicPath() . '/' . $fileNameWithoutSlash)) {
            return $fileNameWithoutSlash;
        }
        // build the file path relative to the public web path
        if (PathUtility::isExtensionPath($filename)) {
            // @todo: Until a full refactor of this class, we return an URL relative to document root
            //        to adhere the internal API
            return ltrim((string)PathUtility::getSystemResourceUri($filename, null, new UriGenerationOptions(uriPrefix: '', cacheBusting: false)), '/');
        }
        return $filename;
    }

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
     * Writes $contents into file $filename together with a gzipped version into $filename.gz (gzipFileExtension)
     *
     * @param string $filename Target filename
     * @param string $contents File contents
     */
    protected function writeFileAndCompressed($filename, $contents)
    {
        // write uncompressed file
        GeneralUtility::writeFile(Environment::getPublicPath() . '/' . $filename, $contents, true);
        if ($this->createGzipped) {
            // create compressed version
            GeneralUtility::writeFile(Environment::getPublicPath() . '/' . $filename . $this->gzipFileExtension, (string)gzencode($contents, $this->gzipCompressionLevel), true);
        }
    }

    /**
     * Decides whether a client can deal with gzipped content or not and returns the according file name,
     * based on HTTP_ACCEPT_ENCODING
     *
     * @param string $filename File name
     * @return string $filename suffixed with '.gz' or not - dependent on HTTP_ACCEPT_ENCODING
     */
    protected function returnFileReference($filename)
    {
        // if the client accepts gzip and we can create gzipped files, we give him compressed versions
        if ($this->createGzipped && str_contains(GeneralUtility::getIndpEnv('HTTP_ACCEPT_ENCODING'), 'gzip')) {
            $filename .= $this->gzipFileExtension;
        }
        return $filename;
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
            GeneralUtility::writeFile(Environment::getPublicPath() . '/' . $filename, $externalContent, true);
        }
        return $filename;
    }

    public function compressJavaScriptSource(string $javaScriptSourceCode): string
    {
        $this->initialize();
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
     * Whenever HTML5 is used, do not use the "text/javascript" type attribute.
     */
    protected function getJavaScriptFileType(): string
    {
        $docType = GeneralUtility::makeInstance(PageRenderer::class)->getDocType();
        return $docType === DocType::html5 ? '' : 'text/javascript';
    }

    protected function getPathFixer(): RelativeCssPathFixer
    {
        return GeneralUtility::makeInstance(RelativeCssPathFixer::class);
    }
}
