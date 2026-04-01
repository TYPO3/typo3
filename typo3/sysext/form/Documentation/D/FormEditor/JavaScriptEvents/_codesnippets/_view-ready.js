export function bootstrap(formEditorApp) {
    formEditorApp.getPublisherSubscriber().subscribe('view/ready', () => {
        // Safe to call any formEditorApp API here.
    });
}
