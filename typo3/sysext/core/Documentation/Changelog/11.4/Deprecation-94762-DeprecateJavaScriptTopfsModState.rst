.. include:: /Includes.rst.txt

==========================================================
Deprecation: #94762 - Deprecate JavaScript top.fsMod state
==========================================================

See :issue:`94762`

Description
===========

The JavaScript object :js:`top.fsMod` manages the "state" for page-tree and
file-tree related contexts in the backend user-interface like this:

* :js:`top.fsMod.recentIds.web` contained the current ("recent")
  page or file related identifier details were shown for
* :js:`top.fsMod.navFrameHighlightedID.web` contained the currently
  selected identifier that was highlighted in page-tree or file-tree
* :js:`top.fsMod.currentBank` contained the current mount point or
  file mount ("bank") used in page-tree or file-tree

To get rid of inline JavaScript and reduce usage of JavaScript :js:`top.*`,
mentioned :js:`top.fsMod` has been marked as deprecated and replaced by new component
:js:`TYPO3/CMS/Backend/Storage/ModuleStateStorage`.

Impact
======

As fall-back, reading from :js:`top.fsMod` is still possible, changing
data will cause a JavaScript exception.

Affected Installations
======================

Sites using custom modifications for JavaScript aspects in the backend user
interface relying on :js:`top.fsMod`.

Migration
=========

New :js:`ModuleStorage` component is capable of providing similar behavior,
corresponding state is written to `sessionStorage` and available for current
client user session (per browser tab).

.. code-block:: javascript

   import {ModuleStateStorage} from '../Storage/ModuleStateStorage';
   let identifier: string, selection: string|null, mount: string|null;

   // reading state
   // -------------

   const currentState = ModuleStateStorage.current('web');

   identifier = top.fsMod.recentIds.web; // deprecated
   identifier = currentState.identifier; // replacement

   selection = top.fsMod.navFrameHighlightedID.web; // deprecated
   selection = currentState.selection; // replacement

   mount = top.fsMod.currentBank; // deprecated
   mount = currentState.mount; // replacement

   // updating state
   // --------------

   // ModuleStateStorage.update(module, identifier, selected, mount?)
   ModuleStateStorage.update('web', 123, true, '0');

   // ModuleStateStorage.updateWithCurrentMount(module, identifier, selected)
   ModuleStateStorage.updateWithCurrentMount('web', 123, true);


.. index:: Backend, JavaScript, NotScanned, ext:backend
