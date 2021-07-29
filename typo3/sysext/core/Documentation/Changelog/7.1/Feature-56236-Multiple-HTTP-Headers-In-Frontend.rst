
.. include:: ../../Includes.txt

===========================================================================
Feature: #56236 - Multiple HTTP headers of the same type in Frontend Output
===========================================================================

See :issue:`56236`

Description
===========

It is now possible to use `config.additionalHeaders` as a TypoScript array object to add multiple headers
at the same time

Usage:

.. code-block:: typoscript

	config.additionalHeaders {
		10 {
			# the header string
			header = WWW-Authenticate: Negotiate

			# replace previous headers with the same name
            # optional, default is "on"
			replace = 0

			# optional, force the HTTP response code
			httpResponseCode = 401
		}
		# always set cache headers to private, overwriting the sophisticated TYPO3 option
		20.header = Cache-control: Private
	}

See also: https://php.net/header

Impact
======

The previous option `config.additionalHeaders = X-Header: ABC|X-Header2: DEF` is deprecated in favor of the more
flexible solution.


.. index:: TypoScript, Frontend
