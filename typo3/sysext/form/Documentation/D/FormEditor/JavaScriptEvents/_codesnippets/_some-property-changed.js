export function bootstrap(formEditorApp) {
    formEditorApp.getPublisherSubscriber().subscribe(
        'core/formElement/somePropertyChanged',
        (topic, args) => {
            const [propertyPath, newValue, oldValue, identifierPath] = args;
            if (propertyPath === 'label' && identifierPath?.startsWith('my-form/page-1/')) {
                console.log('Label changed from', oldValue, 'to', newValue);
            }
        },
    );
}
