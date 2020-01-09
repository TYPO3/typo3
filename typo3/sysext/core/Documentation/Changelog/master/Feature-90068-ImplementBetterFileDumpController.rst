.. include:: ../../Includes.txt

=====================================================================
Feature: #90068 - Implement better FileDumpController
=====================================================================

See :issue:`90068`

Description
===========

FileDumpController can now process UIDs of sys_file_reference records and
can adopt image sizes to records of sys_file.

Following URI-Parameters are now possible:

+ `t` (*Type*): Can be one of `f` (sys_file), `r` (sys_file_reference) or `p` (sys_file_processedfile)
+ `f` (*File*): Use it for an UID of table sys_file
+ `r` (*Reference*): Use it for an UID of table sys_file_reference
+ `p` (*Processed*): Use it for an UID of table sys_file_processedfile
+ `s` (*Size*): Use it for an UID of table sys_file_processedfile
+ `cv` (*CropVariant*): In case of sys_file_reference, you can assign it a cropping variant

You have to choose one of these parameters: `f`, `r` and `p`. It is not possible
to use them multiple times in one request.

The Parameter `s` has following syntax: width:height:minW:minH:maxW:maxH. You
can leave this Parameter empty to load file in original size. Parameter `width`
and `height` can consist of trailing `c` or `m` identicator like known from TS.

See following example how to create an URI using the FileDumpController for
a sys_file record with a fixed image size:

.. code-block:: php

   $queryParameterArray = ['eID' => 'dumpFile', 't' => 'f'];
   $queryParameterArray['f'] = $resourceObject->getUid();
   $queryParameterArray['s'] = '320c:280c';
   $queryParameterArray['token'] = GeneralUtility::hmac(implode('|', $queryParameterArray), 'resourceStorageDumpFile');
   $publicUrl = GeneralUtility::locationHeaderUrl(PathUtility::getAbsoluteWebPath(Environment::getPublicPath() . '/index.php'));
   $publicUrl .= '?' . http_build_query($queryParameterArray, '', '&', PHP_QUERY_RFC3986);


In this example crop variant `default` and an image size of 320:280 will be
applied to a sys_file_reference record:

.. code-block:: php

   $queryParameterArray = ['eID' => 'dumpFile', 't' => 'r'];
   $queryParameterArray['f'] = $resourceObject->getUid();
   $queryParameterArray['s'] = '320c:280c:320:280:320:280';
   $queryParameterArray['cv'] = 'default';
   $queryParameterArray['token'] = GeneralUtility::hmac(implode('|', $queryParameterArray), 'resourceStorageDumpFile');
   $publicUrl = GeneralUtility::locationHeaderUrl(PathUtility::getAbsoluteWebPath(Environment::getPublicPath() . '/index.php'));
   $publicUrl .= '?' . http_build_query($queryParameterArray, '', '&', PHP_QUERY_RFC3986);


This example shows the usage how to create an URI to load an image of
sys_file_processfiles:

.. code-block:: php

   $queryParameterArray = ['eID' => 'dumpFile', 't' => 'p'];
   $queryParameterArray['p'] = $resourceObject->getUid();
   $queryParameterArray['token'] = GeneralUtility::hmac(implode('|', $queryParameterArray), 'resourceStorageDumpFile');
   $publicUrl = GeneralUtility::locationHeaderUrl(PathUtility::getAbsoluteWebPath(Environment::getPublicPath() . '/index.php'));
   $publicUrl .= '?' . http_build_query($queryParameterArray, '', '&', PHP_QUERY_RFC3986);


There are some restriction while using the new URI-Parameters:
+ You can't assign any size parameter to processed files, as they are already resized.
+ You can't apply CropVariants to sys_file and sys_file_processedfile records.


Impact
======

No impact, as this class was extended only. It's full backwards compatible

.. index:: FAL, ext:core
