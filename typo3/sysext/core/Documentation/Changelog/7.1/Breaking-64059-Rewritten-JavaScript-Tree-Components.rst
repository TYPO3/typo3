
.. include:: /Includes.rst.txt

=======================================================
Breaking: #64059 - Rewritten Javascript Tree Components
=======================================================

See :issue:`64059`

Description
===========

In the process of refactoring prototype/scripta.culo.us code and migrate these to an AMD module, the tree component and its
drag&drop parts have been migrated to a RequireJS / jQuery module.

The page tree filter functionality of the prototype tree, which is not used in the TYPO3 core, was removed from the
tree component.

The file :file:`typo3/js/tree.js` has been removed, the replacement code, based on jQuery is located under
EXT:backend/Resources/Public/JavaScript/LegacyTree.js.


Impact
======

Any usages in third party extensions that include :file:`js/tree.js` will fail, as the tree component has been removed.
Any extension using the filter part of the tree.js component will not work.


Affected installations
======================

Any installation with its own backend module using the tree component from the core.


Migration
=========

Rewrite any needed logic for filtering, and include the RequireJS module like in e.g.
FileSystemNavigationFrameController.php, to use the tree component. If the old code is needed, the :file:`tree.js` file
and prototype need to be included as part of the extension, not from the core.


.. index:: JavaScript, Backend
