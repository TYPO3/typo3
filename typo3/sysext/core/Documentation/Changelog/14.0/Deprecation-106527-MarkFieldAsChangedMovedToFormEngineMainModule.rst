..  include:: /Includes.rst.txt

..  _deprecation-106527-1744189076:

=============================================================================
Deprecation: #106527 - `markFieldAsChanged()` moved to FormEngine main module
=============================================================================

See :issue:`106527`

Description
===========

The static method :js:`markFieldAsChanged()` in the module
:js:`@typo3/backend/form-engine-validation` is used to modify the markup in the
DOM to mark a field as changed. Technically, this is unrelated to validation at
all, therefore the method has been moved to :js:`@typo3/backend/form-engine`.


Impact
======

Calling :js:`markFieldAsChanged()` from
:js:`@typo3/backend/form-engine-validation` will trigger a deprecation notice in
the browser console.


Affected installations
======================

All extensions using the deprecated code are affected.


Migration
=========

If not already done, import the main FormEngine module and call
:js:`markFieldAsChanged()` from that.

Example:

..  code-block:: diff

    - import FormEngineValidation from '@typo3/backend/form-engine-validation.js';
    + import FormEngine from '@typo3/backend/form-engine.js';

    - FormEngineValidation.markFieldAsChanged(fieldReference);
    + FormEngine.markFieldAsChanged(fieldReference);


Example compatibility layer:

..  code-block:: js

    import FormEngine from '@typo3/backend/form-engine.js';
    import FormEngineValidation from '@typo3/backend/form-engine-validation.js';

    if (typeof FormEngine.markFieldAsChanged === 'function') {
      FormEngine.markFieldAsChanged(fieldReference);
    } else {
      FormEngineValidation.markFieldAsChanged(fieldReference);
    }

..  index:: Backend, JavaScript, NotScanned, ext:backend
