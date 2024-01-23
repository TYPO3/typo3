.. include:: /Includes.rst.txt

.. _feature-102836-1705994823:

===================================================================
Feature: #102836 - Allow deleting IRRE elements via `postMessage()`
===================================================================

See :issue:`102836`

Description
===========

To invoke a deletion on items in FormEngine's Inline Relation container
API-wise, a new message identifier :js:`typo3:foreignRelation:delete` has been
introduced.

Example usage:

..  code-block:: js

    import { MessageUtility } from '@typo3/backend/utility/message-utility.js';

    MessageUtility.send({
        actionName: 'typo3:foreignRelation:delete',
        objectGroup: 'data-<page_id>-<parent_table>-<parent_uid>-<reference_table>',
        uid: '<reference_uid>'
    });


Impact
======

Extension developers are now able to trigger the deletion of IRRE elements via
API.

.. index:: Backend, JavaScript, ext:backend
