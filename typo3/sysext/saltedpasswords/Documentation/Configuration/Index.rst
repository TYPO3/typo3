.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _configuration:

Configuration
-------------

All extension configuration settings are divided into three
categories: basic, advanced frontend, advanced backendPlease use the
category drop-down box in the extension configuration to switch
between these categories!


.. _basic-configuration:

Basic configuration
^^^^^^^^^^^^^^^^^^^

::

   # Hashing method for the frontend (list)
   FE.saltedPWHashingMethod = tx_saltedpasswords_salts_phpass (Portable PHP password hashing)

Defines hashing method to use for TYPO3 frontend.

::

   # Hashing method for the backend (list)
   BE.saltedPWHashingMethod = tx_saltedpasswords_salts_phpass (Portable PHP password hashing)

Defines hashing method to use for TYPO3 backend.
