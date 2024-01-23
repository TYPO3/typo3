.. include:: /Includes.rst.txt

.. _breaking-101305-1689059968:

==================================================================================
Breaking: #101305 - Introduce type declarations for some methods in GeneralUtility
==================================================================================

See :issue:`101305`, :issue:`101453`

Description
===========

Native return and param type declarations have been introduced for the following
methods of :php:`\TYPO3\CMS\Core\Utility\GeneralUtility`:

- :php:`addInstance()`
- :php:`array2xml()`
- :php:`callUserFunction()`
- :php:`cmpFQDN()`
- :php:`cmpIP()`
- :php:`cmpIPv4()`
- :php:`cmpIPv6()`
- :php:`copyDirectory()`
- :php:`createDirectoryPath()`
- :php:`createVersionNumberedFilename()`
- :php:`explodeUrl2Array()`
- :php:`fixPermissions()`
- :php:`flushInternalRuntimeCaches()`
- :php:`getAllFilesAndFoldersInPath()`
- :php:`getBytesFromSizeMeasurement()`
- :php:`getClassName()`
- :php:`getFileAbsFileName()`
- :php:`getFilesInDir()`
- :php:`getIndpEnv()`
- :php:`getInstances()`
- :php:`getLogger()`
- :php:`getSingletonInstances()`
- :php:`getUrl()`
- :php:`get_dirs()`
- :php:`get_tag_attributes()`
- :php:`implodeArrayForUrl()`
- :php:`implodeAttributes()`
- :php:`intExplode()`
- :php:`isAllowedAbsPath()`
- :php:`isOnCurrentHost()`
- :php:`isValidUrl()`
- :php:`jsonEncodeForHtmlAttribute()`
- :php:`jsonEncodeForJavaScript()`
- :php:`locationHeaderUrl()`
- :php:`makeInstanceForDi()`
- :php:`mkdir_deep()`
- :php:`mkdir()`
- :php:`normalizeIPv6()`
- :php:`purgeInstances()`
- :php:`quoteJSvalue()`
- :php:`removePrefixPathFromList()`
- :php:`removeSingletonInstance()`
- :php:`resetSingletonInstances()`
- :php:`resolveBackPath()`
- :php:`revExplode()`
- :php:`rmdir()`
- :php:`sanitizeLocalUrl()`
- :php:`setIndpEnv()`
- :php:`setSingletonInstance()`
- :php:`split_tag_attributes()`
- :php:`tempnam()`
- :php:`trimExplode()`
- :php:`unlink_tempfile()`
- :php:`upload_copy_move()`
- :php:`upload_to_tempfile()`
- :php:`validEmail()`
- :php:`validIP()`
- :php:`validIPv4()`
- :php:`validIPv6()`
- :php:`validPathStr()`
- :php:`webserverUsesHttps()`
- :php:`wrapJS()`
- :php:`writeFileToTypo3tempDir()`
- :php:`writeFile()`
- :php:`writeJavaScriptContentToTemporaryFile()`
- :php:`writeStyleSheetContentToTemporaryFile()`
- :php:`xml2arrayProcess()`
- :php:`xml2array()`
- :php:`xml2tree()`
- :php:`xmlRecompileFromStructValArray()`

Impact
======

Calling any of the mentioned methods with invalid types will result in a
PHP error.

Affected installations
======================

Only those installations that use the mentioned methods with invalid types.

Migration
=========

Make sure to pass parameters of the required types to the mentioned methods.

.. index:: PHP-API, NotScanned, ext:core
