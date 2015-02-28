package org.flowplayer.view {
    import mx.core.UIComponent;

    public class FlowplayerComponent extends UIComponent {
        private var _launcher:Launcher;

        public function FlowplayerComponent() {
        }

        override protected function createChildren():void {
            _launcher = new Launcher();
            addChild(_launcher);
        }

    }
}