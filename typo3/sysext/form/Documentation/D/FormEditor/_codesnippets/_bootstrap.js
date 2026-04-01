/**
 * Custom form editor module for EXT:my_extension.
 */
export function bootstrap(formEditorApp) {
    const ps = formEditorApp.getPublisherSubscriber();

    ps.subscribe('view/ready', () => {
        // Editor is fully initialised – set up your custom logic here.
    });
}
