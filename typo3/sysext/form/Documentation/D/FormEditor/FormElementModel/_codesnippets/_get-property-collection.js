export function bootstrap(formEditorApp) {
    const formElement = formEditorApp
        .getFormElementByIdentifierPath('example-form/page-1/name');

    const propertyPath = formEditorApp
        .buildPropertyPath('options.minimum', 'StringLength', 'validators', formElement);
    // propertyPath = e.g. 'validators.0.options.minimum'

    const value = formElement.get(propertyPath); // '1'
}
