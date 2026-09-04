..  include:: /Includes.rst.txt

..  _important-94407-1788534856:

===========================================================
Important: #94407 - Custom actions in the workspaces module
===========================================================

See :issue:`94407`

Description
===========

The record table of the workspaces backend module rendered a fixed set of action
buttons per record. Listeners of the PSR-14 event
:php:`\TYPO3\CMS\Workspaces\Event\AfterDataGeneratedForWorkspaceEvent` could
neither add a button of their own nor hide or disable one of the buttons the
module renders itself, which left overriding the module's JavaScript as the only
option.

The buttons of a record are now described in an :php:`actions` section, which a
listener can modify:

..  code-block:: php
    :caption: Example event listener class

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Workspaces\Event\AfterDataGeneratedForWorkspaceEvent;

    final class ModifyWorkspaceRecordActionsEventListener
    {
        #[AsEventListener('my-extension/modify-workspace-record-actions')]
        public function __invoke(AfterDataGeneratedForWorkspaceEvent $event): void
        {
            $data = $event->getData();
            $data['tt_content:42']['actions'] = [
                // Render the publish button greyed out
                'publish' => ['enabled' => false],
                // Remove the discard button from the record
                'remove' => ['visible' => false],
                // Add an own button
                'schedule' => [
                    'icon' => 'actions-clock',
                    'title' => 'Schedule publication',
                ],
            ];
            $event->setData($data);
        }
    }

Records are indexed by table name and uid of the versioned record. The section is
merged into the set of actions the module determines for itself, so only the keys
that should differ have to be given:

*   :php:`enabled` - :php:`false` renders the button greyed out, the way the
    module does it for missing permissions
*   :php:`visible` - :php:`false` removes the button from the record
*   :php:`icon` - identifier of the icon rendered in the button
*   :php:`title` - title attribute of the button
*   :php:`url` - renders the action as a link to this URL
*   :php:`group` - identifier of the button group the action is rendered in,
    :php:`custom` for actions the module does not know

The module renders the actions :php:`preview`, :php:`qrcode`, :php:`open`,
:php:`version`, :php:`expand`, :php:`changes`, :php:`publish` and :php:`remove`
itself and knows their icon and title. Every other identifier is rendered from
the payload alone, as a link if an :php:`url` is given and as a button carrying a
:php:`data-action` attribute with the identifier otherwise. The JavaScript of the
declaring extension picks such a button up from there and has to use event
delegation, since the record table is re-rendered whenever the module updates.

The permissions of a record, such as :php:`allowedAction_publish`, are unchanged
and still determine the default state of the module's own actions. Listeners that
modify them therefore keep working as before.

..  index:: Backend, JavaScript, PHP-API, ext:workspaces
