/*
 * This file is part of Flowplayer, http://flowplayer.org
 *
 * By: Daniel Rossi, <electroteque@gmail.com>
 * Copyright (c) 2009 Electroteque Multimedia
 *
 * Released under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 */

package org.flowplayer.captions
{
    import org.flowplayer.model.DisplayProperties;
    import org.flowplayer.model.DisplayPropertiesImpl;
    import org.flowplayer.util.Arrange;

    internal class Config {
        private var _autoLayout:Boolean = true;
        private var _simpleFormatting:Boolean = false;
        private var _captionTarget:String;
        private var _template:String;
        private static const BUTTON_DEFAULTS:Object = { width: 20, height: 15, right: 5, bottom: 35, name: "cc_button", label: 'CC' };
        private var _button:Object = BUTTON_DEFAULTS;


        public function get captionTarget():String {
            return _captionTarget;
        }

        public function set captionTarget(captionTarget:String):void {
            _captionTarget = captionTarget;
        }

        public function get template():String {
            return _template;
        }

        public function set template(template:String):void {
            _template = template;
        }

        public function get autoLayout():Boolean {
            return _autoLayout;
        }

        public function set autoLayout(autoLayout:Boolean):void {
            _autoLayout = autoLayout;
        }

        public function get simpleFormatting():Boolean {
            return _simpleFormatting;
        }

        public function set simpleFormatting(simpleFormatting:Boolean):void {
            _simpleFormatting = simpleFormatting;
        }

        public function get button():Object {
            return _button;
        }

        public function set button(val:Object):void {
            if (! val) {
                _button = null;
                return;
            }
            fixPositionSettings(val, BUTTON_DEFAULTS);
            _button = BUTTON_DEFAULTS;
            for (var prop:String in val) {
                _button[prop] = val[prop];
            }
        }

        private function fixPositionSettings(props:Object, defaults:Object):void {
            clearOpposite("bottom", "top", props, defaults);
            clearOpposite("left", "right", props, defaults);
        }

        private function clearOpposite(prop1:String, prop2:String, props:Object, defaults:Object):void {
            if (props.hasOwnProperty(prop1) && defaults.hasOwnProperty(prop2)) {
                delete defaults[prop2];
            } else if (props.hasOwnProperty(prop2) && defaults.hasOwnProperty(prop1)) {
                delete defaults[prop1];
            }
        }

    }
}



