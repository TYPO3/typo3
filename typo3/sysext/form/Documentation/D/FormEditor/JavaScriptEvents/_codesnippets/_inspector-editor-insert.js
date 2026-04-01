export function bootstrap(formEditorApp) {
    formEditorApp.getPublisherSubscriber().subscribe(
        'view/inspector/editor/insert/perform',
        (topic, args) => {
            const [editorConfiguration, editorHtml] = args;

            if (editorConfiguration.templateName !== 'Inspector-MyCustomEditor') {
                return;
            }

            // Wire up your custom editor UI inside editorHtml
            const input = editorHtml.querySelector('.my-custom-input');
            if (input) {
                input.addEventListener('change', (e) => {
                    formEditorApp
                        .getCurrentlySelectedFormElement()
                        .set(editorConfiguration.propertyPath, e.target.value);
                });
            }
        },
    );
}
