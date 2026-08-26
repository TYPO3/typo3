..  include:: /Includes.rst.txt

..  _important-100638-1787786729:

===================================================================
Important: #100638 - Extbase ignoreEnableFields applies to overlays
===================================================================

See :issue:`100638`

Description
===========

The Extbase query setting :php:`setIgnoreEnableFields()` switched off the
enable fields for the records selected by the query, but not for the
translations those records are overlaid with. A hidden translation was
therefore never found, even though the query was told to ignore enable
fields:

..  code-block:: php

    $query = $this->createQuery();
    $query->getQuerySettings()->setIgnoreEnableFields(true);
    return $query->execute();

With :typoscript:`languageOverlayMode = hideNonTranslated` the record was
dropped from the result entirely, while :php:`count()` still counted it. With
the default overlay mode the untranslated default language record was returned
instead of the hidden translation.

Extbase now mirrors :php:`ignoreEnableFields` and
:php:`enableFieldsToBeIgnored` into the visibility aspect of the context the
overlay is done with, so hidden and time-restricted translations are found as
well. Listing :php:`'disabled'` in :php:`setEnableFieldsToBeIgnored()` reveals
hidden translations, :php:`'starttime'` or :php:`'endtime'` reveals
time-restricted ones, and an empty list keeps meaning "ignore all of them".

Frontend user group restrictions (:php:`'fe_group'`) are not part of the
visibility aspect and are still applied to translations.

Extensions that use :php:`setIgnoreEnableFields(true)` in a multi-language
setup may see hidden translations in their result sets where they previously
saw the default language record or no record at all.

..  index:: PHP-API, ext:extbase
