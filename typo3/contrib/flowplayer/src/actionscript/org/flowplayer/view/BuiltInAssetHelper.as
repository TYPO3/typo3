/*    
 *    Author: Anssi Piirainen, <api@iki.fi>
 *
 *    Copyright (c) 2010 Flowplayer Oy
 *
 *    This file is part of Flowplayer.
 *
 *    Flowplayer is licensed under the GPL v3 license with an
 *    Additional Term, see http://flowplayer.org/license_gpl.html
 */
package org.flowplayer.view {
    import flash.display.DisplayObject;


    public class BuiltInAssetHelper {
        private static var _config:BuiltInConfig = new BuiltInConfig();
        private static const PLAY:String = "PlayButton";
        private static const LOGO:String = "Logo";

        public static function get hasPlayButton():Boolean {
            return _config.hasOwnProperty(PLAY);
        }

        public static function createPlayButton():DisplayObject {
            return createAsset(PLAY);
        }

        public static function get hasLogo():Boolean {
            return _config.hasOwnProperty(LOGO);
        }

        public static function createLogo():DisplayObject {
            return createAsset(LOGO);
        }

        private static function createAsset(name:String):* {
            if (_config.hasOwnProperty(name)) {
                var clazz:Class = _config[name] as Class;
                return new clazz();
            }
            return null;
        }
    }

}