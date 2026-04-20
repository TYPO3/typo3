..  include:: /Includes.rst.txt

..  _deprecation-109102-1740480000:

==============================================================
Deprecation: #109102 - FormEngine "additionalHiddenFields" key
==============================================================

See :issue:`109102`

Description
===========

The `additionalHiddenFields` result array key in FormEngine was a legacy
mechanism that stored hidden :html:`<input>` HTML strings separately from the
main `html` key. This indirection is no longer needed. Elements can simply
add their hidden fields to the `html` key.

The following have been deprecated:

* The `additionalHiddenFields` key in FormEngine result arrays
* :php:`FormResult::$hiddenFieldsHtml`
* :php:`FormResultCollection::getHiddenFieldsHtml()`

Impact
======

Third-party FormEngine elements that add entries to
:php:`$resultArray['additionalHiddenFields']` will trigger a PHP
:php:`E_USER_DEPRECATED` level error when their result is merged via
:php:`AbstractNode::mergeChildReturnIntoExistingResult()`.

Affected installations
======================

Installations with custom FormEngine elements or containers that populate the
`additionalHiddenFields` result array key.

Migration
=========

Move hidden field HTML from `additionalHiddenFields` into the `html` key.

Before:

..  code-block:: php

    $resultArray = $this->initializeResultArray();
    $resultArray['additionalHiddenFields'][] =
        '<input type="hidden" name="myField" value="myValue" />';

After:

..  code-block:: php

    $resultArray = $this->initializeResultArray();
    $resultArray['html'] .=
        '<input type="hidden" name="myField" value="myValue" />';

..  index:: Backend, PHP-API, NotScanned, ext:backend
