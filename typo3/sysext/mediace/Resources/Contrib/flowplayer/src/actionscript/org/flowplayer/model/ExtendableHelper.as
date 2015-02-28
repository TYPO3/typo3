/*
 * This file is part of Flowplayer, http://flowplayer.org
 *
 * By: Anssi Piirainen, <api@iki.fi>
 *
 * Copyright (c) 2008-2011 Flowplayer Oy
 *
 * Released under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 */
package org.flowplayer.model {
    import org.flowplayer.util.Assert;

    public class ExtendableHelper {
        private var _customProperties:Object;

        public function set props(props:Object):void {
            _customProperties = props;
        }

        public function setProp(name:String, value:Object):void {
            Assert.notNull(name,  "the name of the property cannot be null");

            if (!_customProperties) {
                _customProperties = new Object();
            }
            _customProperties[name] = value;
        }

        public function get props():Object {
            return _customProperties;
        }

        public function getProp(name:String):Object {
            if (!_customProperties) return null;
            return _customProperties[name];
        }

        public function deleteProp(name:String):void {
            if (!_customProperties) return;
            delete _customProperties[name];
        }
    }
}
