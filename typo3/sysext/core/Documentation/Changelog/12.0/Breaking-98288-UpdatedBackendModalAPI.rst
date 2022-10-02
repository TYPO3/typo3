.. include:: /Includes.rst.txt

.. _breaking-98288-1662580832:

============================================
Breaking: #98288 - Updated Backend Modal API
============================================

See :issue:`98288`

Description
===========

The modal API provided by the module :js:`@typo3/backend/modal.js` has been
adapted to backed by a custom web component and therefore gained an updated,
stateless interface.

The return type of all :js:`Modal.*` factory methods has been changed from
:js:`JQuery` to :js:`ModalElement`.

:js:`ModalElement` is a web component which allows to attach modal API
(like modal hiding) directly to the returned object. Usage of globals like
`Modal.currentModal` can thus be avoided when using the returned
:js:`ModalElement`.

This affects the following methods which now return :js:`ModalElement`:

* :js:`Modal.confirm()`
* :js:`Modal.loadUrl()`
* :js:`Modal.show()`
* :js:`Modal.advanced()`
* :js:`Modal.setButtons()`
* :js:`Modal.generate()`

Furthermore the following changes have been applied:

* The :js:`Button` property `dataAttributes` has been removed without
  replacement, as the functionality can be expressed via :js:`Button.name`
  or :js:`Button.trigger` and is therefore redundant.

* The :js:`ajaxTarget` of the modal :js:`Configuration` object has been
  dropped, as it was never actually used in TYPO3. Use nested, custom
  web components for dynamic ajax loading of modal sub areas.

* The rendering life cycle has been adapted to synchronize rendering to
  the browsers idle callback. That means rendering is delayed and modal content
  can not be modified directly after modal creation.
  The existing API :js:`Configuration.callback` has to be used instead, but
  usage of lit :js:`TemplateResult` without the need for post-processing is
  suggested to be used instead.

* The :js:`bs.modal.*` events are no longer considered API, but remain working
  for the time being (as bootstrap modal is still used right now).
  These events may be dropped at any time, when the modal component is switched
  to shadow dom, or the native `<dialog>` tag.
  Therefore :js:`typo3-modal-*` events are to be used instead.

* The event :js:`modal-destroyed` has been removed.
  Use :js:`typo3-modal-hide` or :js:`typo3-modal-hidden` instead.

* :js:`Modal.currentModal.trigger('modal-dismiss')` has been removed.
  Use :js:`ModalElement.hideModal()` instead.

Impact
======

Using jQuery API on :js:`ModalElement` will lead to JavaScript errors as
no jQuery interop is provided.

Affected installations
======================

All 3rd party extensions using the API of the :js:`@typo3/backend/modal.js`
module are affected, if they use the return type of the methods to attach
to events or to customize the modal after creations.

Migration
=========

Given the following fully-fledged example of a modal that uses custom buttons,
with custom attributes, triggers and events, they should be migrated away
from :js:`JQuery` to :js:`ModalElement` usage.

Existing code:

..  code-block:: javascript

    var configuration = {
       buttons: [
          {
             text: 'Save changes',
             name: 'save',
             icon: 'actions-document-save',
             active: true,
             btnClass: 'btn-primary',
             dataAttributes: {
                action: 'save'
             },
             trigger: function() {
                Modal.currentModal.trigger('modal-dismiss');
             }
          }
       ]
    };
    Modal
      .advanced(configuration)
      .on('hidden.bs.modal', function() {
        // do something
    });

Should be adapted to:

..  code-block:: javascript

    const modal = Modal.advanced({
       buttons: [
          {
             text: 'Save changes',
             name: 'save',
             icon: 'actions-document-save',
             active: true,
             btnClass: 'btn-primary',
             trigger: function(event, modal) {
               modal.hideModal();
             }
          }
       ]
    });
    modal.addEventListener('typo3-modal-hidden', function() {
      // do something
    });

.. index:: Backend, JavaScript, NotScanned, ext:backend
