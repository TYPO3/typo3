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

/**
 * This file contains the default array definition that is
 * later populated as $GLOBALS['TYPO3_CONF_VARS']
 */
return [
    'GFX' => [
        // Configuration of the image processing features in TYPO3. 'IM' and 'GD' are short for ImageMagick and GD library respectively.
        'image_processing' => true,                        // Boolean: Enables image processing features. Disabling this means NO image processing with either GD or IM!
        'thumbnails' => true,                            // Boolean: Enables the use of thumbnails in the backend interface.
        'thumbnails_png' => 0,                            // Bits. Bit0: If set, thumbnails from non-jpegs will be 'png', otherwise 'gif' (0=gif/1=png). Bit1: Even JPG's will be converted to png or gif (2=gif/3=png)
        'gif_compress' => true,                            // Boolean: Enables the use of the \TYPO3\CMS\Core\Utility\GeneralUtility::gifCompress() workaround function for compressing giffiles made with GD or IM, which probably use only RLE or no compression at all.
        'imagefile_ext' => 'gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai,svg', // Commalist of file extensions perceived as images by TYPO3. List should be set to 'gif,png,jpeg,jpg' if IM is not available. Lowercase and no spaces between!
        'gdlib' => true,                                // Boolean: Enables the use of GD.
        'gdlib_png' => false,                            // Boolean: Enables the use of GD, with PNG only. This means that all items normally generated as gif-files will be png-files instead!
        'im' => true,                                    // Boolean: Enables the use of IM.
        'im_path' => '/usr/bin/',                        // Path to the IM tools 'convert', 'combine', 'identify'.
        'im_path_lzw' => '/usr/bin/',                    // Path to the IM tool 'convert' with LZW enabled! See 'gif_compress'. If your version 4.2.9 of ImageMagick is compiled with LZW you may leave this field blank AND disable the flag 'gif_compress'! Tip: You can call LZW 'convert' with a prefix like 'myver_convert' by setting this path with it, eg. '/usr/bin/myver_' instead of just '/usr/bin/'.
        'im_version_5' => 'im6',                        // String: Set this either to "im6" or "gm" (uses GraphicsMagick instead of ImageMagick). Setting this value will automatically configure some settings for use with the specified program version.
        'im_v5effects' => 0,                            // <p>Integer (-1, 0, 1)</p><dl><dt>0</dt><dd>disabled</dd><dt>-1</dt><dd>Do not sharpen images by default</dd><dt>1</dt><dd>All; blur and sharpening is allowed in ImageMagick.</dd></dl>
        'im_mask_temp_ext_gif' => 1,                    // Boolean: This should be set if ImageMagick is version 5+. This is used in \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer for masking images and the extension png is normally used because it's faster than gif for temporary files. But png seems not to work with some IM 5+ versions, so...
        'im_noScaleUp' => false,                        // Boolean: If set, images are not being scaled up if told so (in \TYPO3\CMS\Core\Imaging\GraphicalFunctions)
        'im_noFramePrepended' => false,                    // Boolean: If set, the [x] frame indicator is NOT prepended to filenames in stdgraphic. Some IM5+ version didn't work at all with the typical [0]-prefix, which allow multipage pdf's and animated gif's to be scaled only for the first frame/page and that seriously cuts down rendering time. Set this flag only if your ImageMagick version cannot find the files. Notice that changing this flag causes temporary filenames to change, thus the server will begin scaling images again which were previously cached.
        'im_stripProfileCommand' => '+profile \'*\'',    // String: Specify the command to strip the profile information, which can reduce thumbnail size up to 60KB. Command can differ in IM/GM, IM also know the -strip command. See <a href="http://www.imagemagick.org/Usage/thumbnails/#profiles" target="_blank">imagemagick.org</a> for details
        'im_useStripProfileByDefault' => true,            // Boolean: If set, the im_stripProfileCommand is used with all IM Image operations by default. See tsRef for setting this parameter explocit for IMAGE generation.
        'jpg_quality' => 70,                            // Integer: Default JPEG generation quality
        'png_truecolor' => true,                        // Boolean: When creating png images, always use the full colorpalette, if disabled could reduce file sizes for scaled images, but the image quality will be let down.
        'colorspace' => 'RGB',                            // String: Specify the colorspace to use. Some ImageMagick versions (like 6.7.0 and above) use the sRGB colorspace, so all images are darker then the original. <br />Possible Values: CMY, CMYK, Gray, HCL, HSB, HSL, HWB, Lab, LCH, LMS, Log, Luv, OHTA, Rec601Luma, Rec601YCbCr, Rec709Luma, Rec709YCbCr, RGB, sRGB, Transparent, XYZ, YCbCr, YCC, YIQ, YCbCr, YUV
    ],
    'SYS' => [
        // System related concerning both frontend and backend.
        'lang' => [
            'format' => [
                'priority' => 'xlf,xml'
            ],
            'parser' => [
                'xml' => \TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser::class,
                'xlf' => \TYPO3\CMS\Core\Localization\Parser\XliffParser::class
            ]
        ],
        'fileCreateMask' => '0664',                        // File mode mask for Unix file systems (when files are uploaded/created).
        'folderCreateMask' => '2775',                    // As above, but for folders.
        'createGroup' => '',                            // Group for newly created files and folders (Unix only). Group ownership can be changed on Unix file systems (see above). Set this if you want to change the group ownership of created files/folders to a specific group. This makes sense in all cases where the webserver is running with a different user/group as you do. Create a new group on your system and add you and the webserver user to the group. Now you can safely set the last bit in fileCreateMask/folderCreateMask to 0 (e.g. 770). Important: The user who is running your webserver needs to be a member of the group you specify here! Otherwise you might get some error messages.
        'sitename' => 'TYPO3',                    // Name of the base-site. This title shows up in the root of the tree structure if you're an 'admin' backend user.
        'encryptionKey' => '',                    // This is a "salt" used for various kinds of encryption, CRC checksums and validations. You can enter any rubbish string here but try to keep it secret. You should notice that a change to this value might invalidate temporary information, URLs etc. At least, clear all cache if you change this so any such information can be rebuild with the new key.
        'cookieDomain' => '',                    // Restricts the domain name for FE and BE session cookies. When setting the value to ".domain.com" (replace domain.com with your domain!), login sessions will be shared across subdomains. Alternatively, if you have more than one domain with sub-domains, you can set the value to a regular expression to match against the domain of the HTTP request. The result of the match is used as the domain for the cookie. eg. /\.(example1|example2)\.com$/ or /\.(example1\.com)|(example2\.net)$/. Separate domains for FE and BE can be set using <a href="#FE-cookieDomain">$TYPO3_CONF_VARS['FE']['cookieDomain']</a> and <a href="#BE-cookieDomain">$TYPO3_CONF_VARS['BE']['cookieDomain']</a> respectively.
        'cookieSecure' => 0,                    // <p>Integer (0, 1, 2): Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client.</p><dl><dt>0</dt><dd>always send cookie</dd><dt>1 (force HTTPS)</dt><dd>the cookie will only be set if a secure (HTTPS) connection exists - use this in combination with lockSSL since otherwise the application will fail and throw an exception</dd><dt>2</dt><dd>the cookie will be set in each case, but uses the secure flag if a secure (HTTPS) connection exists.</dd></dl>
        'cookieHttpOnly' => true,                // Boolean: When enabled the cookie will be made accessible only through the HTTP protocol. This means that the cookie won't be accessible by scripting languages, such as JavaScript. This setting can effectively help to reduce identity theft through XSS attacks (although it is not supported by all browsers).
        'doNotCheckReferer' => false,            // Boolean: If set, it's NOT checked numerous places that the refering host is the same as the current. This is an option you should set if you have problems with proxies not passing the HTTP_REFERER variable.
        'recursiveDomainSearch' => false,        // Boolean: If set, the search for domain records will be done recursively by stripping parts of the hostname off until a matching domain record is found.
        'trustedHostsPattern' => 'SERVER_NAME',    // String: Regular expression pattern that matches all allowed hostnames (including their ports) of this TYPO3 installation, or the string "SERVER_NAME" (default). The default value <code>SERVER_NAME</code> checks if the HTTP Host header equals the SERVER_NAME and SERVER_PORT. This is secure in correctly configured hosting environments and does not need further configuration. If you cannot change your hosting environment, you can enter a regular expression here. Examples: <code>.*\.domain\.com</code> matches all hosts that end with <code>.domain.com</code> with all corresponding subdomains. <code>(.*\.domain|.*\.otherdomain)\.com</code> matches all hostnames with subdomains from <code>.domain.com</code> and <code>.otherdomain.com</code>. Be aware that HTTP Host header may also contain a port. If your installation runs on a specific port, you need to explicitly allow this in your pattern, e.g. <code>www\.domain\.com:88</code> allows only <code>www.domain.com:88</code>, <strong>not</strong> <code>www.domain.com</code>. To disable this check completely (not recommended because it is <strong>insecure</strong>) you can use ".*" as pattern.
        'devIPmask' => '127.0.0.1,::1',            // Defines a list of IP addresses which will allow development-output to display. The debug() function will use this as a filter. See the function \TYPO3\CMS\Core\Utility\GeneralUtility::cmpIP() for details on syntax. Setting this to blank value will deny all. Setting to "*" will allow all.
        'sqlDebug' => 0,                        // <p>Integer (0, 1, 2). Allows displaying executed SQL queries in the browser (for debugging purposes and development)</p><dl><dt>0</dt><dd>no SQL shown (default)</dd><dt>1</dt><dd>show only failed queries</dd><dt>2</dt><dd>show all queries</dd></dl>
        'enable_DLOG' => false,                    // Boolean: Whether the developer log is enabled. See constant "TYPO3_DLOG"
        'ddmmyy' => 'd-m-y',                    // Format of Date-Month-Year - see PHP-function <a href="http://php.net/date" target="_blank">date()</a>
        'hhmm' => 'H:i',                        // Format of Hours-Minutes - see PHP-function <a href="http://php.net/date" target="_blank">date()</a>
        'USdateFormat' => false,                // Boolean: If TRUE, dates entered in the TCEforms of the backend will be formatted mm-dd-yyyy
        'loginCopyrightWarrantyProvider' => '',    // String: If you provide warranty for TYPO3 to your customers insert you (company) name here. It will appear in the login-dialog as the warranty provider. (You must also set URL below).
        'loginCopyrightWarrantyURL' => '',        // String: Add the URL where you explain the extend of the warranty you provide. This URL is displayed in the login dialog as the place where people can learn more about the conditions of your warranty. Must be set (more than 10 chars) in addition with the 'loginCopyrightWarrantyProvider' message.
        'curlUse' => false,                        // Boolean: If set, try to use cURL to fetch external URLs
        'curlProxyNTLM' => false,                    // Boolean: Proxy NTLM authentication support.
        /**
         * @deprecated since 4.6 - will be removed in 6.2.
         */
        'curlProxyServer' => '',                // String: Proxyserver as http://proxy:port/. Deprecated since 4.6 - will be removed in 6.2. See below for http options.
        /**
         * @deprecated since 4.6 - will be removed in 6.2.
         */
        'curlProxyTunnel' => false,                // Boolean: If set, use a tunneled connection through the proxy (useful for websense etc.). Deprecated since 4.6 - will be removed in 6.2. See below for http options.
        /**
         * @deprecated since 4.6 - will be removed in 6.2.
         */
        'curlProxyUserPass' => '',                // String: Proxyserver authentication user:pass. Deprecated since 4.6 - will be removed in 6.2. See below for http options.
        /**
         * @deprecated since 4.6 - will be removed in 6.2.
         */
        'curlTimeout' => 0,                        // Integer: Timeout value for cURL requests in seconds. 0 means to wait indefinitely. Deprecated since 4.6 - will be removed in 6.2. See below for http options.
        'textfile_ext' => 'txt,ts,typoscript,html,htm,css,tmpl,js,sql,xml,csv,xlf',    // Text file extensions. Those that can be edited. Executable PHP files may not be editable in webspace if disallowed!
        'mediafile_ext' => 'gif,jpg,jpeg,bmp,png,pdf,svg,ai,mp3,wav,mp4,webm,youtube,vimeo',    // Commalist of file extensions perceived as media files by TYPO3. Lowercase and no spaces between!
        'binPath' => '',                        // String: List of absolute paths where external programs should be searched for. Eg. <code>/usr/local/webbin/,/home/xyz/bin/</code>. (ImageMagick path have to be configured separately)
        'binSetup' => '',                        // String (textarea): List of programs (separated by newline or comma). By default programs will be searched in default paths and the special paths defined by 'binPath'. When PHP has openbasedir enabled the programs can not be found and have to be configured here. Example: <code>perl=/usr/bin/perl,unzip=/usr/local/bin/unzip</code>
        't3lib_cs_convMethod' => '',            // String (values: "iconv", "recode", "mbstring", default is homemade PHP-code). Defines which of these PHP-features to use for various charset conversion functions in \TYPO3\CMS\Core\Charset\CharsetConverter. Will speed up charset conversion radically.
        't3lib_cs_utils' => '',                    // String (values: "iconv", "mbstring", default is homemade PHP-code). Defines which of these PHP-features to use for various charset processing functions in \TYPO3\CMS\Core\Charset\CharsetConverter. Will speed up charset functions radically.
        'no_pconnect' => true,                    // Boolean: If TRUE, "connect" is used to connect to the database. If FALSE, a persistent connection using "pconnect" will be established!
        'dbClientCompress' => false,            // Boolean: if TRUE, data exchange between TYPO3 and database server will be compressed. This may improve performance if (1) database serever is on the different server and (2) network connection speed to database server is 100mbps or less. CPU usage will be higher if this option is used but database operations will be executed faster due to much less (up to 3 times) database network traffic. This option has no effect if MySQL server is localhost.
        'setDBinit' => '',                        // String (textarea): These commands are executed after the database connection was established. Hint: The previous default "SET NAMES utf8;" is not required any more and will be removed automatically if set!
        'setMemoryLimit' => 0,                    // Integer: memory_limit in MB: If more than 16, TYPO3 will try to use ini_set() to set the memory limit of PHP to the value. This works only if the function ini_set() is not disabled by your sysadmin.
        'phpTimeZone' => '',                    // String: timezone to force for all date() and mktime() functions. A list of supported values can be found at <a href="http://php.net/manual/en/timezones.php" target="_blank">php.net</a>. If this is not set, a valid fallback will be searched for by PHP (php.ini's <a href="http://www.php.net/manual/en/datetime.configuration.php#ini.date.timezone" target="_blank">date.timezone</a> setting, server defaults, etc); and if no fallback is found, the value of "UTC" is used instead.
        'systemLog' => '',                        // <p>String: semi-colon separated list. Defines one or more logging methods. Possible methods:</p><dl><dt>file,&lt;abs-path-to-file&gt;[,&lt;level&gt;]</dt><dd>logs to a file</dd><dt>mail,&lt;to&gt;[/&lt;from&gt;][,&lt;level&gt;]</dt><dd>sends the log entries via mail</dd><dt>syslog,&lt;facility&gt;,[,&lt;level&gt;]</dt><dd>uses the operating system's log. Facility may be one of LOCAL0..LOCAL7, USER (on Windows USER is the only valid type).</dd><dt>error_log[,,&lt;level&gt;]</dt><dd>uses the PHP error log</dd></dl><p>The &lt;level&gt; is the individual logging level (see <a href="#SYS-systemLogLevel">[SYS][systemLogLevel]</a>).</p>
        'systemLogLevel' => 0,                    // <p>Integer (0, 1, 2, 3, 4): Only messages with same or higher severity are logged.</p><ul><li>0: info</li><li>1: notice</li><li>2: warning</li><li>3: error</li><li>4: fatal error</li></ul>
        'enableDeprecationLog' => '',                // If set, this configuration enables the logging of deprecated methods and functions. The following options are allowed: <dl><dt>String: &quot;file&quot; (or integer &quot;1&quot;)</dt><dd>The log file will be written to typo3conf/deprecation_[hash-value].log</dd><dt>String: &quot;devlog&quot;</dt><dd>The log will be written to the development log</dd><dt>String: &quot;console&quot;<dt><dd>The log will be displayed in the Backend's Debug Console.</dd></dl>Logging options &quot;file&quot;, &quot;devlog&quot; and &quot;console&quot; can be combined by comma-separating them.
        'maxFileNameLength' => 60,                // Integer: This is the maximum file name length. The value will be taken into account by basic file operations like renaming or creation of files and folders.
        'UTF8filesystem' => false,                // Boolean: If TRUE then TYPO3 uses utf-8 to store file names. This allows for accented Latin letters as well as any other non-latin characters like Cyrillic and Chinese.
        'systemLocale' => '',                    // String: locale used for certain system related functions, e.g. escaping shell commands. If problems with filenames containing special characters occur, the value of this option is probably wrong. See <a href="http://php.net/manual/en/function.setlocale.php" target="_blank">setlocale()</a>.
        'lockingMode' => 'simple',                // String: *deprecated* Define which locking mode is used to control requests to pages being generated. Can be one of either "disable" (no locking), "simple" (checks for file existence), "flock" (using PHPs <a href="http://php.net/flock" target="_blank">flock()</a> function), "semaphore" (using PHPs <a href="http://php.net/sem-acquire" target="_blank">sem_acquire()</a> function). Default is "simple". (This option is deprecated since TYPO3 CMS 7 and will be removed in TYPO3 CMS 8. The option is only used by extensions using the old Locker.)
        'reverseProxyIP' => '',                    // String: list of IP addresses. If TYPO3 is behind one or more (intransparent) reverse proxies the IP addresses must be added here.
        'reverseProxyHeaderMultiValue' => 'none',    // String: "none","first","last": defines which values of a proxy header (eg HTTP_X_FORWARDED_FOR) to use, if more than one is found. "none" discards the value, "first" and "last" use the first/last of the values in the list.
        'reverseProxyPrefix' => '',                // String: optional prefix to be added to the internal URL (SCRIPT_NAME and REQUEST_URI).
        'reverseProxySSL' => '',                // String: '*' or list of IP addresses of proxies that use SSL (https) for the connection to the client, but an unencrypted connection (http) to the server. If '*' all proxies defined in <a href="#SYS-reverseProxyIP">[SYS][reverseProxyIP]</a> use SSL.
        'reverseProxyPrefixSSL' => '',            // String: prefix to be added to the internal URL (SCRIPT_NAME and REQUEST_URI) when accessing the server via an SSL proxy. This setting overrides <a href="#SYS-reverseProxyPrefix">[SYS][reverseProxyPrefix]</a>.
        'caching' => [
            'cacheConfigurations' => [
                // The cache_core cache is is for core php code only and must
                // not be abused by third party extensions.
                'cache_core' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
                    'options' => [
                        'defaultLifetime' => 0,
                    ],
                    'groups' => ['system']
                ],
                'cache_hash' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [],
                    'groups' => ['pages', 'all']
                ],
                'cache_pages' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [
                        'compression' => true
                    ],
                    'groups' => ['pages', 'all']
                ],
                'cache_pagesection' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [
                        'compression' => true,
                        'defaultLifetime' => 2592000, // 30 days; set this to a lower value in case your cache gets too big
                    ],
                    'groups' => ['pages', 'all']
                ],
                'cache_phpcode' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\FileBackend::class,
                    'options' => [
                        'defaultLifetime' => 0,
                    ],
                    'groups' => ['system']
                ],
                'cache_runtime' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class,
                    'options' => [],
                    'groups' => []
                ],
                'cache_rootline' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [
                        'defaultLifetime' => 2592000, // 30 days; set this to a lower value in case your cache gets too big
                    ],
                    'groups' => ['pages', 'all']
                ],
                'cache_imagesizes' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [
                        'defaultLifetime' => 0,
                    ],
                    'groups' => ['lowlevel'],
                ],
                'l10n' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
                    'options' => [
                        'defaultLifetime' => 0,
                    ],
                    'groups' => ['system']
                ],
                'fluid_template' => [
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\FileBackend::class,
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend::class,
                    'groups' => ['system'],
                ],
                'extbase_object' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [
                        'defaultLifetime' => 0,
                    ],
                    'groups' => ['system']
                ],
                'extbase_reflection' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [
                        'defaultLifetime' => 0,
                    ],
                    'groups' => ['system']
                ],
                'extbase_typo3dbbackend_tablecolumns' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\NullBackend::class,
                    'groups' => ['system'],
                ],
                'extbase_typo3dbbackend_queries' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'groups' => ['system'],
                ],
                'extbase_datamapfactory_datamap' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'groups' => ['system'],
                ],
            ],
        ],
        'defaultCategorizedTables' => 'pages,tt_content,sys_file_metadata', // List of comma separated tables that are categorizable by default.
        'displayErrors' => -1,        // <p>Integer (-1, 0, 1). Configures whether PHP errors or Exceptions should be displayed.</p><dl><dt>0</dt><dd>Do not display any PHP error message. Sets PHP "display_errors" setting to 0. Overrides the value of [SYS][exceptionalErrors] and sets it to 0 (= no errors are turned into exceptions). The configured [SYS][productionExceptionHandler] is used as exception handler.</dd><dt>1</dt><dd>Display error messages with the registered [SYS][errorHandler]. Sets PHP "display_errors" setting to 1. The configured [SYS][debugExceptionHandler] is used as exception handler.</dd><dt>-1</dt><dd>TYPO3 CMS does not touch the PHP "display_errors" setting. If [SYS][devIPmask] matches the user's IP address, the configured [SYS][debugExceptionHandler] is used instead of the [SYS][productionExceptionHandler] to handle exceptions.</dd></dl>
        'productionExceptionHandler' => \TYPO3\CMS\Core\Error\ProductionExceptionHandler::class,        // String: Classname to handle exceptions that might happen in the TYPO3-code. Leave empty to disable exception handling. Default: "TYPO3\\CMS\\Core\\Error\\ProductionExceptionHandler". This exception handler displays a nice error message when something went wrong. The error message is logged to the configured logs. Note: The configured "productionExceptionHandler" is used if [SYS][displayErrors] is set to "0" or is set to "-1" and [SYS][devIPmask] doesn't match the user's IP.
        'debugExceptionHandler' => \TYPO3\CMS\Core\Error\DebugExceptionHandler::class,        // String: Classname to handle exceptions that might happen in the TYPO3-code. Leave empty to disable exception handling. Default: "TYPO3\\CMS\\Core\\Error\\DebugExceptionHandler". This exception handler displays the complete stack trace of any encountered exception. The error message and the stack trace is logged to the configured logs. Note: The configured "debugExceptionHandler" is used if [SYS][displayErrors] is set to "1" or is set to "-1" or "2" and the [SYS][devIPmask] matches the user's IP.
        'errorHandler' => \TYPO3\CMS\Core\Error\ErrorHandler::class,        // String: Classname to handle PHP errors. E.g.: TYPO3\CMS\Core\Error\ErrorHandler. This class displays and logs all errors that are registered as [SYS][errorHandlerErrors]. Leave empty to disable error handling. Errors can be logged to syslog (see: [SYS][systemLog]), to the installed developer log and to the "syslog" table. If an error is registered in [SYS][exceptionalErrors] it will be turned into an exception to be handled by the configured exceptionHandler.
        'errorHandlerErrors' => E_ALL & ~(E_STRICT | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR),        // Integer: The E_* constant that will be handled by the [SYS][errorHandler]. Not all PHP error types can be handled! Default is 30466 = <code>E_ALL & ~(E_STRICT | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR)</code> (see <a href="http://php.net/manual/en/errorfunc.constants.php" target="_blank">PHP documentation</a>).
        'exceptionalErrors' => E_ALL & ~(E_STRICT | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR | E_DEPRECATED | E_WARNING | E_USER_ERROR | E_USER_NOTICE | E_USER_WARNING),        // Integer: The E_* constant that will be converted into an exception by the default [SYS][errorHandler]. Default is 20480 = <code>E_ALL & ~(E_STRICT | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR | E_DEPRECATED | E_WARNING | E_USER_ERROR | E_USER_NOTICE | E_USER_WARNING)</code> (see <a href="http://php.net/manual/en/errorfunc.constants.php" target="_blank">PHP documentation</a>).
        'enable_errorDLOG' => 0,    // Boolean: If set, errors are written to the developer log (requires an installed *devlog* extension).
        'enable_exceptionDLOG' => 0, // Boolean: If set, exceptions are written to the developer log (requires an installed *devlog* extension).
        'syslogErrorReporting' => E_ALL & ~(E_STRICT | E_NOTICE),        // Integer: Configures which PHP errors should be logged to the configured syslogs (see: [SYS][systemLog]). If set to "0" no PHP errors are logged to the syslog. Default is 30711 = <code>E_ALL & ~(E_STRICT | E_NOTICE)</code> (see <a href="http://php.net/manual/en/errorfunc.constants.php" target="_blank">PHP documentation</a>).
        'belogErrorReporting' => E_ALL & ~(E_STRICT | E_NOTICE),        // Integer: Configures which PHP errors should be logged to the "syslog" table (extension: belog). If set to "0" no PHP errors are logged to the sys_log table. Default is 30711 = <code>E_ALL & ~(E_STRICT | E_NOTICE)</code> (see <a href="http://php.net/manual/en/errorfunc.constants.php" target="_blank">PHP documentation</a>).
        'locallangXMLOverride' => [],        // For extension/overriding of the arrays in 'locallang' files in frontend and backend. See 'Inside TYPO3' for more information.
        'generateApacheHtaccess' => 1,        // Boolean: TYPO3 can create <em>.htaccess</em> files which are used by Apache Webserver. They are useful for access protection or performance improvements. Currently <em>.htaccess</em> files in the following directories are created, if they do not exist: <ul><li>typo3temp/compressor/</li></ul>You want to disable this feature, if you are not running Apache or want to use own rulesets.
        'Objects' => [],
        'fal' => [
            'registeredDrivers' => [
                'Local' => [
                    'class' => \TYPO3\CMS\Core\Resource\Driver\LocalDriver::class,
                    'shortName' => 'Local',
                    'flexFormDS' => 'FILE:EXT:core/Configuration/Resource/Driver/LocalDriverFlexForm.xml',
                    'label' => 'Local filesystem'
                ]
            ],
            'defaultFilterCallbacks' => [
                [
                    \TYPO3\CMS\Core\Resource\Filter\FileNameFilter::class,
                    'filterHiddenFilesAndFolders'
                ]
            ],
            'processingTaskTypes' => [
                'Image.Preview' => \TYPO3\CMS\Core\Resource\Processing\ImagePreviewTask::class,
                'Image.CropScaleMask' => \TYPO3\CMS\Core\Resource\Processing\ImageCropScaleMaskTask::class
            ],
            'registeredCollections' => [
                'static' => \TYPO3\CMS\Core\Resource\Collection\StaticFileCollection::class,
                'folder' => \TYPO3\CMS\Core\Resource\Collection\FolderBasedFileCollection::class,
                'category' => \TYPO3\CMS\Core\Resource\Collection\CategoryBasedFileCollection::class,
            ],
            'onlineMediaHelpers' => [
                'youtube' => \TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\YouTubeHelper::class,
                'vimeo' => \TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\VimeoHelper::class,
            ],
        ],
        'IconFactory' => [
            'recordStatusMapping' => [
                'hidden' => 'overlay-hidden',
                'fe_group' => 'overlay-restricted',
                'starttime' => 'overlay-scheduled',
                'endtime' => 'overlay-scheduled',
                'futureendtime' => 'overlay-scheduled',
                'readonly' => 'overlay-readonly',
                'deleted' => 'overlay-deleted',
                'missing' => 'overlay-missing',
                'translated' => 'overlay-translated',
                'protectedSection' => 'overlay-includes-subpages'
            ],
            'overlayPriorities' => [
                'hidden',
                'starttime',
                'endtime',
                'futureendtime',
                'protectedSection',
                'fe_group'
            ]
        ],
        'FileInfo' => [
            // Static mapping for file extensions to mime types.
            // In special cases the mime type is not detected correctly.
            // Use this array only if the automatic detection does not work correct!
            'fileExtensionToMimeType' => [
                'svg' => 'image/svg+xml',
                'youtube' => 'video/youtube',
                'vimeo' => 'video/vimeo',
            ]
        ],
        'livesearch' => [],    // Array: keywords used for commands to search for specific tables
        'isInitialInstallationInProgress' => false,        // Boolean: If TRUE, the installation is 'in progress'. This value is handled within the install tool step installer internally.
        'isInitialDatabaseImportDone' => true,        // Boolean: If TRUE, the database import is finished. This value is handled within the install tool step installer internally.
        'clearCacheSystem' => false,        // Boolean: If set, the toolbar menu entry for clearing system caches (core cache, class cache, etc.) is visible for admin users.
        'formEngine' => [
            'nodeRegistry' => [], // Array: Registry to add or overwrite FormEngine nodes. Main key is a timestamp of the date when an entry is added, sub keys type, priority and class are required. Class must implement TYPO3\CMS\Backend\Form\NodeInterface.
            'nodeResolver' => [], // Array: Additional node resolver. Main key is a timestamp of the date when an entry is added, sub keys type, priority and class are required. Class must implement TYPO3\CMS\Backend\Form\NodeResolverInterface.
            'formDataGroup' => [ // Array: Registry of form data providers for form data groups
                'tcaDatabaseRecord' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\ReturnUrl::class => [],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\ReturnUrl::class,
                        ]
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageRootline::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageRootline::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\ParentPageTca::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\ParentPageTca::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUniqueUidNewRow::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDateTimeFields::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUniqueUidNewRow::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDateTimeFields::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageLanguageOverlayRows::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseLanguageRows::class => [
                        'depends' => [
                            // Language stuff depends on user ts, but it *may* also depend on new row defaults
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageLanguageOverlayRows::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseLanguageRows::class,
                            // As the ctrl.type can hold a nested key we need to resolve all relations
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsOverrides::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsOverrides::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessPlaceholders::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessShowitem::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessPlaceholders::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessPlaceholders::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessShowitem::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexFetch::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexFetch::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageRootline::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class,
                            // GeneralUtility::getFlexFormDS() needs unchanged databaseRow values as string
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexFetch::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInputPlaceholders::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineIsOnSymmetricSide::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInputPlaceholders::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRecordTitle::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineIsOnSymmetricSide::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\EvaluateDisplayConditions::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRecordTitle::class,
                        ],
                    ],
                ],
                'flexFormSegment' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class => [],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInputPlaceholders::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ]
                    ]
                ],
                'tcaInputPlaceholderRecord' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class => [],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class,
                        ],
                    ],
                ],
                'inlineParentRecord' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class => [],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                        ]
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexFetch::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class,
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexFetch::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class,
                        ],
                    ],
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class => [
                        'depends' => [
                            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class,
                        ],
                    ],
                ],
            ],
        ],
    ],
    'EXT' => [ // Options related to the Extension Management
        'allowGlobalInstall' => false,        // Boolean: If set, global extensions in typo3/ext/ are allowed to be installed, updated and deleted etc.
        'allowLocalInstall' => true,        // Boolean: If set, local extensions in typo3conf/ext/ are allowed to be installed, updated and deleted etc.
        'allowSystemInstall' => false,        // Boolean: If set, you can install extensions in the sysext/ dir.
        'excludeForPackaging' => '(?:\\..*(?!htaccess)|.*~|.*\\.swp|.*\\.bak|\\.sass-cache|node_modules|bower_components)',        // String: List of directories and files which will not be packaged into extensions nor taken into account otherwise by the Extension Manager. Perl regular expression syntax!
        'extConf' => [
            'saltedpasswords' => serialize([
                'BE.' => [
                    'saltedPWHashingMethod' => \TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt::class,
                    'forceSalted' => 0,
                    'onlyAuthService' => 0,
                    'updatePasswd' => 1,
                ],
                'FE.' => [
                    'enabled' => 0,
                    'saltedPWHashingMethod' => \TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt::class,
                    'forceSalted' => 0,
                    'onlyAuthService' => 0,
                    'updatePasswd' => 1,
                ],
            ]),
        ],
        'runtimeActivatedPackages' => [],
    ],
    'BE' => [
        // Backend Configuration.
        'lang' => [
            'debug' => false
        ],
        'unzip_path' => '',                                // Path to "unzip". Only specify the path here, do not include the program name, it is expected to be called "unzip".
        'fileadminDir' => 'fileadmin/',                    // Path to the fileadmin dir. This is relative to PATH_site, DefaultStorage will be created with that configuration, do not access manually but ResourceFactory::getDefaultStorage()
        'RTE_imageStorageDir' => 'uploads/',            // Default storage directory for Rich Text Editor files
        'lockRootPath' => '',                            // This path is used to evaluate if paths outside of PATH_site should be allowed. Ending slash required!
        'userHomePath' => '',                            // Combined folder identifier of the directory where TYPO3 backend-users have their home-dirs. A combined folder identifier looks like this: [storageUid]:[folderIdentifier]. Eg. '2:users/'. A home for backend user 2 would be: '2:users/2/'. Ending slash required!
        'groupHomePath' => '',                            // Combined folder identifier of the directory where TYPO3 backend-groups have their home-dirs. A combined folder identifier looks like this: [storageUid]:[folderIdentifier]. Eg. '2:groups/'. A home for backend group 1 would be: '2:groups/1/'. Ending slash required!
        'userUploadDir' => '',                            // Suffix to the user home dir which is what gets mounted in TYPO3. Eg. if the user dir is "../123_user/" and this value is "/upload" then "../123_user/upload" gets mounted.
        'warning_email_addr' => '',                        // Email address that will receive notification whenever an attempt to login to the Install Tool is made and that will also receive warnings whenever more than 3 failed backend login attempts (regardless of user) are detected within an hour.
        'warning_mode' => '',                            // Bit 1: If set, warning_email_addr will be notified every time a backend user logs in. Bit 2: If set, warning_email_addr will be notified every time an ADMIN backend user logs in. Other bits are reserved for future options.
        'lockIP' => 4,                                    // Integer (0-4). Session IP locking for backend users. See <a href="#FE-lockIP">[FE][lockIP]</a> for details. Default is 4 (which is locking the FULL IP address to session).
        'sessionTimeout' => 3600,                        // Integer: seconds. Session time out for backend users. The value must be at least 180 to avoid side effects. Default is 3600 seconds = 1 hour.
        'IPmaskList' => '',                                // String: Lets you define a list of IP-numbers (with *-wildcards) that are the ONLY ones allowed access to ANY backend activity. On error an error header is sent and the script exits. Works like IP masking for users configurable through TSconfig. See syntax for that (or look up syntax for the function \TYPO3\CMS\Core\Utility\GeneralUtility::cmpIP())
        'lockBeUserToDBmounts' => true,                    // Boolean: If set, the backend user is allowed to work only within his page-mount. It's advisable to leave this on because it makes security easy to manage.
        'lockSSL' => 0,                                    // <p>Integer (0, 1, 2). If &gt;0, If set (1,2), the backend can only be operated from an SSL-encrypted connection (https)</p><dl><dt>0</dt><dd>no locking (default)</dd><dt>1</dt><dd>only allow access via SSL</dd><dt>2</dt><dd>redirect user trying to access non-https admin-urls to SSL URLs instead</dd></dl>
        'lockSSLPort' => 0,                                // Integer: Use a non-standard HTTPS port for lockSSL. Set this value if you use lockSSL and the HTTPS port of your webserver is not 443.
        'enabledBeUserIPLock' => true,                    // Boolean: If set, the User/Group TSconfig option 'option.lockToIP' is enabled.
        'lockHashKeyWords' => 'useragent',                // Keyword list (Strings comma separated). Currently only "useragent"; If set, then the BE user session is locked to the value of HTTP_USER_AGENT. This lowers the risk of session hi-jacking. However in some cases (like during development) you might need to switch the user agent while keeping the session. In this case you can disable that feature (e.g. with a blank string).
        'cookieDomain' => '',                            // Same as <a href="#SYS-cookieDomain">$TYPO3_CONF_VARS['SYS']['cookieDomain']</a> but only for BE cookies. If empty, $TYPO3_CONF_VARS['SYS']['cookieDomain'] value will be used.
        'cookieName' => 'be_typo_user',                    // String: Set the name for the cookie used for the back-end user session
        'loginSecurityLevel' => '',                        // String: Keywords that determines the security level of login to the backend. "normal" means the password from the login form is sent in clear-text, "rsa" uses RSA password encryption (only if the rsaauth extension is installed).
        'showRefreshLoginPopup' => false,                // Boolean: If set, the Ajax relogin will show a real popup window for relogin after the count down. Some auth services need this as they add custom validation to the login form. If it's not set, the Ajax relogin will show an inline relogin window.
        'adminOnly' => 0,                                // <p>Integer (-1, 0, 1, 2)</p><dl><dt>-1</dt><dd>total shutdown for maintenance purposes</dd><dt>0</dt><dd>normal operation, everyone can login (default)</dd><dt>1</dt><dd>only admins can login</dd><dt>2</dt><dd>only admins and regular CLI users can login</dd></dl>
        'disable_exec_function' => false,                // Boolean: Don't use exec() function (except for ImageMagick which is disabled by <a href="#GFX-im">[GFX][im]</a>=0). If set, all fileoperations are done by the default PHP-functions. This is necessary under Windows! On Unix the system commands by exec() can be used, unless this is disabled.
        'compressionLevel' => 0,                        // Determines output compression of BE output. Makes output smaller but slows down the page generation depending on the compression level. Requires a) zlib in your PHP installation and b) special rewrite rules for .css.gzip and .js.gzip (please see _.htacces for an example). Range 1-9, where 1 is least compression and 9 is greatest compression. 'true' as value will set the compression based on the PHP default settings (usually 5). Suggested and most optimal value is 5.
        'installToolPassword' => '',                    // String: This is the md5-hashed, salted password for the Install Tool. Set this to '' and access will be totally denied. You may consider to externally protect the typo3/sysext/install/ folder, eg. with a .htaccess file.
        'pageTree' => [
            'preloadLimit' => 50
        ],
        'defaultUserTSconfig' => 'options.enableBookmarks=1
			options.file_list.enableDisplayBigControlPanel=selectable
			options.file_list.enableDisplayThumbnails=selectable
			options.file_list.enableClipBoard=selectable
			options.pageTree {
				doktypesToShowInNewPageDragArea = 1,6,4,7,3,254,255,199
			}

			options.contextMenu.options.leftIcons = 1
			options.contextMenu {
				table {
					virtual_root {
						disableItems =

						items {
							100 = ITEM
							100 {
								name = history
								label = LLL:EXT:lang/locallang_misc.xlf:CM_history
								iconName = actions-document-history-open
								displayCondition = canShowHistory != 0
								callbackAction = openHistoryPopUp
							}
						}
					}

					pages_root {
						disableItems =

						items {
							100 = ITEM
							100 {
								name = view
								label = LLL:EXT:lang/locallang_core.xlf:cm.view
								iconName = actions-document-view
								displayCondition = canBeViewed != 0
								callbackAction = viewPage
							}

							200 = ITEM
							200 {
								name = new
								label = LLL:EXT:lang/locallang_core.xlf:cm.new
								iconName = actions-page-new
								displayCondition = canCreateNewPages != 0
								callbackAction = newPageWizard
							}

							300 = DIVIDER

							400 = ITEM
							400 {
								name = history
								label = LLL:EXT:lang/locallang_misc.xlf:CM_history
								iconName = actions-document-history-open
								displayCondition = canShowHistory != 0
								callbackAction = openHistoryPopUp
							}
						}
					}

					pages {
						disableItems =

						items {
							100 = ITEM
							100 {
								name = view
								label = LLL:EXT:lang/locallang_core.xlf:cm.view
								iconName = actions-document-view
								displayCondition = canBeViewed != 0
								callbackAction = viewPage
							}

							200 = DIVIDER

							300 = ITEM
							300 {
								name = disable
								label = LLL:EXT:lang/locallang_common.xlf:disable
								iconName = actions-edit-hide
								displayCondition = getRecord|hidden = 0 && canBeDisabledAndEnabled != 0
								callbackAction = disablePage
							}

							400 = ITEM
							400 {
								name = enable
								label = LLL:EXT:lang/locallang_common.xlf:enable
								iconName = actions-edit-unhide
								displayCondition = getRecord|hidden = 1 && canBeDisabledAndEnabled != 0
								callbackAction = enablePage
							}

							500 = ITEM
							500 {
								name = edit
								label = LLL:EXT:lang/locallang_core.xlf:cm.edit
								iconName = actions-page-open
								displayCondition = canBeEdited != 0
								callbackAction = editPageProperties
							}

							600 = ITEM
							600 {
								name = info
								label = LLL:EXT:lang/locallang_core.xlf:cm.info
								iconName = actions-document-info
								displayCondition = canShowInfo != 0
								callbackAction = openInfoPopUp
							}

							700 = ITEM
							700 {
								name = history
								label = LLL:EXT:lang/locallang_misc.xlf:CM_history
								iconName = actions-document-history-open
								displayCondition = canShowHistory != 0
								callbackAction = openHistoryPopUp
							}

							800 = DIVIDER

							900 = SUBMENU
							900 {
								label = LLL:EXT:lang/locallang_core.xlf:cm.copyPasteActions

								100 = ITEM
								100 {
									name = new
									label = LLL:EXT:lang/locallang_core.xlf:cm.new
									iconName = actions-page-new
									displayCondition = canCreateNewPages != 0
									callbackAction = newPageWizard
								}

								200 = DIVIDER

								300 = ITEM
								300 {
									name = cut
									label = LLL:EXT:lang/locallang_core.xlf:cm.cut
									iconName = actions-edit-cut
									displayCondition = isInCutMode = 0 && canBeCut != 0 && isMountPoint != 1
									callbackAction = enableCutMode
								}

								400 = ITEM
								400 {
									name = cut
									label = LLL:EXT:lang/locallang_core.xlf:cm.cut
									iconName = actions-edit-cut-release
									displayCondition = isInCutMode = 1 && canBeCut != 0
									callbackAction = disableCutMode
								}

								500 = ITEM
								500 {
									name = copy
									label = LLL:EXT:lang/locallang_core.xlf:cm.copy
									iconName = actions-edit-copy
									displayCondition = isInCopyMode = 0
									callbackAction = enableCopyMode
								}

								600 = ITEM
								600 {
									name = copy
									label = LLL:EXT:lang/locallang_core.xlf:cm.copy
									iconName = actions-edit-copy-release
									displayCondition = isInCopyMode = 1
									callbackAction = disableCopyMode
								}

								700 = ITEM
								700 {
									name = pasteInto
									label = LLL:EXT:lang/locallang_core.xlf:cm.pasteinto
									iconName = actions-document-paste-into
									displayCondition = getContextInfo|inCopyMode = 1 || getContextInfo|inCutMode = 1 && canBePastedInto != 0
									callbackAction = pasteIntoNode
								}

								800 = ITEM
								800 {
									name = pasteAfter
									label = LLL:EXT:lang/locallang_core.xlf:cm.pasteafter
									iconName = actions-document-paste-after
									displayCondition = getContextInfo|inCopyMode = 1 || getContextInfo|inCutMode = 1 && canBePastedAfter != 0
									callbackAction = pasteAfterNode
								}

								900 = DIVIDER

								1000 = ITEM
								1000 {
									name = delete
									label = LLL:EXT:lang/locallang_core.xlf:cm.delete
									iconName = actions-edit-delete
									displayCondition = canBeRemoved != 0 && isMountPoint != 1
									callbackAction = removeNode
								}
							}

							1000 = SUBMENU
							1000 {
								label = LLL:EXT:lang/locallang_core.xlf:cm.branchActions

								100 = ITEM
								100 {
									name = mountAsTreeroot
									label = LLL:EXT:lang/locallang_core.xlf:cm.tempMountPoint
									iconName = actions-pagetree-mountroot
									displayCondition = canBeTemporaryMountPoint != 0 && isMountPoint = 0
									callbackAction = mountAsTreeRoot
								}

								200 = DIVIDER

								300 = ITEM
								300 {
									name = expandBranch
									label = LLL:EXT:lang/locallang_core.xlf:cm.expandBranch
									iconName = actions-pagetree-expand
									displayCondition =
									callbackAction = expandBranch
								}

								400 = ITEM
								400 {
									name = collapseBranch
									label = LLL:EXT:lang/locallang_core.xlf:cm.collapseBranch
									iconName = actions-pagetree-collapse
									displayCondition =
									callbackAction = collapseBranch
								}
							}
						}
					}
				}
			}
		',
        // String (exclude). Enter lines of default backend user/group TSconfig.
        'defaultPageTSconfig' => 'mod.web_list.enableDisplayBigControlPanel=selectable
			mod.web_list.enableClipBoard=selectable
			mod.web_list.enableLocalizationView=selectable
			mod.web_list.tableDisplayOrder {
				be_users.after = be_groups
				sys_filemounts.after = be_users
				sys_file_storage.after = sys_filemounts
				sys_language.after = sys_file_storage
				pages_language_overlay.before = pages
				fe_users.after = fe_groups
				fe_users.before = pages
				sys_template.after = pages
				backend_layout.after = pages
				sys_domain.after = sys_template
				tt_content.after = pages,backend_layout,sys_template
				sys_category.after = tt_content
			}
			mod.wizards.newRecord.pages.show.pageInside=1
			mod.wizards.newRecord.pages.show.pageAfter=1
			mod.wizards.newRecord.pages.show.pageSelectPosition=1
			mod.web_view.previewFrameWidths {
				1280.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:computer
				1024.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:tablet
				960.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:mobile
				800.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:computer
				768.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:tablet
				600.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:tablet
				640.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:mobile
				480.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:mobile
				400.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:mobile
				360.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:mobile
				300.label = LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:mobile
			}
		',
        // String (exclude).Enter lines of default Page TSconfig.
        'defaultPermissions' => [],
        'defaultUC' => [],
        // The control of file extensions goes in two catagories. Webspace and Ftpspace. Webspace is folders accessible from a webbrowser (below TYPO3_DOCUMENT_ROOT) and ftpspace is everything else.
        // The control is done like this: If an extension matches 'allow' then the check returns TRUE. If not and an extension matches 'deny' then the check return FALSE. If no match at all, returns TRUE.
        // You list extensions comma-separated. If the value is a '*' every extension is matched
        // If no file extension, TRUE is returned if 'allow' is '*', FALSE if 'deny' is '*' and TRUE if none of these matches
        // This configuration below accepts everything in ftpspace and everything in webspace except php3,php4,php5 or php files
        'fileExtensions' => [
            'webspace' => ['allow' => '', 'deny' => PHP_EXTENSIONS_DEFAULT],
            'ftpspace' => ['allow' => '*', 'deny' => '']
        ],
        'customPermOptions' => [],                        // Array with sets of custom permission options. Syntax is; 'key' => array('header' => 'header string, language splitted', 'items' => array('key' => array('label, language splitted', 'icon reference', 'Description text, language splitted'))). Keys cannot contain ":|," characters.
        'fileDenyPattern' => FILE_DENY_PATTERN_DEFAULT,        // A perl-compatible regular expression (without delimiters!) that - if it matches a filename - will deny the file upload/rename or whatever in the webspace. For security reasons, files with multiple extensions have to be denied on an Apache environment with mod_alias, if the filename contains a valid php handler in an arbitrary position. Also, ".htaccess" files have to be denied. Matching is done case-insensitive. Default value is stored in constant FILE_DENY_PATTERN_DEFAULT
        'interfaces' => 'backend',                            // This determines which interface options is available in the login prompt and in which order (All options: ",backend,frontend")
        'notificationPrefix' => '[TYPO3 Note]',                // String: Used to prefix the subject of mails sent in the taskcenter
        'explicitADmode' => 'explicitDeny',                    // Sets the general allow/deny mode for selector box values. Value can be either "explicitAllow" or "explicitDeny", nothing else!
        'niceFlexFormXMLtags' => true,                        // If set, the flexform XML will be stored with meaningful tags which can be validated with DTD schema. If you rely on custom reading of the XML from pre-4.0 versions you should set this to FALSE if you don't like to change your reader code (internally it is insignificant since \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array() doesn't care for the tags if the index-attribute value is set)
        'compactFlexFormXML' => 0,                            // If set, the flexform XML will not contain indentation spaces making XML more compact
        'flexformForceCDATA' => 0,                            // Boolean:  If set, will add CDATA to Flexform XML. Some versions of libxml have a bug that causes HTML entities to be stripped from any XML content and this setting will avoid the bug by adding CDATA.
        'explicitConfirmationOfTranslation' => false,        // If set, then the diff-data of localized records is not saved automatically when updated but requires that a translator clicks the special finish_translation/save/close button that becomes available.
        'versionNumberInFilename' => false,                    // <p>Boolean: If TRUE, included CSS and JS files will have the timestamp embedded in the filename, ie. filename.1269312081.js. This will make browsers and proxies reload the files if they change (thus avoiding caching issues). IMPORTANT: this feature requires extra .htaccess rules to work (please refer to _.htaccess or the _.htaccess file from the dummy package)</p><p>If FALSE the filemtime will be appended as a query-string.</p>
        /**
         * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8
         */
        'spriteIconGenerator_handler' => \TYPO3\CMS\Backend\Sprite\SimpleSpriteHandler::class,        // String: Used to register own/other spriteGenerating Handler, they have to implement the interface \TYPO3\CMS\Backend\Sprite\SpriteIconGeneratorInterface. If set to "\TYPO3\CMS\Backend\Sprite\SpriteBuildingHandler" icons from extensions will automatically merged into sprites.
        'debug' => false,                                    // Boolean: If set, the loginrefresh is disabled and pageRenderer is set to debug mode. Use this to debug the backend only!
        'AJAX' => [],                                    // array of key-value pairs for a unified use of AJAX calls in the TYPO3 backend. Keys are the unique ajaxIDs where the value will be resolved to call a method in an object. See the AjaxRequestHandler class for more information.
        'toolbarItems' => [], // Array: Registered toolbar items classes
        'HTTP' => [
            'Response' => [
                'Headers' => ['clickJackingProtection' => 'X-Frame-Options: SAMEORIGIN']
            ]
        ],
        'XCLASS' => []
    ],
    'FE' => [ // Configuration for the TypoScript frontend (FE). Nothing here relates to the administration backend!
        'addAllowedPaths' => '',        // Additional relative paths (comma-list) to allow TypoScript resources be in. Should be prepended with '/'. If not, then any path where the first part is like this path will match. That is: 'myfolder/ , myarchive' will match eg. 'myfolder/', 'myarchive/', 'myarchive_one/', 'myarchive_2/' ... No check is done to see if this directory actually exists in the root of the site. Paths are matched by simply checking if these strings equals the first part of any TypoScript resource filepath. (See class template, function init() in \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser)
        'debug' => false,        // Boolean: If set, some debug HTML-comments may be output somewhere. Can also be set by TypoScript.
        'noPHPscriptInclude' => false,        // Boolean: If set, PHP-scripts are not included by TypoScript configurations, unless they reside in the folders typo3/ext/, typo3/sysext/ or typo3conf/ext. This is a security option to ensure that users with template-access do not terrorize
        'strictFormmail' => true,        // Boolean: If set, the internal "formmail" feature in TYPO3 will send mail ONLY to recipients which has been encoded by the system itself. This protects against spammers misusing the formmailer. This option has been marked as deprecated since TYPO3 CMS 7 and will be removed in TYPO3 CMS 8.
        'secureFormmail' => true,        // Boolean: If set, the internal "formmail" feature in TYPO3 will send mail ONLY to the recipients that are defined in the form CE record. This protects against spammers misusing the formmailer. This option has been marked as deprecated since TYPO3 CMS 7 and will be removed in TYPO3 CMS 8.
        'formmailMaxAttachmentSize' => 250000,        // Integer: Sets the maximum allowed size (in bytes) of attachments for the internal "formmail" feature.This option has been marked as deprecated since TYPO3 CMS 7 and will be removed in TYPO3 CMS 8.
        'compressionLevel' => 0,        // Integer: Determines output compression of FE output. Makes output smaller but slows down the page generation depending on the compression level. Requires zlib in your PHP installation. Range 1-9, where 1 is least compression and 9 is greatest compression. 'true' as value will set the compression based on the PHP default settings (usually 5). Suggested and most optimal value is 5.
        'pageNotFound_handling' => '',        // <p>How TYPO3 should handle requests for non-existing/accessible pages.</p> <dl><dt>empty (default)</dt><dd>The next visible page upwards in the page tree is shown.</dd> <dt>'true' or '1'</dt><dd>An error message is shown.</dd><dt>String</dt><dd>Static HTML file to show (reads content and outputs with correct headers), e.g. 'notfound.html' or 'http://www.example.org/errors/notfound.html'.</dd> <dt>Prefix "REDIRECT:"</dt><dd> If prefixed with "REDIRECT:" it will redirect to the URL/script after the prefix.</dd><dt>Prefix "READFILE:"</dt><dd>If prefixed with "READFILE" then it will expect the remaining string to be a HTML file which will be read and outputted directly after having the marker "###CURRENT_URL###" substituted with REQUEST_URI and ###REASON### with reason text, for example: "READFILE:fileadmin/notfound.html".</dd> <dt>Prefix "USER_FUNCTION:"</dt><dd> If prefixed with "USER_FUNCTION:" a user function is called, e.g. "USER_FUNCTION:fileadmin/class.user_notfound.php:user_notFound->pageNotFound" where the file must contain a class "user_notFound" with a method "pageNotFound" inside with two parameters $param and $ref.</dd></dl>
        'pageNotFound_handling_statheader' => 'HTTP/1.0 404 Not Found',        // If 'pageNotFound_handling' is enabled, this string will always be sent as header before the actual handling.
        'pageNotFoundOnCHashError' => true,        // Boolean: If TRUE, a page not found call is made when cHash evaluation error occurs, otherwise caching is disabled and page output is displayed.
        'pageUnavailable_handling' => '',        // <p>How TYPO3 should handle requests when pages are unavailable due to system problems.</p><dl><dt>empty (default)</dt><dd>An error message is shown.</dd><dt>String</dt><dd>HTML file or URL to show (reads content and outputs with correct headers), e.g. 'unavailable.html' or 'http://www.example.org/errors/unavailable.html'.</dd><dt>Prefix "REDIRECT:"</dt><dd>If prefixed "REDIRECT:" it will redirect to the URL/script after the prefix.</dd><dt>Prefix "READFILE:"</dt><dd>If prefixed with "READFILE:" then it will expect the remaining string to be a HTML file which will be read and outputted directly after having the marker "###CURRENT_URL###" substituted with REQUEST_URI and ###REASON### with reason text, for example: "READFILE:fileadmin/unavailable.html".</dd><dt>Prefix "USER_FUNCTION:"</dt><dd>If prefixed "USER_FUNCTION:" then it will call a user function, eg. "USER_FUNCTION:fileadmin/class.user_unavailable.php:user_unavailable->pageUnavailable" where the file must contain a class "user_unavailable" with a method "pageUnavailable" inside with two parameters $param and $ref. If the client matches <a href="#SYS-devIPmask">[SYS][devIPmask]</a>, this setting is ignored and the page is shown as normal.</dd></dl>
        'pageUnavailable_handling_statheader' => 'HTTP/1.0 503 Service Temporarily Unavailable',        // If 'pageUnavailable_handling' is enabled, this string will always be sent as header before the actual handling.
        'pageUnavailable_force' => false,        // Boolean: If TRUE, pageUnavailable_handling is used for every frontend page. If the client matches <a href="#SYS-devIPmask">[SYS][devIPmask]</a>, the page is shown as normal. This is useful during temporary site maintenance.
        'addRootLineFields' => '',        // Comma-list of fields from the 'pages'-table. These fields are added to the select query for fields in the rootline.
        'checkFeUserPid' => true,        // Boolean: If set, the pid of fe_user logins must be sent in the form as the field 'pid' and then the user must be located in the pid. If you unset this, you should change the fe_users.username eval-flag 'uniqueInPid' to 'unique' in $TCA. This will do: $TCA['fe_users']['columns']['username']['config']['eval']= 'nospace,lower,required,unique';
        'lockIP' => 2,        // Integer (0-4). If >0, fe_users are locked to (a part of) their REMOTE_ADDR IP for their session. Enhances security but may throw off users that may change IP during their session (in which case you can lower it to 2 or 3). The integer indicates how many parts of the IP address to include in the check. Reducing to 1-3 means that only first, second or third part of the IP address is used. 4 is the FULL IP address and recommended. 0 (zero) disables checking of course.
        'loginSecurityLevel' => '',        // See description for <a href="#BE-loginSecurityLevel">[BE][loginSecurityLevel]</a>. Default state for frontend is "normal". Alternative authentication services can implement higher levels if preferred. For example, "rsa" level uses RSA password encryption (only if the rsaauth extension is installed)
        'lifetime' => 0,        // Integer: positive. If >0 and the option permalogin is >=0, the cookie of FE users will have a lifetime of the number of seconds this value indicates. Otherwise it will be a session cookie (deleted when browser is shut down). Setting this value to 604800 will result in automatic login of FE users during a whole week, 86400 will keep the FE users logged in for a day.
        'sessionDataLifetime' => 86400,        // Integer: positive. If >0, the session data will timeout and be removed after the number of seconds given (86400 seconds represents 24 hours).
        'permalogin' => 0,        // <p>Integer:</p><dl><dt>-1</dt><dd>Permanent login for FE users disabled.</dd><dt>0</dt><dd>By default permalogin is disabled for FE users but can be enabled by a form control in the login form.</dd><dt>1</dt><dd>Permanent login is by default enabled but can be disabled by a form control in the login form.</dd><dt>2</dt><dd>Permanent login is forced to be enabled.// In any case, permanent login is only possible if <a href="#FE-lifetime">[FE][lifetime]</a> lifetime is > 0.</dd></dl>
        'maxSessionDataSize' => 10000,        // Integer: Setting the maximum size (bytes) of frontend session data stored in the table fe_session_data. Set to zero (0) means no limit, but this is not recommended since it also disables a check that session data is stored only if a confirmed cookie is set.
        'cookieDomain' => '',        // Same as <a href="#SYS-cookieDomain">$TYPO3_CONF_VARS['SYS']['cookieDomain']</a> but only for FE cookies. If empty, $TYPO3_CONF_VARS['SYS']['cookieDomain'] value will be used.
        'cookieName' => 'fe_typo_user',        // String: Set the name for the cookie used for the front-end user session
        'lockHashKeyWords' => 'useragent',        // Keyword list (Strings commaseparated). Currently only "useragent"; If set, then the FE user session is locked to the value of HTTP_USER_AGENT. This lowers the risk of session hi-jacking. However some cases (like payment gateways) might have to use the session cookie and in this case you will have to disable that feature (eg. with a blank string).
        'defaultUserTSconfig' => '',        // String (textarea). Enter lines of default frontend user/group TSconfig.
        'defaultTypoScript_constants' => '',        // String (textarea). Enter lines of default TypoScript, constants-field.
        'defaultTypoScript_constants.' => [],        // Lines of TS to include after a static template with the uid = the index in the array (Constants)
        'defaultTypoScript_setup' => '',        // String (textarea). Enter lines of default TypoScript, setup-field.
        'defaultTypoScript_setup.' => [],        // Lines of TS to include after a static template with the uid = the index in the array (Setup)
        'additionalAbsRefPrefixDirectories' => '',        // Enter additional directories to be prepended with absRefPrefix. Directories must be comma-separated. TYPO3 already prepends the following directories: typo3/, typo3temp/, typo3conf/ext/ and all local storages
        'IPmaskMountGroups' => [ // This allows you to specify an array of IPmaskLists/fe_group-uids. If the REMOTE_ADDR of the user matches an IPmaskList, then the given fe_group is add to the gr_list. So this is an automatic mounting of a user-group. But no fe_user is logged in though! This feature is implemented for the default frontend user authentication and might not be implemented for alternative authentication services.
            // array('IPmaskList_1','fe_group uid'), array('IPmaskList_2','fe_group uid')
        ],
        'get_url_id_token' => '#get_URL_ID_TOK#',        // This is the token, which is substituted in the output code in order to keep a GET-based session going. Normally the GET-session-id is 5 chars ('&amp;ftu=') + hash_length (norm. 10)
        'content_doktypes' => '1,2,5,7',        // List of pages.doktype values which can contain content (so shortcut pages and external url pages are excluded, but all pages below doktype 199 should be included. doktype=6 is not either (backend users only...).
        'enable_mount_pids' => true,        // Boolean: If set to "1", the mount_pid feature allowing 'symlinks' in the page tree (for frontend operation) is allowed.
        'pageOverlayFields' => 'uid,doktype,title,subtitle,nav_title,media,keywords,description,abstract,author,author_email,url,urltype,shortcut,shortcut_mode',        // List of fields from the table "pages_language_overlay" which should be overlaid on page records. See \TYPO3\CMS\Frontend\Page\PageRepository::getPageOverlay()
        'hidePagesIfNotTranslatedByDefault' => false,        // Boolean: If TRUE, pages that has no translation will be hidden by default. Basically this will inverse the effect of the page localization setting "Hide page if no translation for current language exists" to "Show page even if no translation exists"
        'eID_include' => [],        // Array of key/value pairs where key is "tx_[ext]_[optional suffix]" and value is relative filename of class to include. Key is used as "?eID=" for \TYPO3\CMS\Frontend\Http\RequestHandlerRequestHandler to include the code file which renders the page from that point. (Useful for functionality that requires a low initialization footprint, eg. frontend ajax applications)
        'disableNoCacheParameter' => false,        // Boolean: If set, the no_cache request parameter will become ineffective. This is currently still an experimental feature and will require a website only with plugins that don't use this parameter. However, using "&amp;no_cache=1" should be avoided anyway because there are better ways to disable caching for a certain part of the website (see COA_INT/USER_INT documentation in TSref).
        'cacheHash' => [],        // Array: Processed values of the cHash* parameters, handled by core bootstrap internally
        'cHashIncludePageId' => false,        // Boolean: If enabled the cHash calculation is bound to the current page ID. This is recommended to avoid generation of not required page cache entries. Changing this value will void all links that have a cHash argument, as the cHash will change.
        'cHashExcludedParameters' => 'L, pk_campaign, pk_kwd, utm_source, utm_medium, utm_campaign, utm_term, utm_content',        // String: The the given parameters will be ignored in the cHash calculation. Example: L,tx_search_pi1[query]
        'cHashOnlyForParameters' => '',        // String: Only the given parameters will be evaluated in the cHash calculation. Example: tx_news_pi1[uid]
        'cHashRequiredParameters' => '',        // Optional: Configure Parameters that require a cHash. If no cHash is given but one of the parameters are set, then TYPO3 triggers the configured cHash Error behaviour
        'cHashExcludedParametersIfEmpty' => '',        // Optional: Configure Parameters that are only relevant for the chash if there's an associated value available. And asterisk "*" can be used to skip all empty parameters.
        'workspacePreviewLogoutTemplate' => '',        // If set, points to an HTML file relative to the TYPO3_site root which will be read and outputted as template for this message. Example: fileadmin/templates/template_workspace_preview_logout.html. Inside you can put the marker %1$s to insert the URL to go back to. Use this in &lt;a href="%1$s"&gt;Go back...&lt;/a&gt; links
        'versionNumberInFilename' => 'querystring',        // String: embed,querystring,''. Allows to automatically include a version number (timestamp of the file) to referred CSS and JS filenames on the rendered page. This will make browsers and proxies reload the files if they change (thus avoiding caching issues). Set to 'embed' will have the timestamp embedded in the filename, ie. filename.1269312081.js. IMPORTANT: 'embed' requires extra .htaccess rules to work (please refer to _.htaccess or the _.htaccess file from the dummy package)<p>Set to 'querystring' (default setting) to append the version number as a query parameter (doesn't require mod_rewrite). Set to '' will turn this functionality off (behaves like TYPO3 &lt; v4.4).</p>
        'contentRenderingTemplates' => [],    // Array to define the TypoScript parts that define the main content rendering. Extensions like "css_styled_content" provide content rendering templates. Other extensions like "felogin" or "indexed search" extend these templates and their TypoScript parts are added directly after the content templates. See EXT:css_styled_content/ext_localconf.php and EXT:frontend/Classes/TypoScript/TemplateService.php
        'ContentObjects' => [],    // Array to register ContentObject (cObjects) like TEXT or HMENU within ext_localconf.php, see EXT:frontend/ext_localconf.php
        'XCLASS' => [],        // See 'Inside TYPO3' document for more information.
    ],
    'MAIL' => [ // Mail configurations to tune how \TYPO3\CMS\Core\Mail\ classes will send their mails.
        'transport' => 'mail',        // <p>String:</p><dl><dt>mail</dt><dd>Sends messages by delegating to PHP's internal mail() function. No further settings required. This is the most unreliable option. If you are serious about sending mails, consider using "smtp" or "sendmail".</dd><dt>smtp</dt><dd>Sends messages over the (standardized) Simple Message Transfer Protocol. It can deal with encryption and authentication. Most flexible option, requires a mail server and configurations in transport_smtp_* settings below. Works the same on Windows, Unix and MacOS.</dd><dt>sendmail</dt><dd>Sends messages by communicating with a locally installed MTA - such as sendmail. See setting transport_sendmail_command bellow.<dd><dt>mbox</dt><dd>This doesn't send any mail out, but instead will write every outgoing mail to a file adhering to the RFC 4155 mbox format, which is a simple text file where the mails are concatenated. Useful for debugging the mail sending process and on development machines which cannot send mails to the outside. Configure the file to write to in the 'transport_mbox_file' setting below</dd><dt>&lt;classname&gt;</dt><dd>Custom class which implements Swift_Transport. The constructor receives all settings from the MAIL section to make it possible to add custom settings.</dd></dl>
        'transport_smtp_server' => 'localhost:25',        // String: <em>only with transport=smtp</em>: &lt;server:port> of mailserver to connect to. &lt;port> defaults to "25".
        'transport_smtp_encrypt' => '',        // String: <em>only with transport=smtp</em>: Connect to the server using the specified transport protocol. Requires openssl library. Usually available: <em>ssl, sslv2, sslv3, tls</em>. Check <a href="http://www.php.net/stream_get_transports" target="_blank">stream_get_transports()</a>.
        'transport_smtp_username' => '',        // String: <em>only with transport=smtp</em>: If your SMTP server requires authentication, enter your username here.
        'transport_smtp_password' => '',        // String: <em>only with transport=smtp</em>: If your SMTP server requires authentication, enter your password here.
        'transport_sendmail_command' => '',        // String: <em>only with transport=sendmail</em>: The command to call to send a mail locally.
        'transport_mbox_file' => '',        // String: <em>only with transport=mbox</em>: The file where to write the mails into. This file will be conforming the mbox format described in RFC 4155. It is a simple text file with a concatenation of all mails. Path must be absolute.
        'defaultMailFromAddress' => '',        // String: This default email address is used when no other "from" address is set for a TYPO3-generated email. You can specify an email address only (ex. info@example.org).
        'defaultMailFromName' => ''// String: This default name is used when no other "from" name is set for a TYPO3-generated email.
    ],
    'HTTP' => [ // HTTP configuration to tune how TYPO3 behaves on HTTP request. Have a look at <a href="http://pear.php.net/manual/en/package.http.http-request2.config.php>HTTP_Request2 Manual</a> for some background information on those settings.
        'adapter' => 'socket',        // String: Default adapter - either "socket" or "curl".
        'connect_timeout' => 10,        // Integer: Default timeout for connection. Exception will be thrown if connecting to remote host takes more than this number of seconds.
        'timeout' => 0,        // Integer: Default timeout for whole request. Exception will be thrown if sending the request takes more than this number of seconds. Should be greater than connection timeout (see above) or "0" to not set a limit. Defaults to "0".
        'protocol_version' => '1.1',        // String: Default HTTP protocol version. Use either "1.0" or "1.1".
        'follow_redirects' => false,        // Boolean: If set, redirects are followed by default. If number of tries are exceeded, an exception is thrown.
        'max_redirects' => 5,        // Integer: Maximum number of tries before an exception is thrown.
        'strict_redirects' => false,        // Boolean: Whether to keep request method on redirects via status 301 and 302 (TRUE, needed for compatibility with <a href="http://www.faqs.org/rfcs/rfc2616">RFC 2616</a>) or switch to GET (FALSE, needed for compatibility with most browsers). There are some <a href="http://pear.php.net/manual/en/package.http.http-request2.adapters.php#package.http.http-request2.adapters.curl">issues with cURL adapter</a>. Defaults to FALSE.
        'proxy_host' => '',        // String: Default proxy server as "proxy.example.org" (You must not set the protocol or the port here. Set the port below.)
        'proxy_port' => '',        // Integer: Default proxy server port.
        'proxy_user' => '',        // String: Default user name.
        'proxy_password' => '',        // String: Default password.
        'proxy_auth_scheme' => 'basic',        // String: Default authentication method. Can either be "basic" or "digest". Defaults to "basic".
        'ssl_verify_peer' => false,        // Boolean: Whether to verify peer's SSL certificate. Turned off by default, due to <a href="http://pear.php.net/manual/en/package.http.http-request2.adapters.php#package.http.http-request2.adapters.socket" target="_blank">issues with Socket adapter</a>. You are advised to use the <em>curl</em> adapter and enable this option!
        'ssl_verify_host' => true,        // Boolean: Whether to check that Common Name in SSL certificate matches hostname. There are some <a href="http://pear.php.net/manual/en/package.http.http-request2.adapters.php#package.http.http-request2.adapters.socket" target="_blank">issues with Socket Adapter</a>.
        'ssl_cafile' => '',        // String: Certificate Authority file to verify the peer with (use when ssl_verify_peer is TRUE).
        'ssl_capath' => '',        // String: Directory holding multiple Certificate Authority files.
        'ssl_local_cert' => '',        // String: Name of a file containing local certificate.
        'ssl_passphrase' => '',        // String: Passphrase with which local certificate was encoded.
        'userAgent' => 'TYPO3/' . TYPO3_version// String: Default user agent. If empty, this will be "TYPO3/x.y.z", while x.y.z is the current version. This overrides the constant <em>TYPO3_user_agent</em>.
    ],
    'LOG' => [
        'writerConfiguration' => [
            \TYPO3\CMS\Core\Log\LogLevel::WARNING => [
                \TYPO3\CMS\Core\Log\Writer\FileWriter::class => []
            ]
        ],
        'TYPO3' => [
            'CMS' => [
                'Core' => [
                    'Resource' => [
                        'ResourceStorage' => [
                            'writerConfiguration' => [
                                \TYPO3\CMS\Core\Log\LogLevel::ERROR => [
                                    \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [],
                                    \TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class => []
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],
    'MODS' => [],
    'USER' => [],
    'SC_OPTIONS' => [
        // Here you can more or less freely define additional configuration for scripts in TYPO3. Of course the features supported depends on the script. See documentation "Inside TYPO3" for examples. Keys in the array are the relative path of a script (for output scripts it should be the "script ID" as found in a comment in the HTML header ) and values can then be anything that scripts wants to define for itself. The key "GLOBAL" is reserved.
        'GLOBAL' => [
            'softRefParser' => [
                'substitute' => \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
                'notify' => \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
                'images' => \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
                'typolink' => \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
                'typolink_tag' => \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
                'TSconfig' => \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
                'TStemplate' => \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
                'ext_fileref' => \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
                'email' => \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
                'url' => \TYPO3\CMS\Core\Database\SoftReferenceIndex::class,
            ],
            // @deprecated global soft reference parsers are deprecated since TYPO3 CMS 7 and will be removed in TYPO3 CMS 8
            'softRefParser_GL' => [],
            'cliKeys' => []
        ],
    ],
    'EXTCONF' => [
        // Here you may add manually set configuration options for your extensions. Eg. $TYPO3_CONF_VARS['EXTCONF']['my_extension_key']['my_option'] = 'my_value';
        'cms' => [
            'db_layout' => [
                'addTables' => [
                    'fe_users' => [
                        0 => [
                            'MENU' => '',
                            'fList' => 'username,usergroup,name,email,telephone,address,zip,city',
                            'icon' => true
                        ]
                    ]
                ]
            ]
        ]
    ],
    'SVCONF' => []
];
