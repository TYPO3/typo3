/*    
 *    Copyright (c) 2008-2011 Flowplayer Oy *
 *    This file is part of Flowplayer.
 *
 *    Flowplayer is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    Flowplayer is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with Flowplayer.  If not, see <http://www.gnu.org/licenses/>.
 */

package org.flowplayer.model {
    import flash.net.ObjectEncoding;

	/**
	 * @author api
	 */
	public class ProviderModel extends PluginModelImpl implements PluginModel {
		private var _connectionProvider:String;
        private var _urlResolver:String;
        private var _objectEncoding:uint = ObjectEncoding.DEFAULT;

		public function ProviderModel(providerObject:Object, name:String) {
			super(providerObject, name);
		}

		public function get connectionProvider():String {
			return _connectionProvider;
		}

		public function set connectionProvider(connectionProvider:String):void {
			_connectionProvider = connectionProvider;
		}

        public function get urlResolver():String {
            return _urlResolver;
        }

        public function set urlResolver(val:String):void {
            _urlResolver = val;
        }

        public function get objectEncoding():uint {
            return _objectEncoding;
        }

        public function set objectEncoding(val:uint):void {
            _objectEncoding = val;
        }
    }
}
