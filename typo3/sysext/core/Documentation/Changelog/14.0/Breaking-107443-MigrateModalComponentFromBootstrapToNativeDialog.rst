..  include:: /Includes.rst.txt

..  _breaking-107443-1761040245:

===========================================================================
Breaking: #107443 - Migrate Modal component from Bootstrap to native dialog
===========================================================================

See :issue:`107443`

Description
===========

The TYPO3 Modal component has been migrated from Bootstrap's modal
implementation to use the native HTML :html:`<dialog>` element. This improves
accessibility, reduces bundle size, and removes the dependency on Bootstrap's
JavaScript for modal functionality.

As part of this migration, Bootstrap's native modal events (such as
:js:`show.bs.modal`, :js:`shown.bs.modal`, :js:`hide.bs.modal`, and
:js:`hidden.bs.modal`) are no longer dispatched.

Likewise, direct Bootstrap modal API usage (for example :js:`new Modal()` from
Bootstrap or :js:`$(element).modal()`) is no longer supported.

Impact
======

Bootstrap modal events (:js:`*.bs.modal`) are no longer available.

Extensions listening to these events must migrate to TYPO3's custom modal
events.

The Modal component now uses the native :html:`<dialog>` element with updated
CSS classes and DOM structure.

Any direct manipulation of Bootstrap modal APIs will no longer work.

Extensions using :js:`data-bs-toggle="modal"`, :js:`data-bs-content="..."`, or
:js:`data-bs-target` attributes to trigger modals must migrate to TYPO3's
Modal API.

Affected installations
======================

All installations with custom extensions that:

- Listen to Bootstrap modal events (:js:`show.bs.modal`, :js:`shown.bs.modal`,
  :js:`hide.bs.modal`, :js:`hidden.bs.modal`)
- Use Bootstrap's modal JavaScript API directly
  (for example :js:`new bootstrap.Modal()`)
- Use jQuery to control modals (for example :js:`$(element).modal('show')`)
- Use :js:`data-bs-toggle="modal"` or :js:`data-bs-content="..."` attributes
- Manipulate modal DOM structures or classes expecting Bootstrap markup

Migration
=========

Event migration
---------------

Replace Bootstrap modal event listeners with TYPO3's custom modal events.
Event listeners must be attached to the modal instance returned by the Modal
API, not queried from the DOM.

**Before:**

..  code-block:: javascript

    const modalElement = document.querySelector('.modal');
    modalElement.addEventListener('show.bs.modal', (event) => {
      console.log('Modal is about to be shown');
    });
    modalElement.addEventListener('shown.bs.modal', (event) => {
      console.log('Modal is now visible');
    });
    modalElement.addEventListener('hide.bs.modal', (event) => {
      console.log('Modal is about to be hidden');
    });
    modalElement.addEventListener('hidden.bs.modal', (event) => {
      console.log('Modal is now hidden');
    });

**After:**

..  code-block:: javascript

    import Modal from '@typo3/backend/modal';
    import Severity from '@typo3/backend/severity';

    const modal = Modal.show(
      'My Modal Title',
      'This is the modal content',
      Severity.info
    );

    modal.addEventListener('typo3-modal-show', (event) => {
      console.log('Modal is about to be shown');
    });
    modal.addEventListener('typo3-modal-shown', (event) => {
      console.log('Modal is now visible');
    });
    modal.addEventListener('typo3-modal-hide', (event) => {
      console.log('Modal is about to be hidden');
    });
    modal.addEventListener('typo3-modal-hidden', (event) => {
      console.log('Modal is now hidden');
    });

Bootstrap API migration
-----------------------

Replace Bootstrap modal API calls with TYPO3's Modal API.

Do not instantiate Bootstrap modals or manipulate modal DOM elements directly.

**Before:**

..  code-block:: javascript

    import { Modal } from 'bootstrap';

    const modalElement = document.querySelector('.modal');
    const bsModal = new Modal(modalElement);
    bsModal.show();
    bsModal.hide();

**After:**

..  code-block:: javascript

    import Modal from '@typo3/backend/modal';
    import Severity from '@typo3/backend/severity';

    // Show a simple modal
    Modal.show(
      'My Modal Title',
      'This is the modal content',
      Severity.info,
      [
        {
          text: 'Close',
          btnClass: 'btn-default',
          trigger: (event, modal) => modal.hideModal()
        }
      ]
    );

    // Dismiss the current modal
    Modal.dismiss();

Data attribute migration
------------------------

Replace Bootstrap's :js:`data-bs-toggle` and :js:`data-bs-target` attributes
with TYPO3's modal trigger API.

**Before:**

..  code-block:: html

    <button type="button"
            data-bs-toggle="modal"
            data-bs-target="#myModal"
            data-bs-content="Are you sure?">
      Open Modal
    </button>

**After:**

..  code-block:: html

    <button type="button"
            class="t3js-modal-trigger"
            data-title="Confirmation"
            data-content="Are you sure?"
            data-severity="warning"
            data-button-close-text="Cancel"
            data-button-ok-text="Confirm">
      Open Modal
    </button>

Alternatively, use the JavaScript API directly:

..  code-block:: javascript

    import Modal from '@typo3/backend/modal';
    import Severity from '@typo3/backend/severity';

    document.querySelector('button').addEventListener('click', (event) => {
      Modal.confirm(
        'Confirmation',
        'Are you sure?',
        Severity.warning
      );
    });

..  index:: Backend, JavaScript, NotScanned, ext:backend
