======================================================
Breaking: #71110 - TYPO3-specific Upload Limit removed
======================================================

Description
===========

TYPO3 has a specific upload limit setting, that is set to 10MB by default, to manually limit down the PHP-specific
setting ``max_upload_limit``. If configured wrongly the PHP limit was lower than the TYPO3-specific limit.

The TYPO3 setting ``$TYPO3_CONF_VARS['BE']['maxFileSize']`` is removed and the PHP-internal limit is now the
upper barrier.


Impact
======

Setting the option mentioned above has no effect anymore. The PHP limit is used instead.