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
package org.goasap.interfaces {
	
	/**
	 * Makes an object compatible with <code>GoEngine.addManager()</code>
	 * <font color="#CC0000">[This section updated recently!]</font></p>
	 * <p><b>What are managers?</b></p>
	 * 
	 * <p>Tweens and other animation items are not aware of other items while they 
	 * run; by contrast, manager classes can monitor and interact with many active 
	 * items at once. <code>OverlapMonitor</code>, a manager shipped with GoASAP, 
	 * prevents situations like two different tween instances trying to animate the 
	 * x property of a single sprite at the same time. This type of conflict needs 
	 * a system-level manager that can look at multiple items as they operate. Managers 
	 * can be used to automate any  general process within an animation system. 
	 * This sounds dry, but it can be a creative opportunity as well:  imagine a manager
	 * that automatically motion-blurs targets based on their velocity, for example. 
	 * Working at the system level gives you power that you don't have at the GoItem level, 
	 * and opens up a new range of possibilites. For example,  a custom game engine would 
	 * be built primarily at the management level.</p>
	 * 
	 * <p>There is a distinct difference in the Go system between <i>managers</i> and 
	 * <i>utilities</i>, although both typically work with batches of Go items. Utilities 
	 * are tools designed to be directly used at a project level, such as a sequence or 
	 * animation syntax parser (even <code>GoItems</code> like tween classes are essentially 
	 * utilities). In contrast, managers are self-sufficient entities that, once registered 
	 * to <code>GoEngine</code>, operate in the background without requiring any direct 
	 * interaction at runtime.</p>
	 * 
	 * <p><b>About Go's Decoupled Management system</b></p>
	 * 
	 * <p>The downside of managers in general is that they can add overhead as they perform 
	 * their additional processes, slowing your system down. Prefab tween engines usually "bake" 
	 * management features into their core code, locking you into any processing cost incurred as 
	 * well as whichever set of features the author decided were important. GoASAP's management 
	 * layer is designed specifically to solve these problems, and  is GoASAP's most unique 
	 * architectural feature. It leverages the centralized pulse engine as a registration hub 
	 * for any number of managers, then leaves it up to the end user which managers to register 
	 * per project.</p>
	 * 
	 * <p>This layer stays <i>optional</i> at all levels: it is optional to make tweens or other 
	 * animation items manageable in the first place (by implementing <code>IManageable</code>), 
	 * but it is very easy to write your own custom managers (that implement <code>IManager</code>). 
	 * Then even after implementation, it still remains optional for the end-user whether to add 
	 * any particular manager to GoEngine at runtime. By choosing not to add any managers if they 
	 * aren't needed in a project, Go can stay ultimately streamlined and limit its footprint to 
	 * just code that is used. It's also very easy to create custom managers to meet the needs 
	 * of a challenging project. You can activate these custom tools at runtime this time, then 
	 * ignore them until needed again. This allows you to tie your custom program code very tightly 
	 * into your animation engine, but keeps those customizations neatly 'decoupled.'</p>
	 * 
	 * <p><b>Go Manager types</b></p>
	 * 
	 * <p>Go currently provides two manager interfaces to choose from, <code>IManager</code> and 
	 * <code>ILiveManager</code>. An <code>IManager</code> is notified every time any <code>IManageable</code> 
	 * item is added or removed from GoEngine. This is the interface used by <code>OverlapMonitor</code> 
	 * for example, which only needs to detect conflicts as new items are added. The second interface, 
	 * <code>ILiveManager</code>, is for situations where you want a manager to actively handle items 
	 * as they update.</p>
	 * 
	 * <p><b>Implementing <code>IManager</code></b></p>
	 * 
	 * <p>This interface has two methods that are called by <code>GoEngine</code>, <code>reserve()</code> 
	 * and <code>release()</code>. The first method is called when any item that implements <code>IManageable</code> 
	 * is added to the engine, and the second is called when such an item is removed. This means that 
	 * instances of a tween class that implements  <code>IManageable</code>, for example, can be 
	 * trapped by the manager while their play cycle is active. Managers can do whatever they want 
	 * with the items, but the  <code>IManageable</code> interface ensures that they can always get 
	 * the <i>active animation targets and properties</i> from the item, determine <i>property overlap</i> 
	 * between items, and ask items to <i>stop playing</i> when necessary. There are no rules for what you 
	 * write in the  <code>reserve()</code> or <code>release()</code> methods, except that you should not 
	 * call <code>release()</code> directly from <code>reserve()</code>, but instead ask an item to stop via 
	 * a <code>IManageable.releaseHandling()</code> call. <code>GoEngine</code> will call <code>release()</code> 
	 * on the manager once the item has truly been stopped.</p>
	 * 
	 * <p>You can also extend <code>IManageable</code> to add special functionality that a manager might use 
	 * on an item, or even just to create a new marker datatype without adding any custom methods. This enables 
	 * your custom managers to sniff for a particular interface type in order to determine which items to store, 
	 * monitor, or alter. The general rule is that items like tweens are considered working code, so you might 
	 * end up changing the management implementations on different sets of tweens based on your project needs. 
	 * Regardless of implementation on the manageable side, managers will remain decoupled in that they need 
	 * to be registered into <code>GoEngine</code> to be compiled and used in a project. As a general rule 
	 * you should try to have managers and managed items only reference each other via interfaces so that no 
	 * classes are forced to be compiled until they are used directly in a project.</p>
	 * 
	 * @see IManageable
	 * @see org.goasap.managers.OverlapMonitor OverlapMonitor
	 * @see org.goasap.GoEngine#addManager GoEngine.addManager
	 * 
	 * @author Moses Gunesch
	 */
	public interface IManager 
	{
		
		/**
		 * GoEngine reporting that an IManageable is being added to its pulse list.
		 * 
		 * @param handler		IManageable to query
		 */
		function reserve(handler:IManageable):void;
		
		
		/**
		 * GoEngine reporting that an IManageable is being removed from its pulse list.
		 * 
		 * <p>This method should NOT directly stop the item, stopping an item results in 
		 * a release() call from GoEngine. This method should simply remove the item from 
		 * any internal lists and unsubscribe all listeners on the item.</p> 
		 */
		function release(handler:IManageable):void;
	}
}
