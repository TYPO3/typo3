
.. include:: ../../Includes.txt

======================================================
Breaking: #71110 - TYPO3-specific Upload Limit removed
======================================================

See :issue:`71110`

Description
===========

TYPO3 has a specific upload limit setting, that is set to 10MB by default, to manually limit down the PHP-specific
setting `max_upload_limit`. If not configured properly the PHP limit was lower than the TYPO3-specific limit.

The TYPO3 setting `$TYPO3_CONF_VARS['BE']['maxFileSize']` has been removed and the PHP-internal limit is now the
upper barrier.


Impact
======

Setting the option mentioned above has no effect anymore. The PHP limit is used instead.

The TCA setting `max_size` for `fe_users.image` has been removed, allowing editors to upload images with a size
up to the PHP-specific limit.


Affected Installations
======================

Extensions that use `$GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize']` as default for the TCA setting `max_size`
need adjustment, if the PHP-specific upload limit is higher than `$TYPO3_CONF_VARS['BE']['maxFileSize']`.


Migration
=========

Explicitly set a value for `max_size` or drop those lines from your TCA configuration.
