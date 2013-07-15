.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _administration:

Administration
--------------

The extension was originally developed for TYPO3 4.3. It might work
with older versions but the TYPO3 core team does not provide any
support for RSA authentication in older TYPO3 versions. The extension
is kept up to date for newer versions of TYPO3.


.. _requirements:

Requirements
^^^^^^^^^^^^

The extension requires either an :code:`openssl` PHP module or the
:code:`openssl` binary to be available to TYPO3. The extension will
choose the first working solution with the preference being the PHP
module. If neither PHP module, nor binary is available, the extension
will refuse to continue and silently fail authentications.

.. _installation:

Installation
^^^^^^^^^^^^

To install the extension, install it using the TYPO3 Extension
Manager. Create necessary database tables and provide a path to the
temporary directory. This path is necessary only if the extension uses
the :code:`openssl` binary. The path should be outside of the web
server root and it should not be accessible to anyone except the web
server user. If using PHP :code:`open\_basedir` directive, make sure
that this path is included into this directive (with the slash at the
end of the path).


.. _activation:

Activation
^^^^^^^^^^

The extension supports both Frontend and Backend authentication using
public/private key pair. Both Frontend and Backend authentication must
be activated separately in order to work.

To activate the extension for Frontend and/or Backend, use the TYPO3
Install tool. Login to the Install tool and select the :code:`All
configuration` option. Next search for the
:code:`[FE][loginSecurityLevel]` setting for the Frontend or for the
:code:`[BE][loginSecurityLevel]` setting for the Backend
authentication method. Enter :code:`rsa` without spaces in the setting
box. New settings will be activated immediately after saving. The next
user will use RSA authentication for the Frontend and/or Backend.


.. _frontend-authentication:

Frontend authentication
^^^^^^^^^^^^^^^^^^^^^^^

Frontend RSA authentication is supported only for the :code:`felogin`
system extension. It will not work with the old login form.

Since a new key pair is generated each time for the authentication, it
is necessary to run the :code:`felogin` plugin uncached. To do so, the
plugin should be run in :code:`USER\_INT` mode. This can be
accomplished by the following piece of the TypoScript:

::

   plugin.tx_felogin_pi1 = USER_INT

The RSA authentication will not work if the :code:`felogin` plugin
runs as :code:`USER` (which **is** cached).

