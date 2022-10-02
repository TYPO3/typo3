.. include:: /Includes.rst.txt

.. _breaking-96158:

==========================================================================
Breaking: #96158 - Remove support for inline JavaScript in fieldChangeFunc
==========================================================================

See :issue:`96158`

Description
===========

Custom :php:`FormEngine` nodes allow to use internal property `fieldChangeFunc`
to add or modify client-side JavaScript behavior when field values are changed.
Through TYPO3 v11 it was possible to directly use inline JavaScript that was
assigned as plain :php:`string` type. With TYPO3 v12.0 inline JavaScript is
not supported anymore - values assigned to `fieldChangeFunc` items have to
implement :php:`\TYPO3\CMS\Backend\Form\Behavior\OnFieldChangeInterface`
which allows to declare the behavior in a structured way.

Impact
======

Assigning scalar values to `fieldChangeFunc` items - without using
:php:`\TYPO3\CMS\Backend\Form\Behavior\OnFieldChangeInterface` - is not
supported anymore and will lead to PHP type errors.

Affected Installations
======================

Installations implementing custom :php:`FormEngine` components (wizards, nodes,
render-types, ...) that provide inline JavaScript using `fieldChangeFunc`.

..  code-block:: php

    // examples
    $this->data['parameterArray']['fieldChangeFunc']['example'] = "alert('demo');";
    $parameterArray['fieldChangeFunc']['example'] = "alert('demo');";

Migration
=========

:doc:`Previous deprecation ChangeLog documentation <../11.5/Deprecation-91787-DeprecateInlineJavaScriptInFieldChangeFunc>`
provided migration details already. A complete and installable example is available with
`ext:demo_91787 <https://github.com/ohader/demo_91787>`__ as well.

The provided code examples are supposed to work with TYPO3 v11 and v12, easing
the migration path for extension maintainers. The crucial point is to use
:php:`\TYPO3\CMS\Backend\Form\Behavior\OnFieldChangeInterface` which still
would inline JavaScript as a fallback in TYPO3 v11.

Thus, basically scalar assignments like...

..  code-block:: php

    // examples
    $this->data['parameterArray']['fieldChangeFunc']['example'] = "alert('demo');";
    $parameterArray['fieldChangeFunc']['example'] = "alert('demo');";

... have to be replaced by custom :php:`OnFieldChangeInterface` instances...

..  code-block:: php

    // examples
    $this->data['parameterArray']['fieldChangeFunc']['example'] = new AlertOnFieldChange('demo');
    $parameterArray['fieldChangeFunc']['example'] = new AlertOnFieldChange('demo');

.. index:: Backend, JavaScript, TCA, NotScanned, ext:backend
