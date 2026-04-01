export function bootstrap(formEditorApp) {
    formEditorApp
        .getFormElementByIdentifierPath('example-form/page-1/name')
        .off('properties.fluidAdditionalAttributes.placeholder', 'my/custom/event');
}
