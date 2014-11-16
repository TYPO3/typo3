=======================================================
Breaking: #64059 - Rewritten Javascript Tree Components
=======================================================

Description
===========

In the process of refactoring prototype/scriptaculous code and migrate to an AMD module, the tree component and its
drag&drop parts are migrated to a RequireJS / jQuery module.

The page tree filter functionality of the prototype tree, which is not used in the TYPO3 core, was removed from the
tree component.

The file typo3/js/tree.js was removed, the replacement code, based on jQuery is located under
EXT:backend/Resources/Public/JavaScript/LegacyTree.js.


Impact
======

Any usages in third party extensions that include js/tree.js will fail, as the tree component was removed. Any
extension using the filter part of the tree.js component will not work.


Affected installations
======================

Any installation with its own backend module using the tree component from the core.


Migration
=========

Rewrite any needed logic for filtering, and include the RequireJS module like in e.g.
FileSystemNavigationFrameController.php, to use the tree component. If the old code is needed, the tree.js file
and prototype need to be included as part of the extension, not from the core.
