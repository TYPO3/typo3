export function bootstrap(formEditorApp) {
    const formElement = formEditorApp
        .getFormElementByIdentifierPath('example-form/page-1/name');
    const copy = formElement.clone();
}
