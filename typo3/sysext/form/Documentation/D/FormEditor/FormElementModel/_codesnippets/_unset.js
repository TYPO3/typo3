export function bootstrap(formEditorApp) {
    formEditorApp
        .getFormElementByIdentifierPath('example-form/page-1/name')
        .unset('properties.fluidAdditionalAttributes.placeholder');
}
