.. include:: /Includes.rst.txt

.. _security:

Security
========

Disable the extension when not in use
-------------------------------------

Exported content may contain sensitive and restricted information related to your site. It is
recommended that this extension be deactivated when it is not in use to prevent
content being exported in error.


Prevent unauthorized access
---------------------------

Export functionality is available to editors with
non-admin rights by default. It is limited to content the editor has
access to. Export functionality can also be disabled by user TsConfig
:ref:`contextMenu disableItems <t3tsconfig:useroptions-contextMenu-key-disableItems>`


Secure the export directory
---------------------------

Exports are stored in the
:file:`public/fileadmin/user_upload/_temp_/importexport`. TYPO3 will
automatically create a :file:`.htaccess` file to prevent access to this folder from external
sources. On nginx-based servers the :file:`.htaccess` file has no effect.
Follow the :ref:`Security guidelines for System Administrators
<t3coreapi:security-administrators>` to find out how to prevent access to specific directories
on NGINX based web servers.
