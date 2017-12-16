
.. include:: ../../Includes.txt

=============================================================
Feature: #69764 - Introduced file icon detection by mime-type
=============================================================

See :issue:`69764`

Description
===========

The IconRegistry has been extended with a mapping of file icons by mime-type.
It is possible to register full mime-types `main-type/sub-type` but also a
fallback for only the main part of the mime-type `main-type/*`.
The core provides these fallbacks for `audio/*`, `video/*`, `image/*` and `text/*`.


Impact
======

It is now possible to register or overwrite the iconIdentifier for a file mime-type.

.. code-block:: php

	$iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
	$iconRegistry->registerMimeTypeIcon('video/my-custom-type', 'icon-identifier-for-my-custom-type');
