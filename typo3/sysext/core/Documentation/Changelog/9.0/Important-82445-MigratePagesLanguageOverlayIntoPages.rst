.. include:: ../../Includes.txt

=============================================================
Important: #82445 - Migrate pages_language_overlay into pages
=============================================================

See :issue:`82445`

Description
===========

The functionality of "pages_language_overlay" has been migrated into "pages".

An upgrade wizard is in place to migrate all existing data into the "pages" database table.

All relations directly to "pages_language_overlay" are migrated to the newly created "pages" records
as well.

Some rules for future development need to be clarified:

Definitions:

**Default Language Page**

- previously exclusively available in "pages"
- Holds the PID of the parent page
- MUST be in place in order to create a translated page (not possible to create a translated page without having a default page in place)
- Has always "l10n_parent" and "sys_language_uid" fields set to "0"
- The "uid" of this record is automatically the PID for all records of this page

**Translated Page** (previously known as "pages_language_overlay")

- Is identified as *Translated Page* by having a "sys_language_uid" field greater 0 and "l10n_parent" field containing the "uid" of the *Default Language Page*.
- The value of the "pid" field is the same "pid" as of the *Default Language Page* - effectively putting the *Translated Page* and the *Default Language Page* on the same root-level.
- The value of "sorting" is the same for all translated pages
- The "uid" field is not used by anything currently within the TYPO3 Core.
- The "hidden" field is set as "allowLanguageSynchronization"


**The following details apply**

- Any TCA-based records (= subpages, content elements) still ALWAYS contain the pid to the *Original Language Page*, a DataHandler restriction ensures this constraint.
- Backend: All UI elements like Element Browser, Page Browser etc. are restricted to only show the *Default Translation Pages* to be selected (one can not link to a specific Translated Page).
- Permissions are always fetched from the "Original Language Page"
- DataHandler: Moving or deleting of a *Default Language Page* always moves/deletes the associated *Translated Page* records as well.
- DataHandler: "sorting" and "pid" parameters of translations are always kept in sync one-to-one for translated pages. Translated pages cannot be moved themselves.
- Permissions: Restricting a Backend User/Group to a language limits the access to "pages" to a specific language in page module.
- Permissions: All existing "pages_language_overlay" permissions are merged into "pages" options for all records - when a Backend User/Group is limited to only certain languages (and not the default language) this , the . If a Backend User/Group does have permission on "pages_language_overlay" but not "pages", the Backend User/Group has automatically assigned all translations (sys_language_uid) as language limitations.
- Frontend: Requesting a page can be done with ?id=originalpage&L=1 or ?id=translatedpage where "?id=translatedpage" internally resolves the "id" parameter to the uid of the Original Language Page and the "L" parameter resolved to the "sys_language_uid" corresponding in the TypoScript options.
- Frontend: All "pid" checks are always done against the Original Language Page, as all records still sit on that page.
- Frontend: Generating a link to a *Default Translation Page* with a current Translated Page generated, will exchange the target on link creation to the targeted Translated Page automatically.

.. index:: Database, PHP-API
