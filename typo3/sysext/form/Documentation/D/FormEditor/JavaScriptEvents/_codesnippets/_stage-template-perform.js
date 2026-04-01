export function bootstrap(formEditorApp) {
    formEditorApp.getPublisherSubscriber().subscribe(
        'view/stage/abstract/render/template/perform',
        (topic, args) => {
            const [formElement, template] = args;

            if (formElement.get('type') !== 'MyCustomElement') {
                return;
            }

            const labelEl = template.querySelector('[data-identifier="elementLabel"]');
            if (labelEl) {
                labelEl.textContent =
                    formElement.get('label') || formElement.get('identifier');
            }

            const summaryEl = template.querySelector('[data-identifier="elementSummary"]');
            if (summaryEl) {
                summaryEl.textContent =
                    formElement.get('properties.myCustomProperty') ?? '';
            }
        },
    );
}
