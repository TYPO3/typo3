..  include:: /Includes.rst.txt

..  _breaking-111146-1792407218:

==================================================
Breaking: #111146 - Require PHP extension fileinfo
==================================================

See :issue:`111146`

Description
===========

The PHP extension :php:`fileinfo` is required by the Composer package
`typo3/cms-core` and by the Install Tool. Before, both only suggested it,
although the File Abstraction Layer detects the MIME type of files with it,
and the check that the MIME type of a written file matches its file extension
relies on that detection.

Impact
======

Composer refuses to install TYPO3 into a PHP environment without the
extension :php:`fileinfo`. The installation process, the Install Tool
"Environment Status" and the reports module report the extension as missing
instead of suggesting it.

Affected installations
======================

Installations whose PHP lacks the extension :php:`fileinfo`. PHP enables it by
default, it is only missing where PHP was built with `--disable-fileinfo`.

Migration
=========

Provide the extension in the PHP environment.

..  index:: PHP-API, NotScanned, ext:core, ext:install
