.. include:: /Includes.rst.txt

=====================================================================
Feature: #90068 - Implement better FileDumpController
=====================================================================

See :issue:`90068`

Description
===========

FileDumpController can now process UIDs of sys_file_reference records and
can adopt image sizes to records of sys_file.

Following URI-Parameters are now possible:

+ :php:`t` (*Type*): Can be one of :php:`f` (`sys_file`), :php:`r` (`sys_file_reference`) or :php:`p` (`sys_file_processedfile`)
+ :php:`f` (*File*): Use it for a UID of table :sql:`sys_file`
+ :php:`r` (*Reference*): Use it for a UID of table :sql:`sys_file_reference`
+ :php:`p` (*Processed*): Use it for a UID of table :sql:`sys_file_processedfile`
+ :php:`s` (*Size*): Use it for a UID of table :sql:`sys_file_processedfile`
+ :php:`cv` (*CropVariant*): In case of `sys_file_reference`, you can assign it a cropping variant

You have to choose one of these parameters: :php:`f`, :php:`r` or :php:`p`. It is not possible
to use them multiple times in one request.

The Parameter :php:`s` has following syntax: width:height:minW:minH:maxW:maxH. You
can leave this Parameter empty to load the file in original size. Parameter :php:`width`
and :php:`height` can feature the trailing :typoscript:`c` or :typoscript:`m` indicator like known from TS.

See the following example on how to create a URI using the :php:`FileDumpController` for
a sys_file record with a fixed image size:

.. code-block:: php

   $queryParameterArray = ['eID' => 'dumpFile', 't' => 'f'];
   $queryParameterArray['f'] = $resourceObject->getUid();
   $queryParameterArray['s'] = '320c:280c';
   $queryParameterArray['token'] = GeneralUtility::hmac(implode('|', $queryParameterArray), 'resourceStorageDumpFile');
   $publicUrl = GeneralUtility::locationHeaderUrl(PathUtility::getAbsoluteWebPath(Environment::getPublicPath() . '/index.php'));
   $publicUrl .= '?' . http_build_query($queryParameterArray, '', '&', PHP_QUERY_RFC3986);


In this example crop variant :php:`default` and an image size of 320:280 will be
applied to a sys_file_reference record:

.. code-block:: php

   $queryParameterArray = ['eID' => 'dumpFile', 't' => 'r'];
   $queryParameterArray['f'] = $resourceObject->getUid();
   $queryParameterArray['s'] = '320c:280c:320:280:320:280';
   $queryParameterArray['cv'] = 'default';
   $queryParameterArray['token'] = GeneralUtility::hmac(implode('|', $queryParameterArray), 'resourceStorageDumpFile');
   $publicUrl = GeneralUtility::locationHeaderUrl(PathUtility::getAbsoluteWebPath(Environment::getPublicPath() . '/index.php'));
   $publicUrl .= '?' . http_build_query($queryParameterArray, '', '&', PHP_QUERY_RFC3986);


This example shows the usage how to create a URI to load an image of
sys_file_processedfile:

.. code-block:: php

   $queryParameterArray = ['eID' => 'dumpFile', 't' => 'p'];
   $queryParameterArray['p'] = $resourceObject->getUid();
   $queryParameterArray['token'] = GeneralUtility::hmac(implode('|', $queryParameterArray), 'resourceStorageDumpFile');
   $publicUrl = GeneralUtility::locationHeaderUrl(PathUtility::getAbsoluteWebPath(Environment::getPublicPath() . '/index.php'));
   $publicUrl .= '?' . http_build_query($queryParameterArray, '', '&', PHP_QUERY_RFC3986);


There are some restriction while using the new URI-Parameters:

+ You can't assign any size parameter to processed files, as they are already resized.
+ You can't apply CropVariants to `sys_file` and `sys_file_processedfile` records.


Impact
======

No impact, as this class was extended only. It's fully backwards compatible.

.. index:: FAL, ext:core
