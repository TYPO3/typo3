.. include:: /Includes.rst.txt

=================================================================
Breaking: #91974 - Configuration Option IPmaskMountGroups removed
=================================================================

See :issue:`91974`

Description
===========

The global configuration option :php:`$GLOBALS['TYPO3_CONF_VARS']['FE'][IPmaskMountGroups]` has been removed. It allowed to automatically assign
groups to users visiting the TYPO3 Frontend from specific IP addresses / networks.

This is especially handy to show content only in Intranet/Extranet
sites where internal members see restricted content automatically.

However, showing content based on certain contexts is usually solved with a much more flexible way through third-party extensions
such as EXT:contexts. Third-party extensions allow even for automatic login based on IP-addresses, which should be used instead.


Impact
======

The mentioned option is automatically removed from :file:`LocalConfiguration.php`
on upgrade, and not evaluated anymore.


Affected Installations
======================

Installations having the global configuration setting set in
:file:`typo3conf/LocalConfiguration.php` or :file:`typo3conf/AdditionalConfiguration.php`, mostly related to intranet / extranet websites.


Migration
=========

If this functionality explicitly is required, it can be provided by a third-party extension, or a custom extension registering
a AuthenticationService ("getGroupsFE") to assign the groups on a more specific approach.

.. index:: LocalConfiguration, FullyScanned, ext:frontend
