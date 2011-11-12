/*
 *    Copyright (c) 2008 - 2010 Flowplayer Oy
 *
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
package org.flowplayer.view {
    import flash.display.DisplayObject;
    import flash.display.MovieClip;
    import flash.display.StageAlign;
    import flash.display.StageScaleMode;
    import flash.events.Event;
    import flash.events.ProgressEvent;
    import flash.utils.getDefinitionByName;
    import flash.utils.*;
    import flash.display.StageDisplayState;
    
    import org.flowplayer.util.Arrange;
    import org.flowplayer.util.Log;
    import org.flowplayer.util.LogConfiguration;

    public class Preloader extends MovieClip {
        private var _log:Log = new Log(this);
        private var _app:DisplayObject;
        // this variable can be set from external SWF files, if it's set well use it to construct the config
        public var injectedConfig:String;

        public function Preloader() {

            var logConfig:LogConfiguration = new LogConfiguration();
            logConfig.level = "error";
            logConfig.filter = "org.flowplayer.view.Preloader";
            Log.configure(logConfig);
            _log.debug("Preloader");

            stop();
            addEventListener(Event.ADDED_TO_STAGE, onAddedToStage);
        }
        
        private function onStageResize(e:Event):void{
          setParentDimensions();
        }

        private function setParentDimensions():void{
          if(stage.displayState == StageDisplayState.FULL_SCREEN || (Arrange.set && !Arrange.hasParent)){
            Arrange.parentWidth=stage.stageWidth;
            Arrange.parentHeight=stage.stageHeight;
            return;
          }
          if(Arrange.set && Arrange.hasParent){
            Arrange.parentWidth  = Arrange.localWidth;
            Arrange.parentHeight = Arrange.localHeight;
            return;
          } 
          var p:Object = parent;
          while(p){
            if(p.width !=0 && p.height !=0 && getQualifiedClassName(p) != 'mx.controls::SWFLoader'){
              Arrange.parentWidth =Arrange.localWidth  = p.width;
              Arrange.parentHeight = Arrange.localHeight = p.height;
              Arrange.hasParent = true;
              break;
            }
            p=p.parent;
          }
          if(Arrange.parentWidth == 0 && Arrange.parentHeight == 0){
            Arrange.parentWidth = stage.stageWidth;
            Arrange.parentHeight = stage.stageHeight;
          }
          Arrange.set = true;
        }
        
        private function onAddedToStage(event:Event):void {
            log("onAddedToStage(): stage size is " + Arrange.parentWidth + " x " + Arrange.parentHeight);
            log("onAddedToStage(), bytes loaded " + loaderInfo.bytesLoaded);
            stage.addEventListener(Event.RESIZE, onStageResize, false, 1);
            setParentDimensions();
            
            addEventListener(Event.ENTER_FRAME, enterFrameHandler);
        }

        private function enterFrameHandler(evt:Event):void {
            log("enterFrameHandler() " + loaderInfo.bytesLoaded);

            if (loaderInfo.bytesLoaded == loaderInfo.bytesTotal) {
                log("bytesLoaded == bytesTotal, stageWidth = " + Arrange.parentWidth + " , stageHeight = " + Arrange.parentHeight);
                if (Arrange.parentWidth != 0 && Arrange.parentHeight != 0) {
                    initialize();
                    removeEventListener(Event.ENTER_FRAME, enterFrameHandler);
                }
            }
        }

        private function initialize():void {
            log("initialize()");
            nextFrame();

            if (_app) {
                log("initialize(), _app already instantiated returning");
                return;
            }

            prepareStage();
            try {
                var mainClass:Class = getAppClass();
                _app = new mainClass() as DisplayObject;
                addChild(_app as DisplayObject);
                log("Launcher instantiated " + _app);
                removeEventListener(Event.ENTER_FRAME, enterFrameHandler);
            } catch (e:Error) {
                log("error instantiating Launcher " + e + ": " + e.message);
                _app = null;
            }
        }

        private function getAppClass():Class {
            try {
                return Class(getDefinitionByName("org.flowplayer.view.Launcher"));
            } catch (e:Error) {
            }
            return null;
        }

        private function prepareStage():void {
            if (! stage) return;
            stage.align = StageAlign.TOP_LEFT;
            stage.scaleMode = StageScaleMode.NO_SCALE;
        }

        private function log(msg:Object):void {
            _log.debug(msg + "");
            trace(msg + "");
        }

        private function get rotationEnabled():Boolean {
            var config:Object = stage.loaderInfo.parameters["config"];
            if (! config) return true;
            if (config.replace(/\s/g, "").indexOf("buffering:null") > 0) return false;
            return true;
        }
    }
}
