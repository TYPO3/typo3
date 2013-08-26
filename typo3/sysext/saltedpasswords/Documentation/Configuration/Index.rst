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

   # Enable FE (boolean)
   FE.enabled = 1

Enables usage of salted user password records for the TYPO3 frontend

::

   # Hashing method for the frontend (list)
   FE.saltedPWHashingMethod = tx_saltedpasswords_salts_phpass (Portable PHP password hashing)

Defines hashing method to use for TYPO3 frontend.

::

   # Hashing method for the backend (list)
   BE.saltedPWHashingMethod = tx_saltedpasswords_salts_phpass (Portable PHP password hashing)

Defines hashing method to use for TYPO3 backend.


.. _advanced-frontend-configuration:

Advanced frontend configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

::

   # Force salted passwords (boolean)
   FE.forceSalted = 0

Enforces usage of salted user password hashes only. Any other type of
stored password will result in a failed authentication.

::

   # Exclusive FE usage (boolean)
   FE.onlyAuthService = 0

If enabled and authentication fails, no further authentication service
will be tried.

::

   # Update FE user passwords (boolean)
   FE.updatePasswd = 1

Uses existing FE user passwords but automatically convert them to the
salted hash format during authentication (will not work if forceSalted
is enabled).


.. _advanced-backend-configuration:

Advanced backend configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

::

   # Force salted passwords (boolean)
   BE.forceSalted = 0

Enforces usage of salted user password hashes only. Any other type of
stored password will result in a failed authentication.

::

   # Exclusive BE usage (boolean)
   BE.onlyAuthService = 0

If enabled and authentication fails, no further authentication service
will be tried.

::

   # Update BE user passwords (boolean)
   BE.updatePasswd = 1

Uses existing BE user passwords but automatically convert them to the
salted hash format during authentication (will not work if forceSalted
is enabled).

