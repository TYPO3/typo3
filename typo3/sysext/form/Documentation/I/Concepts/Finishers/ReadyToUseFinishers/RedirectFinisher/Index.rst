..  include:: /Includes.rst.txt
..  _concepts-finishers-redirectfinisher:
..  _finishers-redirect:

=================
Redirect finisher
=================

This finisher redirects the user after submitting the form to a given page.
Additional parameters can be added to the URL.

..  contents:: Table of contents

..  important::

    Finishers are executed in the order defined in your form definition.
    This finisher stops the execution of all subsequent finishers in order to perform
    the redirect. Therefore, this finisher should always be the last finisher to be
    executed. Finishers after this one will never be executed.

..  _apireference-finisheroptions-redirectfinisher-options:

Options of the redirect finisher
================================

..  _apireference-finisheroptions-redirectfinisher-options-pageuid:

..  confval:: Page: [pageUid]
    :name: redirectfinisher-pageUid
    :type: int
    :required: true
    :default: `1`

    ID of the page to redirect to. Button :guilabel:`Page` can be used to chose
    a page from the page tree.

..  _apireference-finisheroptions-redirectfinisher-options-additionalparameters:

..  confval:: Additional parameters: [additionalParameters]
    :name: redirectfinisher-additionalParameters
    :type: string
    :required: false
    :default: `''`

    URL parameters which will be appended to the URL.

..  _apireference-finisheroptions-redirectfinisher-options-fragment:

..  confval:: URL fragment: [fragment]
    :name: redirectfinisher-fragment
    :type: string
    :required: false
    :default: `''`

    ID of a content element identifier or a custom fragment
    identifier. This will be appended to the URL and used as section anchor.

    Adds a fragment (e.g. :html:`#c9` or :html:`#foo`) to the redirect link.
    The :html:`#` character can be omitted.

..  _apireference-finisheroptions-redirectfinisher-options-additional:

Additional options of the redirect finisher
===========================================

These additional options can be set directly in the form definition YAML or
programmatically in the options array but **not** from the backend editor:

..  _apireference-finisheroptions-redirectfinisher-options-delay:

..  confval:: delay
    :name: redirectfinisher-delay
    :type: int
    :required: false
    :default: `0`

    The redirect delay in seconds.

..  _apireference-finisheroptions-redirectfinisher-options-statuscode:

..  confval:: statusCode
    :name: redirectfinisher-statusCode
    :type: int
    :required: false
    :default: `303`

    The HTTP status code for the redirect. Default is "303 See Other".

..  _concepts-finishers-redirectfinisher-yaml:

Redirect finisher in the YAML form definition
=============================================

..  literalinclude:: _codesnippets/_form.yaml
    :caption: public/fileadmin/forms/my_form.yaml

..  _concepts-finishers-redirectfinisher-last:

Example: Load the redirect finisher last
========================================

..  literalinclude:: _codesnippets/_example-redirect.yaml
    :caption: public/fileadmin/forms/my_form_with_multiple_finishers.yaml

..  _apireference-finisheroptions-redirectfinisher:

Usage of the Redirect finisher in PHP code
==========================================

Developers can create a confirmation finisher by using the key `Redirect`:

..  literalinclude:: _codesnippets/_finisher.php.inc
    :language: php

This finisher is implemented in :php:`TYPO3\CMS\Form\Domain\Finishers\RedirectFinisher`.
