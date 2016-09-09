
.. include:: ../../Includes.txt

=========================================================================================
Feature: #68315 - Include a pageTSconfig file in page properties like TS static templates
=========================================================================================

See :issue:`68315`

Description
===========

In the Page properties an option is added to include a page TSconfig file (the same way as TypoScript static templates are included).
The included files from the pages in the rootline are included after the default page TSconfig and before the normal TSconfig
from the pages in the rootline.
To add files to the selector in the Page properties a new function `registerPageTSConfigFile` is added to
`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility` to register a pageTSconfig file.


Impact
======

No effect on existing installations.

Usage
=====

In `Configuration/TCA/Overrides/pages.php` of any extension, register PageTS config files, which will be shown afterwards at the newly introduced field.

.. code-block:: php

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile('extension_name', 'Configuration/PageTS/myPageTSconfigFile.txt', 'My special config');
