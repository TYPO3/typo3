// Illustrative snapshot of a FormElement model as it exists in memory at runtime.
// The actual object is managed by the FormElement class – access it via
// formEditorApp.getFormElementByIdentifierPath() and the get()/set() API.
export const formElementSnapshot = {
    identifier: 'name',
    defaultValue: '',
    label: 'Name',
    type: 'Text',
    properties: {
        fluidAdditionalAttributes: {
            placeholder: 'Name',
        },
    },
    __parentRenderable: 'example-form/page-1 (filtered)',
    __identifierPath: 'example-form/page-1/name',
    validators: [
        { identifier: 'NotEmpty' },
    ],
};
