.. include:: /Includes.rst.txt

====================================================================
Breaking: #92590 - Removed support for extension upload of t3x files
====================================================================

See :issue:`92590`

Description
===========

With the inception of the concept of Extensions, the Extension
Manager in TYPO3 and the TYPO3 Extension Repository (TER) on
https://extensions.typo3.org, the file format `t3x` ("TYPO3 eXtension")
was created.

The proprietary format was introduced because the lack of support
for zip handling in PHP4 in 2004. However, the format was proven
to be cumbersome for developers and zip was bundled with most PHP5 versions.

For this reason, the TYPO3 Ecosystem started to support extensions as regular
`zip` archives during TYPO3 v6 development.

The zip format for extension downloading and uploading was used more and more
in favor of the `t3x` data format, so today the TER only offers the download of
`zip` files via the Web GUI.

However, TYPO3's Extension Manager still supported uploading
`.t3x` files even though files were not created by the Extension Manager
anymore since TYPO3 v6 - downloading an extension via the Extension Manager only
created an archive of the `.zip` format of the extension.

The feature of uploading files with a `t3x` format (identified by the
file extension `.t3x`) has been removed.

Both TER and the Extension Manager for downloading extensions still support `t3x`
under the hood for legacy reasons, but this is not exposed to end-users,
integrators or developers anymore.


Impact
======

Uploading a `t3x`-based extension file in the Extension Manager will result in
an error message.


Affected Installations
======================

TYPO3 installations where administrators still handle `t3x` files for uploading
extensions, which is highly unlikely and only applies for
TYPO3 installations not installed via Composer.


Migration
=========

When using a public extension, it is recommended to download the
`zip` variant from https://extensions.typo3.org.

When a `.t3x` file is provided by a third party, it is possible to upload the
extension in the Extension Manager of an older TYPO3 Core version
(e.g. TYPO3 v10), and then download the extension there as a `.zip` file.

.. index:: Backend, NotScanned, ext:extensionmanager
