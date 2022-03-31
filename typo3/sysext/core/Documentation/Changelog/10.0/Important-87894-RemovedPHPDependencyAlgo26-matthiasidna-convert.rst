.. include:: /Includes.rst.txt

=======================================================================
Important: #87894 - Removed PHP dependency algo26-matthias/idna-convert
=======================================================================

See :issue:`87894`

Description
===========

PHP has native functions for converting UTF-8 based domains to ascii-based ("punicode"), which
can be used directly when the PHP extension "intl" is installed. For servers with PHP packages which
do not have the PHP extension "intl" installed, the symfony polyfill package "symfony/polyfill-intl-idn"
is available, allowing to use native PHP functionality in the TYPO3 code base.

For this reason the PHP dependency "algo26-matthias/idna-convert" is no longer necessary and
has been removed.

.. index:: PHP-API, ext:core
