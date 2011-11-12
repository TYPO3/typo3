/**
 * Copyright (c) 2008 Moses Gunesch
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
package org.goasap.interfaces {
	import org.goasap.interfaces.IManager;
	
	/**
	 * Instances receive a callback from GoEngine after each update cycle, 
	 * allowing managers to more easily perform ongoing processes during animation. 
	 * 
	 * <p><font color="#CC0000">[This is a more advanced manager interface, so if 
	 * you are just getting started with Go's management system it is suggested that 
	 * you focus on <code>IManager</code> & <code>IManageable</code>, and save this 
	 * section for when you need it.]</font> </p>
	 * 
	 * <p>Hypothetical examples:</p>
	 * <ul>
	 * <li>An updater class that refreshes (rerenders) a 3D scene after all 
	 * animations have processed each pulse.</li>
	 * <li>A hitTest manager that allows all items to update their positions 
	 * first, then tests for hits between them.</li>
	 * </ul>
	 * <p>Each <code>ILiveManager</code> receives a special onUpdate() callback 
	 * after GoEngine completes each pulse cycle for any particular pulseInterval. 
	 * This callback receives three things: the pulseInterval associated with the 
	 * cycle, an array containing the items updated, and the synced current-time value 
	 * that was sent to all the items as update() was called. (Background: GoEngine 
	 * stores different lists for every different pulseInterval specified by animation 
	 * items. Usually users will stick to a single pulseInterval but at times it can 
	 * be beneficial to run some animations slower than others – such as the readouts 
	 * in a spaceship game's cockpit which don't need to refresh as often and can free 
	 * up processing power for the game if they don't.)</p>
	 * 
	 * <p>The list of updated items only includes items actually updated, which at 
	 * times can differ slightly from the items that have been added to GoEngine and 
	 * sent to the manager's reserve() method. (Background: when items are added to 
	 * GoEngine during its update cycle, it defers updating them until the next pulse 
	 * so as not to disrupt the cycle in progress.) Therefore, even though <code>ILiveManager</code> 
	 * extends <code>IManager</code> and contains reserve() and release() methods, 
	 * those methods are often not needed here, since you can filter and make use of 
	 * the incoming array of updated items on each update. This can also relieve such 
	 * managers from needing to store and manage complex handler lists (as 
	 * <code>OverlapMonitor</code> does).</p>
	 * 
	 * <p><code>ILiveManager</code> instances registered using <code>GoEngine.addManager()</code> 
	 * are stored in an ordered list. You can control the priority of updates in a 
	 * program by adding certain managers before others.</p>
	 * 
	 * @see IManager
	 * @see IManageable
	 * @see org.goasap.GoEngine#addManager GoEngine.addManager()
	 * 
	 * @author Moses Gunesch
	 */
	public interface ILiveManager extends IManager
	{
		
		/**
		 * GoEngine pings this function after each update() cycle for each pulse.
		 * 
		 * @param pulseInterval	The pulse interval for this update cycle (-1 is ENTER_FRAME)
		 * @param handlers		The list of handlers actually updated during this cycle
		 * @param currentTime	The clock time that was passed to items during update
		 */
		function onUpdate(pulseInterval:int, handlers:Array, currentTime : Number):void;
	}
}
