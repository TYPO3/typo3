export function bootstrap(formEditorApp) {
    const element = formEditorApp
        .getFormElementByIdentifierPath('example-form/page-1/name');

    element.on('properties.fluidAdditionalAttributes.placeholder', 'my/custom/event');

    // The next set() on that path will also publish 'my/custom/event'.
    element.set('properties.fluidAdditionalAttributes.placeholder', 'New Placeholder');
}
