.. include:: /Includes.rst.txt

===========================================================================
Feature: #92518 - Download and filename options added to FileDumpController
===========================================================================

See :issue:`92518`

Description
===========

The :php:`\TYPO3\CMS\Core\Controller\FileDumpController` has been extended with
the parameters :php:`dl` and :php:`fn`.

*  :php:`dl`: Force download of the file
*  :php:`fn`: Use an alternative filename

See the following example on how to create a URI including the new parameters:

.. code-block:: php

   // use TYPO3\CMS\Core\Utility\GeneralUtility;
   // use TYPO3\CMS\Core\Utility\PathUtility;
   // use TYPO3\CMS\Core\Core\Environment;
   $queryParameterArray = [
      'eID' => 'dumpFile',
      't' => 'f',
      'f' => $resourceObject->getUid(),
      'dl' => true,
      'fn' => 'alternative-filename.jpg'
   ];
   $queryParameterArray['token'] =
      GeneralUtility::hmac(
         implode('|', $queryParameterArray),
         'resourceStorageDumpFile'
      );

   $publicUrl =
      GeneralUtility::locationHeaderUrl(
         PathUtility::getAbsoluteWebPath(Environment::getPublicPath() . '/index.php')
      );
   $publicUrl .= '?' . http_build_query($queryParameterArray, '', '&', PHP_QUERY_RFC3986);

This will create a URI from a :sql:`sys_file` record and trigger a download of the
file with the alternative filename, using the :html:`Content-Disposition: attachment`
header.

To ease the use of the file dump functionality, also a new ViewHelper
is added. See :doc:`FileViewHelper <../11.3/Feature-92518-IntroduceFileViewHelper>`
for further information.

Impact
======

The `dumpFile` eID script is now capable of the `dl` parameter, forcing
the download of the corresponding file, as well as the `fn` parameter,
which can be used to define an alternative file name.

.. index:: FAL, ext:core
