/*
 * This file is part of Flowplayer, http://flowplayer.org
 *
 * By: Anssi Piirainen, <support@flowplayer.org>
 * Copyright (c) 2009 Flowplayer Ltd
 *
 * Released under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 */

package org.flowplayer.controller {
    import flash.events.NetStatusEvent;
    import flash.net.NetConnection;

    import flash.utils.setTimeout;

    import org.flowplayer.util.Log;

    public class ParallelRTMPConnector {
        protected var log:Log = new Log(this); 
        protected var _url:String;
        protected var _successListener:Function;
        protected var _connectionClient:Object;
        protected var _connection:NetConnection;
        protected var _failureListener:Function;
        protected var _failed:Boolean;

        public function ParallelRTMPConnector(url:String, connectionClient:Object, onSuccess:Function, onFailure:Function) {
            _url = url;
            _connectionClient = connectionClient;
            _successListener = onSuccess;
            _failureListener = onFailure;
            _failed = false;
            log.debug("created with connection client " + _connectionClient);
        }

        public function connect(proxyType:String, objectEncoding:uint, connectionArgs:Array):void {
            log.debug(this +"::connect() using proxy type '" + proxyType + "'" + ", object encoding " + objectEncoding);
            if (_successListener == null) {
                log.debug(this + ", this connector has been stopped, will not proceed with connect()");
                return;
            }
            _connection = new NetConnection();
            _connection.proxyType = proxyType;
            _connection.objectEncoding = objectEncoding;

            log.debug("using connection client " + _connectionClient);
            if (_connectionClient) {
                _connection.client = _connectionClient;
            }
            _connection.addEventListener(NetStatusEvent.NET_STATUS, _onConnectionStatus);

            log.debug("netConnectionUrl is " + _url);
            if (connectionArgs && connectionArgs.length > 0) {
                _connection.connect.apply(_connection, [ _url ].concat(connectionArgs));
            } else {
                _connection.connect(_url);
            }
        }

		protected function onConnectionStatus(event:NetStatusEvent):void {
			
		}

        private function _onConnectionStatus(event:NetStatusEvent):void {
	
			onConnectionStatus(event);
	
            log.debug(this + "::_onConnectionStatus() " + event.info.code);

            if (event.info.code == "NetConnection.Connect.Success") {
                if (_successListener != null) {
                    _successListener(this, _connection);
                } else {
                    log.debug("this connector is stopped, will not call successListener");
                    _connection.close();
                }
                return;
                
            }

            if (event.info.code == "NetConnection.Connect.Rejected" && event.info.ex && event.info.ex.code == 302) {
                log.debug("starting a timeout to connect to a redirected URL " + event.info.ex.redirect);
                setTimeout(function():void{
                    log.debug("connecting to a redirected URL " + event.info.ex.redirect);
                    _connection.connect(event.info.ex.redirect);
                }, 100);
                return;
            }

            if (["NetConnection.Connect.Failed", "NetConnection.Connect.Rejected", "NetConnection.Connect.AppShutdown", "NetConnection.Connect.InvalidApp"].indexOf(event.info.code) >= 0) {
                _failed = true;
                if (_failureListener != null) {
                    _failureListener();
                }
            }
        }

        public function stop():void {
            log.debug("stop()");
            if (_connection) {
                _connection.close();
            }
            _successListener = null;
        }

        public function toString():String {
            return "Connector, [" + _url + "]";
        }

        public function get failed():Boolean {
            return _failed;
        }
    }
}