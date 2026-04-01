export function bootstrap(formEditorApp) {
    // Returns 'Name'
    const placeholder = formEditorApp
        .getFormElementByIdentifierPath('example-form/page-1/name')
        .get('properties.fluidAdditionalAttributes.placeholder');
}
