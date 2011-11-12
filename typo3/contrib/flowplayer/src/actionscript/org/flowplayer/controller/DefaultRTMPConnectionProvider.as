/*    
 *    Copyright 2008 Anssi Piirainen
 *
 *    This file is part of FlowPlayer.
 *
 *    FlowPlayer is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    FlowPlayer is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with FlowPlayer.  If not, see <http://www.gnu.org/licenses/>.
 */

package org.flowplayer.controller {
    import flash.events.NetStatusEvent;
    import flash.utils.setTimeout;

    import org.flowplayer.controller.ConnectionProvider;
	import org.flowplayer.model.Clip;
	import org.flowplayer.util.Log;
	
	import flash.events.NetStatusEvent;
	import flash.net.NetConnection;		

	/**
	 * @author api
	 */
	public class DefaultRTMPConnectionProvider implements ConnectionProvider {
		protected var log:Log = new Log(this);
		private var _connection:NetConnection;
		private var _successListener:Function;
		private var _failureListener:Function;
		private var _connectionClient:Object;
        private var _provider:NetStreamControllingStreamProvider;
        private var _connectionArgs:Array;
        private var _clip:Clip;

        private function doConnect(connectionArgs:Array, connectionUrl:String):void {
            if (connectionArgs.length > 0) {
                _connection.connect.apply(_connection, [connectionUrl].concat(connectionArgs));
            } else {
                _connection.connect(connectionUrl);
            }
        }

        public function connect(provider:StreamProvider, clip:Clip, successListener:Function, objectEndocing:uint, connectionArgs:Array):void {
            _provider = provider as NetStreamControllingStreamProvider;
			_successListener = successListener;
			_connection = new NetConnection();
			_connection.proxyType = "best";
            _connection.objectEncoding = objectEndocing;
            _connectionArgs = connectionArgs;
            _clip = clip;
			
			if (_connectionClient) {
				_connection.client = _connectionClient;
			}
			_connection.addEventListener(NetStatusEvent.NET_STATUS, _onConnectionStatus);

            var connectionUrl:String = getNetConnectionUrl(clip);
            log.debug("netConnectionUrl is " + connectionUrl);
            doConnect(connectionArgs, connectionUrl);
        }

		protected function getNetConnectionUrl(clip:Clip):String {
			return null;
		}

		private function _onConnectionStatus(event:NetStatusEvent):void {
            onConnectionStatus(event);
			if (event.info.code == "NetConnection.Connect.Success" && _successListener != null) {
				_successListener(_connection);
                
            } else if (event.info.code == "NetConnection.Connect.Rejected") {
                if(event.info.ex.code == 302) {
                    var redirectUrl:String = event.info.ex.redirect;
                    log.debug("doing a redirect to " + redirectUrl);
                    _clip.setCustomProperty("netConnectionUrl", redirectUrl);
                    setTimeout(connect, 100, _provider, _clip, _successListener, _connection.objectEncoding, _connectionArgs);
				}
                
            } else if (["NetConnection.Connect.Failed", "NetConnection.Connect.AppShutdown", "NetConnection.Connect.InvalidApp"].indexOf(event.info.code) >= 0) {
				
				if (_failureListener != null) {
					_failureListener();
				}
			}	
		}

        /**
         * Called when NetStatusEvent.NET_STATUS is received for the NetConnection. This
         * gets called before the successListener() gets called. 
         * @param event
         * @return
         */
        protected function onConnectionStatus(event:NetStatusEvent):void {
        }

		public function set connectionClient(client:Object):void {
			if (_connection) {
				_connection.client = client;
			}
			_connectionClient = client;
		}
		
		public function set onFailure(listener:Function):void {
			_failureListener = listener;
		}
		
		protected function get connection():NetConnection {
			return _connection;
		}

        public function handeNetStatusEvent(event:NetStatusEvent):Boolean {
            return true;
        }

        protected function get provider():NetStreamControllingStreamProvider {
            return _provider;
        }

        protected function get failureListener():Function {
            return _failureListener;
        }

        protected function get successListener():Function {
            return _successListener;
        }
    }
}
