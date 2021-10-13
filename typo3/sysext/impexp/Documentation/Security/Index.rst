.. include:: /Includes.rst.txt

.. _security:

Security
========

Disable the extension when not in use
-------------------------------------

Exported content may contain sensitive and restricted information related to
your site. It is recommended that this extension be deactivated when it is not
in use to prevent content being exported in error.


Prevent unauthorized access
---------------------------

The export function is available by default for editors without admin
rights. It is limited to content to which the editor has access. The export
functionality can be hidden in the editor's context menu by the user TsConfig
:ref:`contextMenu disableItems <t3tsconfig:useroptions-contextMenu-key-disableItems>`.

Note that it cannot be completely disabled as there are currently other entry
points.


Secure the export directory
---------------------------

Exports are stored in :file:`fileadmin/user_upload/_temp_/importexport/`.
TYPO3 will automatically create a :file:`.htaccess` file to prevent access to
this folder from external sources. On Nginx webservers the :file:`.htaccess`
file has no effect. Follow the :ref:`Security guidelines for System
Administrators <t3coreapi:security-administrators>` to find out how to prevent
access to specific directories on Nginx webservers.


Reporting a security issue
--------------------------

If you believe you have found a security related issue that is not listed here,
please contact the :ref:`TYPO3 Security Team<t3coreapi:security-team>`.
