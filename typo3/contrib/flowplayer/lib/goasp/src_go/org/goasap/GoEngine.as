/**
 * Copyright (c) 2007 Moses Gunesch
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
package org.goasap {
	import flash.display.Sprite;
	import flash.events.Event;
	import flash.events.TimerEvent;
	import flash.utils.Dictionary;
	import flash.utils.Timer;
	import flash.utils.getQualifiedClassName;
	import flash.utils.getTimer;
	
	import org.goasap.errors.DuplicateManagerError;
	import org.goasap.interfaces.IManageable;
	import org.goasap.interfaces.IManager;
	import org.goasap.interfaces.IUpdatable;
	import org.goasap.interfaces.ILiveManager;	

	/**
	 * Provides <code>update</code> calls to <code>IUpdatable</code> instances on their specified <code>pulseInterval</code>.
	 * 
	 * <p><b>Using these Docs</b></p>
	 * 
	 * <p><i>Protected methods and properties have been excluded in almost all 
	 * cases, but are documented in the classes. Exceptions include key protected
	 * methods or properties that are integral for writing subclasses or understanding 
	 * the basic mechanics of the system. Many Go classes can be used as is without 
	 * subclassing, so the documentation offers an uncluttered view of their public
	 * usage.</i></p>
	 * 
	 * <p><b>Introduction to Go</b></p>
	 * 
	 * <p>The Go ActionScript Animation Platform ("GOASAP") is a lightweight, portable 
	 * set of generic base classes for buliding AS3 animation tools. It provides structure 
	 * and core functionality, but does not define the specifics of animation-handling 
	 * classes like tweens.</p>
	 * 
	 * <p><i>Important: Store your custom Go classes in a package bearing your 
	 * own classpath, not in the core package! This will help avoid confusion 
	 * with other authors' work.</i></p>
	 * 
	 * <p>You may modify any class in the goasap package to suit your project's needs.</p>
	 *  
	 * <p>Go is a community initiative led by Moses Gunesch at 
	 * <a href="http://www.mosessupposes.com/" target="_top">MosesSupposes.com</a>. Please visit the 
	 * <a href="http://www.goasap.org/" target="_top">Go website</a> for more information.</p>
	 * 
	 * <p><b>GoEngine</b></p>
	 * 
	 * <p>GoEngine sits at the center of the Go system, and along with the IUpdatable 
	 * interface is the only required element for using Go. GoEngine references two other 
	 * interfaces for adding system-wide managers, IManager and IManageable.
	 * All other classes in the go package are merely one suggestion of how a 
	 * system could be structured within Go, and may be considered optional 
	 * elements. To create an API using the provided classes, you simply need 
	 * to extend the item classes LinearGo and PhysicsGo to create animation items.</p>
	 * 
	 * <p>GoEngine serves two purposes: first, it keeps a large system efficient 
	 * by stacking and running updates on blocks of items. Note that any IUpdatable 
	 * instance may specify its own pulseInterval; items with matching pulses 
	 * are grouped into queues for efficiency. Its second purpose is centralization. 
	 * By using a single hub for pulse-driven items of all types, management classes 
	 * can be attached to GoEngine to run processes across items. This is done voluntarily 
	 * by the end-user with <code>addManager()</code>, which keeps management entirely 
	 * compile-optional and extensible. See the documentation for <code>IManager</code>
	 * to learn more about Go's management architeture.</p>
	 * 
	 * <p>You normally don't need to modify this class to use Go. While Go items typically 
	 * only use <code>addItem</code> and <code>removeItem</code>, your project's code might
	 * use GoEngine to register managers, or to pause, resume or stop all Go animation in 
	 * a SWF at once.</p>
	 * 
	 * <p></i>{In the game of Go, the wooden playing board, or Goban, features a grid
	 *  on which black & white go-ishi stones are laid at its intersections.}</i></p>
	 * 
	 * @see org.goasap.items.LinearGo LinearGo
	 * @see org.goasap.interfaces.IManager IManager
	 * @author Moses Gunesch
	 */
	public class GoEngine 
	{
		// -== Constants ==-
		
		public static const INFO:String = "GoASAP 0.4.9 (c) Moses Gunesch, MIT Licensed.";
		
		// -== Settable Class Defaults ==-
		
		/**
		 * A pulseInterval that runs on the player's natural framerate, 
		 * which is often most efficient.
		 */
		public static const ENTER_FRAME	: int = -1;

		// -== Protected Properties ==-
		
		// Note: Various formats for item data have been experimented with including breaking the item lists out into
		// a GoEngineList class, which was nicer-looking but did not perform well. Since GoEngine doesn't normally
		// require active work, this less-pretty but efficient flat-data format was opted for. A minor weakness of this
		// format is its use of a Dictionary, which means update calls are not ordered like they would be with an Array.
		// The Dictionary stores items' pulseInterval values, which is safer than relying on items to not change them.
		// Tests also show that Dictionary performs faster than Array for accessing and deleting items.
		private static var managerTable : Object = new Object(); // registration list of IManager instances
		private static var managers : Array = new Array(); // ordered registration list of IManager instances
		private static var liveManagers : uint = 0;
		private static var timers : Dictionary = new Dictionary(false); // key: pulseInterval, value: Timer for that pulse
		private static var items : Dictionary = new Dictionary(false); // key: IUpdatable item, value: pulseInterval at add.
		private static var itemCounts : Dictionary = new Dictionary(false); // key: pulseInterval, value: item count for that pulse
		private static var pulseSprite : Sprite; // used for ENTER_FRAME pulse
		private static var paused : Boolean = false;
		
		// These additional lists enables caching of items that are added during the update cycle for the same pulse.
		// This prevents groups & sequences from going out of sync by ensuring that each cycle completes before new items are added.
		private static var lockedPulses : Dictionary = new Dictionary(false); // key: pulseInterval, value: true
		private static var delayedPulses : Dictionary = new Dictionary(false); // key: pulseInterval, value: true
		private static var addQueue : Dictionary = new Dictionary(false); // key: IUpdatable item, value: true
		
		// -== Public Class Methods ==-
		
		/**
		 * @param className		A string naming the manager class, such as "OverlapMonitor".
		 * @return				The manager instance, if registered.
		 * @see #addManager()
		 * @see #removeManager()
		 */
		public static function getManager(className:String) : IManager
		{
			return managerTable[ className ];
		}
		
		/**
		 * Enables the extending of this class' functionality with a tight
		 * coupling to an IManager. 
		 * 
		 * <p>Tight coupling is crucial in such a time-sensitive context; 
		 * standard events are too asynchronous. All items that implement 
		 * IManageable are reported to registered managers as they add and 
		 * remove themselves from GoEngine.</p>
		 * 
		 * <p>Managers normally act as singletons within the Go system (which 
		 * you are welcome to modify). This method throws a DuplicateManagerError 
		 * if an instance of the same manager class is already registered. Use a 
		 * try/catch block when calling this method if your program might duplicate 
		 * managers, or use getManager() to check for prior registration.</p>
		 * 
		 * @param instance	An instance of a manager you wish to add.
		 * @see #getManager()
		 * @see #removeManager()
		 */
		public static function addManager( instance:IManager ):void
		{
			var className:String = getQualifiedClassName(instance);
			className = className.slice(className.lastIndexOf("::")+2);
			if (managerTable[ className ]) {
				throw new DuplicateManagerError( className );
				return;
			}
			managerTable[ className ] = instance;
			managers.push(instance);
			if (instance is ILiveManager) liveManagers++;
		}
		
		/**
		 * Unregisters any manager set in <code>addManager</code>.
		 * 
		 * @param className		A string naming the manager class, such as "OverlapMonitor".
		 * @see #getManager()
		 * @see #addManager()
		 */
		public static function removeManager( className:String ):void
		{
			managers.splice(managers.indexOf(managerTable[ className ]), 1);
			if (managerTable[ className ] is ILiveManager)
				liveManagers--;
			delete managerTable[ className ]; // leave last
		}
		
		/**
		 * Test whether an item is currently stored and being updated by the engine.
		 * 
		 * @param item		Any object implementing IUpdatable
		 * @return			Whether the IUpdatable is in the engine
		 */
		public static function hasItem( item:IUpdatable ):Boolean
		{
			return (items[ item ]!=null);
		}
		
		/**
		 * Adds an IUpdatable instance to an update-queue corresponding to
		 * the item's pulseInterval property.
		 * 
		 * @param item		Any object implementing IUpdatable that wishes 
		 * 					to receive update calls on a pulse.
		 * 					
		 * @return			Returns false only if this item was already in the
		 * 					engine under the same pulse. (If an existing item is added
		 * 					but the pulseInterval has changed it will be removed,
		 * 					re-added, and true will be returned.)
		 * 					
		 * @see #removeItem()
		 */
		public static function addItem( item:IUpdatable ):Boolean
		{
			// Group items by pulse for efficient update cycles.
			var interval:int = item.pulseInterval;
			if (items[ item ]) {
				if (items[ item ] == item.pulseInterval)
					return false;
				else
					removeItem(item);
			}
			if (lockedPulses[ interval ]==true) { // this prevents items from being added during an update loop in progress.
				delayedPulses[ interval ] = true; // flags update to clear the queue when the in-progress loop completes.
				addQueue[ item ] = true; // for tightest syncing of item groups, read the documentation under GoItem.update().
			}
			items[ item ] = interval; // Tether item to original pulseint. Used in removeItem & setPaused(false).
			if (!timers[ interval ]) {
				addPulse( interval );
				itemCounts[ interval ] = 1;
			}
			else {
				itemCounts[ interval ] ++;
			}
			// Report IManageable instances to registered managers
			if (item is IManageable) {
				for each (var manager:IManager in managers)
					manager.reserve( item as IManageable );
			}
			return true;
		}
		
		/**
		 * Removes an item from the queue and removes its pulse timer if
		 * the queue is depleted.
		 * 
		 * @param item		Any IUpdatable previously added that wishes 
		 * 					to stop receiving update calls.
		 * 					
		 * @return			Returns false if the item was not in the engine.
		 * 
		 * @see #addItem()
		 */
		public static function removeItem( item:IUpdatable ):Boolean
		{
			if (items[ item ]==null)
				return false;
			var interval: int = items[ item ];
			if ( -- itemCounts[ interval ] == 0 ) {
				removePulse( interval );
				delete itemCounts[ interval ];
			}
			delete items[ item ];
			delete addQueue[ item ]; // * see note following update
			// Report IManageable item removal to registered managers.
			if (item is IManageable) {
				for each (var manager:IManager in managers) 
					manager.release( item as IManageable );
			}
			return true;
		}
		
		/**
		 * Removes all items and resets the engine, 
		 * or removes just items running on a specific pulse.
		 * 
		 * @param pulseInterval		Optionally filter by a specific pulse 
		 * 							such as ENTER_FRAME or a number of milliseconds.
		 * @return					The number of items successfully removed.
		 * @see #removeItem()
		 */
		public static function clear(pulseInterval:Number = NaN) : uint
		{
			var all:Boolean = (isNaN(pulseInterval));
			var n:Number = 0;
			for (var item:Object in items) {
				if (all || items[ item ]==pulseInterval)
					if (removeItem(item as IUpdatable)==true)
						n++;
			}
			return n;
		}
		
		/**
		 * Retrieves number of active items in the engine 
		 * or active items running on a specific pulse.
		 * 
		 * @param pulseInterval		Optionally filter by a specific pulseInterval
		 *							such as ENTER_FRAME or a number of milliseconds.
		 * 
		 * @return					Number of active items in the Engine.
		 */
		public static function getCount(pulseInterval:Number = NaN) : uint
		{
			if (!isNaN(pulseInterval))
				return (itemCounts[pulseInterval]);
			var n:Number = 0;
			for each (var count: int in itemCounts)
				n += count;
			return n;
		}
		
		/**
		 * @return			The paused state of engine.
		 * @see #setPaused()
		 */
		public static function getPaused() : Boolean {
			return paused;
		}
		
		/**
		 * Pauses or resumes all animation globally by suspending processing,
		 * and calls pause() or resume() on each item with those methods. 
		 * 
		 * <p>The return value only reflects how many items had pause() or resume()
		 * called on them, but the GoEngine.getPaused() state will change if any 
		 * pulses are suspended or resumed.</p>
		 * 
		 * @param pause				Pass false to resume if currently paused.
		 * @param pulseInterval		Optionally filter by a specific pulse 
		 * 							such as ENTER_FRAME or a number of milliseconds.
		 * @return					The number of items on which a pause() or resume()
		 * 							method was called (0 doesn't necessarily reflect
		 * 							whether the GoEngine.getPaused() state changed, it
		 * 							may simply indicate that no items had that method).
		 * @see #resume()
		 */
		public static function setPaused(pause:Boolean=true, pulseInterval:Number = NaN) : uint
		{
			if (paused==pause) return 0;
			var n:Number = 0;
			var pulseChanged:Boolean = false;
			var all:Boolean = (isNaN(pulseInterval));
			var method:String = (pause ? "pause" : "resume");
			for (var item:Object in items) {
				var pulse:int = (items[item] as int);
				if (all || pulse==pulseInterval) {
					pulseChanged = (pulseChanged || (pause ? removePulse(pulse) : addPulse(pulse)));
					// call pause or resume on the item if it has such a method.
					if (item.hasOwnProperty(method)) {
						if (item[method] is Function) {
							item[method].apply(item);
							n++;
						}
					}
				}
			}
			if (pulseChanged)
				paused = pause;
			return n;
		}
		
		// -== Private Class Methods ==-
		
		/**
		 * Executes the update queue corresponding to the dispatcher's interval.
		 * 
		 * @param event			TimerEvent or Sprite ENTER_FRAME Event
		 */
		private static function update(event:Event) : void 
		{
			var currentTime:Number = getTimer();
			var pulse:int = (event is TimerEvent ? ( event.target as Timer ).delay : ENTER_FRAME);
			lockedPulses[ pulse ] = true;
			var doLiveUpdate:Boolean = (liveManagers > 0);
			var updated:Array;
			if (doLiveUpdate) updated = []; // syncs the live manager list to items actually updated
			for (var item:* in items) {
				if (items[ item ]==pulse && !addQueue[ item ]) {
					(item as IUpdatable).update(currentTime);
					if (doLiveUpdate) updated.push(item);
				}
			}
			lockedPulses[ pulse ] = false;
			if (delayedPulses[ pulse ]) {
				for (item in addQueue) 
					delete addQueue[ item ];
				delete delayedPulses[ pulse ];
			}
// updateAfterEvent() should not be needed as long as items follow tight-syncing instructions in GoItem.update() documentation.
//			if (pulse!=ENTER_FRAME) (event as TimerEvent).updateAfterEvent();
			if (doLiveUpdate)
				for each (var manager:Object in managers)
					if (manager is ILiveManager)
						(manager as ILiveManager).onUpdate(pulse, updated, currentTime);  // * see note
		}
// * note: In one rare case that has not been reported yet but is theoretically possible, the 'updated' list 
// passed could contain already-released items. This could only happen if the item is removed & released 
// just after the main update cycle but before the the doLiveUpdate() routine runs. If you encounter this issue
// please report it to the GoASAP mailing list, it's too involved to bother with before it's a problem.

		/**
		 * Creates new timers when a previously unused interval is specified,
		 * and tracks the number of items associated with that interval.
		 * 
		 * @param pulse			The pulseInterval requested
		 * @return				Whether a pulse was added
		 */
		private static function addPulse(pulse : int) : Boolean
		{
			if (pulse==ENTER_FRAME) {
				if (!pulseSprite) {
					timers[ENTER_FRAME] = pulseSprite = new Sprite();
					pulseSprite.addEventListener(Event.ENTER_FRAME, update);
				}
				return true;
			}
			var t:Timer = timers[ pulse ] as Timer;
			if (!t) {
				t = timers[ pulse ] = new Timer(pulse);
				(timers[ pulse ] as Timer).addEventListener(TimerEvent.TIMER, update);
				t.start();
				return true;
			}
			return false;
		}
		
		/**
		 * Tracks whether a removed item was the last one using a timer 
		 * and if so, removes that timer.
		 * 
		 * @param pulse			The pulseInterval corresponding to an item being removed.
		 * @return				Whether a pulse was removed
		 */
		private static function removePulse(pulse : int) : Boolean
		{
			if (pulse==ENTER_FRAME) {
				if (pulseSprite) {
					pulseSprite.removeEventListener(Event.ENTER_FRAME, update);
					delete timers[ ENTER_FRAME ];
					pulseSprite = null;
					return true;
				}
			}
			var t:Timer = timers[ pulse ] as Timer;
			if (t) {
				t.stop();
				t.removeEventListener(TimerEvent.TIMER, update);
				delete timers[ pulse ];
				return true;
			}
			return false;
		}
	}
}
