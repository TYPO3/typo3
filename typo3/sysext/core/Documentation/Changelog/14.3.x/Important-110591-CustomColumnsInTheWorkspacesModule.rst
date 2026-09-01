..  include:: /Includes.rst.txt

..  _important-110591-1788297183:

============================================================
Important: #110591 - Custom columns in the workspaces module
============================================================

See :issue:`110591`

Description
===========

Listeners of the PSR-14 event
:php:`\TYPO3\CMS\Workspaces\Event\AfterDataGeneratedForWorkspaceEvent` can enrich
the records displayed in the workspaces backend module, but the module rendered a
fixed set of columns and discarded any additional data. Adding custom columns to
the module, which was possible until TYPO3 v7, therefore did not work.

The record table of the workspaces module now renders custom columns. A listener
declares one by adding an :php:`additional` section to the records it wants to
enrich:

..  code-block:: php

    $data = $event->getData();
    $data['tt_content:42']['additional']['deadline'] = [
        'label' => 'Deadline',
        'value' => '2026-03-01 08:00',
        'icon' => 'actions-clock',
        'title' => 'Editorial deadline',
        'url' => '/typo3/module/my-module?element=42',
    ];
    $event->setData($data);

Records are indexed by table name and uid of the versioned record. All keys of a
column are optional:

*   :php:`label` - header of the column, falls back to the column identifier
*   :php:`value` - content of the cell, rendered as plain text
*   :php:`icon` - identifier of an icon rendered in front of the value
*   :php:`title` - title attribute of the cell
*   :php:`url` - wraps the content of the cell into a link to this URL

A column is displayed as soon as one record declares it. The columns are
determined from all records of the workspace and not only from those of the
current page, so the table header does not change while paging.

..  index:: Backend, JavaScript, PHP-API, ext:workspaces
