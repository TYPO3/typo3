.. include:: /Includes.rst.txt

.. _deprecation-98168-1660890228:

=========================================================
Deprecation: #98168 - Binding context menu item to `this`
=========================================================

See :issue:`98168`

Description
===========

Due to historical reasons, a context menu item is bound to :js:`this` in its callback action
which was used to access the context menu item's :js:`dataset`. The invocation of assigned callback actions
is adapted to pass the :js:`dataset` as the 3rd argument.

Binding the context menu item to :js:`this` in the callback is now marked as deprecated.

Impact
======

Using :js:`this` in a context menu item callback will trigger a deprecated log entry in the browser's console.

Affected installations
======================

All extensions providing custom context menu actions are affected.

Migration
=========

To access data attributes, use the :js:`dataset` argument passed as the 3rd argument in the context menu callback action.

..  code-block:: js

    // Before
    ContextMenuActions.renameFile(table, uid): void {
      const actionUrl = $(this).data('action-url');
      top.TYPO3.Backend.ContentContainer.setUrl(
        actionUrl + '&target=' + encodeURIComponent(uid) + '&returnUrl=' + ContextMenuActions.getReturnUrl()
      );
    }

    // After
    ContextMenuActions.renameFile(table, uid, dataset): void {
      const actionUrl = dataset.actionUrl;
      top.TYPO3.Backend.ContentContainer.setUrl(
        actionUrl + '&target=' + encodeURIComponent(uid) + '&returnUrl=' + ContextMenuActions.getReturnUrl()
      );
    }

.. index:: Backend, JavaScript, NotScanned, ext:backend
