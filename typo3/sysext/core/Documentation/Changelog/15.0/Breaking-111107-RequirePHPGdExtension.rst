..  include:: /Includes.rst.txt

..  _breaking-111107-1792227470:

===========================================
Breaking: #111107 - Require PHP extension gd
===========================================

See :issue:`111107`

Description
===========

The PHP extension :php:`gd` is required by the Composer package
`typo3/cms-core`. Before, the package only suggested it, although the
Install Tool has listed the extension as required for a long time and
GIFBUILDER draws with it.

Impact
======

Composer refuses to install TYPO3 into a PHP environment without the
extension :php:`gd`.

Affected installations
======================

Composer based installations whose PHP lacks the extension :php:`gd`. The
installation process, the Install Tool "Environment Status" and the reports
module already report it as missing in these installations.

Migration
=========

Provide the extension in the PHP environment. On a Debian or Ubuntu based
Linux host, this is typically done with a command similar to this:

..  code-block:: bash

    sudo apt install php8.5-gd

..  index:: PHP-API, NotScanned, ext:core
