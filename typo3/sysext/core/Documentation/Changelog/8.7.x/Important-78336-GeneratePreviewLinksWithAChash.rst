.. include:: ../../Includes.txt

=======================================================
Important: #78336 - Generate preview links with a chash
=======================================================

See :issue:`78336`

Description
===========

The preview link configuration has been extended to be able to generate links with a cHash.

Provide the setting `useCacheHash = 1` to add a cHash. This is essential for records displayed
using Extbase which enforces cHash usage.

.. code-block:: typoscript

	TCEMAIN.preview {
		<table name> {
			previewPageId = 123
			useCacheHash = 1
			fieldToParameterMap {
				uid = tx_myext_pi1[showUid]
			}
			additionalGetParameters {
				tx_myext_pi1[special] = HELLO
			}
		}
	}

If `useCacheHash = 1` is not set, the `no_cache` parameter will be added just like before.

.. index:: Backend, Frontend, TSConfig
