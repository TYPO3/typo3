.. include:: ../../Includes.txt

===========================================================================
Feature: #92518 - Download and filename options added to FileDumpController
===========================================================================

See :issue:`92518`

Description
===========

The :php:`FileDumpController` has been extended with a `dl` and `fn` parameter.

* `dl`: Force download of the file
* `fn`: Use an alternative filename

See the following example on how to create a URI including the new parameters:

.. code-block:: php

   $queryParameterArray = [
      'eID' => 'dumpFile',
      't' => 'f',
      'f' => $resourceObject->getUid(),
      'dl' => true,
      'fn' => 'alternative-filename.jpg'
   ];
   $queryParameterArray['token'] = GeneralUtility::hmac(implode('|', $queryParameterArray), 'resourceStorageDumpFile');

   $publicUrl = GeneralUtility::locationHeaderUrl(PathUtility::getAbsoluteWebPath(Environment::getPublicPath() . '/index.php'));
   $publicUrl .= '?' . http_build_query($queryParameterArray, '', '&', PHP_QUERY_RFC3986);

This will create a URI from a sys_file record and triggers a download of the
file with the alternative filename, using the `Content-Disposition: attachment`
header.

To ease the use of the file dump functionality, also a new view helper
is added. See :doc:`FileViewHelper <../master/Feature-92518-IntroduceFileViewHelper.rst>`
for further information.

Impact
======

The `dumpFile` eID script is now capable of the `dl` parameter, forcing
the download of the corresponding file, as well as the `fn` parameter,
which can be used to define an alternative file name.

.. index:: FAL, ext:core
