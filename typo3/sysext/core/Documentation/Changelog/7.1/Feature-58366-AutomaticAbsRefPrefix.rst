
.. include:: ../../Includes.txt

===========================================================
Feature: #58366 - Add "auto" Option for config.absRefPrefix
===========================================================

See :issue:`58366`

Description
===========

The TypoScript setting `config.absRefPrefix` can be used to allow URL rewriting like giving a hard
prefix for all relative paths. As an alternative to `config.baseURL` to be set to a specific domain
absRefPrefix can autodetect the site root and use that instead of manually setting this option.

Frontend:

The new option can be set like this:

.. code-block:: typoscript

	config.absRefPrefix = auto

instead of hardcoded values for different environments or when moving installations in subfolders.

.. code-block:: typoscript

	[ApplicationContext = Production]
	config.absRefPrefix = /

	[ApplicationContext = Testing]
	config.absRefPrefix = /my_site_root/

As the feature only works with path prefixes, and not with host name variables from the server,
the new option is also safe for multi-domain environments to avoid duplicate caching mechanism.


Impact
======

The new special option can be used to automatically set up installations and distributions like
the Introduction Package where a site configuration is shipped with the system but might need
to be adjusted.
