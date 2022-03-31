.. include:: /Includes.rst.txt

========================================================
Breaking: #93083 - class.ext_update.php handling removed
========================================================

See :issue:`93083`

Description
===========

Handling of old :file:`class.ext_update.php` update scripts has been
dropped: The core introduced a much more solid API for extensions to
perform upgrades with the release of TYPO3 v9. That API matured
and many extensions use it in favor of the clumsy
:file:`class.ext_update.php` solution. Removal of this functionality
within the extension manager has been long overdue and is finally done
with TYPO3 v11.


Impact
======

The :file:`class.ext_update.php` was an old way for extensions to
perform upgrade steps. The TYPO3 core no longer supports this API.


Affected Installations
======================

Some old-style extensions may still rely on this script. It's usage
has been discouraged since the new upgrade wizards API.


Migration
=========

Migrate :file:`class.ext_update.php` to the :ref:`upgrade wizard API of the
Install Tool <t3coreapi:upgrade-wizards>`.


.. index:: PHP-API, NotScanned, ext:extensionmanager
