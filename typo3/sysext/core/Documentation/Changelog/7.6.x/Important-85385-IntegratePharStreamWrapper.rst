.. include:: ../../Includes.txt

=================================================
Important: #85385 - Integrate Phar Stream Wrapper
=================================================

See :issue:`85385`

Description
===========

In order to solve the issues mentioned in the `security advisory TYPO3-SA-2018-002`_
a new `PharStreamWrapper` has been integrated that intercepts all according stream actions using the `phar://` stream prefix.

`PharStreamWrapper` only allows invocation of Phar files that are located in the usual extension directory located in
`typo3conf/ext/` - Phar files stored at different locations cannot be invoked anymore.

When using Phar files in extensions PHP's `__DIR__` magic constant has to be avoided
and replaced by according TYPO3 file resolving instead. This is required in order to
allow extensions being referenced using symbolic links - when `__DIR__` points to
the source which is probably outside of `typo3conf/ext/` and thus denies the expected
Phar file invocation.

.. code-block:: php

   // ...
   include_once 'phar://' . __DIR__ . '/Resources/bundle.phar/vendor/autoload.php';
   // ...

has to be adjusted to the following instead, using `ExtensionManagementUtility::extPath()` in order to resolve the proper path

.. code-block:: php

   // ...
   include_once 'phar://' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('my_extension')
     . '/Resources/bundle.phar/vendor/autoload.php';
   // ...

.. _security advisory TYPO3-SA-2018-002: https://typo3.org/security/advisory/typo3-core-sa-2018-002/


.. index:: PHP-API, ext:core
