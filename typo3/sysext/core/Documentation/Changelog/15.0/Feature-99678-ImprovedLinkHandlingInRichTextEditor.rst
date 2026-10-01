..  include:: /Includes.rst.txt

..  _feature-99678-1790186405:

================================================================
Feature: #99678 - Improved link handling in the rich text editor
================================================================

See :issue:`99678`, :issue:`99679` and :issue:`99680`

Description
===========

The link plugin of the CKEditor 5 based rich text editor now resolves
TYPO3 specific link targets such as :html:`t3://page?uid=3` to human readable
information and uses it in the places where editors interact with links.

*   **Inserting links:** When a link is inserted without selected text, the
    link title (if filled) or the title of the link target, for example the
    page title, is used as link text instead of the raw link. The inserted
    link stays selected afterwards, so it can be adjusted right away.
*   **Link preview:** The balloon shown for a link now displays the icon,
    the title and the path of the target instead of the raw link. An
    additional button opens the link in a new tab.
*   **Opening links:** :kbd:`Ctrl` + click (:kbd:`Cmd` + click on macOS) and
    :kbd:`Alt` + :kbd:`Enter` open the resolved frontend URL, or the public
    URL of a file, instead of the unresolvable :html:`t3://` link.

The information is provided by the new backend AJAX route
`link_preview`, which respects the page permissions of the
current backend user.

Impact
======

Editors get a link workflow comparable to other modern editors. The
generated HTML output of links is unchanged.

..  index:: Backend, JavaScript, RTE, ext:rte_ckeditor
