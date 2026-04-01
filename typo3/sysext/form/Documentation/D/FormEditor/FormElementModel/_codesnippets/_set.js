export function bootstrap(formEditorApp) {
    formEditorApp
        .getFormElementByIdentifierPath('example-form/page-1/name')
        .set('properties.fluidAdditionalAttributes.placeholder', 'New Placeholder');
}
