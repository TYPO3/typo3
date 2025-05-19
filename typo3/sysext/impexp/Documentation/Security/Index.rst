:navigation-title: Security

..  include:: /Includes.rst.txt
..  _security:

=========================================
Security considerations regarding exports
=========================================

Exported content in TYPO3 can contain sensitive or restricted information that
needs to be properly secured. This document outlines the recommended best
practices for managing the security risks associated with exporting content.
The following sections explain:

-   Why you should **disable the export extension when not in use** to reduce
    the risk of unintentional data exposure.
-   How to **prevent unauthorized access** by restricting the visibility of
    export options in the TYPO3 interface.
-   Why it is important to **secure the export directory** to block unauthorized
    file access on different webserver setups.
-   How to **report a security issue** to the TYPO3 Security Team if you
    identify vulnerabilities not addressed in this guide.

..  contents:: Table of contents

..  _security-disable-extension:

Disable the extension when not in use
=====================================

Exported content may contain sensitive and restricted information related to
your site. It is recommended that this extension be deactivated when it is not
in use to prevent content being exported in error.

..  _security-prevent-unauthorized-access:

Prevent unauthorized access
===========================

The export function is available by default for editors without admin rights.
It is limited to content to which the editor has access. The export
functionality can be hidden in the editor's context menu by using the user
TSconfig setting :ref:`contextMenu disableItems
<t3tsref:useroptions-contextMenu-key-disableItems>`.

Note that it cannot be completely disabled as there are currently other entry
points.

..  _security-secure-export-directory:

Secure the export directory
===========================

Exports are stored in :path:`fileadmin/user_upload/_temp_/importexport/`.
TYPO3 will automatically create a :file:`.htaccess` file to prevent access to
this folder from external sources. On Nginx webservers, the :file:`.htaccess`
file has no effect. Follow the :ref:`Security guidelines for System
Administrators <t3coreapi:security-administrators>` to find out how to prevent
access to specific directories on Nginx webservers.

..  _security-reporting-issue:

Reporting a security issue
==========================

If you believe you have found a security-related issue that is not listed
here, please contact the :ref:`TYPO3 Security Team <t3coreapi:security-team>`.
