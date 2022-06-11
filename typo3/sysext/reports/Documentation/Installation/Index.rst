.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

The system extension is not part of common Composer distributions such as
`typo3/cms-base-distribution <https://packagist.org/packages/typo3/cms-base-distribution>`_.

Therefore, if you installed TYPO3 following :ref:`t3start:install`, EXT:reports
is not automatically installed.

You can install it via:

.. code-block:: bash

    composer require typo3/cms-reports

Legacy installations
====================

For legacy installations that do not use Composer, EXT:reports is already
part of the distributed package.

Activate the :guilabel:`Reports`
extension in :guilabel:`Admin Tools > Extension Manager`.

