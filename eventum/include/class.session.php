<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2009 Sun Microsystem Inc.                       |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+
//
// @(#) $Id: class.session.php 3822 2009-02-10 06:35:01Z glen $
//

require_once(APP_INC_PATH . "class.error_handler.php");
require_once(APP_INC_PATH . "class.setup.php");

/**
 * Wrapper class for sessions. This is an initial bare bones implementation.
 * Additional methods will be later as needed.
 *
 * @version 1.0
 * @author Bryan Alsdorf <bryan@mysql.com>
 */

class Session
{
    /**
     * Sets the passed variable in the session using the specified name.
     * 
     * @access  public
     * @param   string $name Name to store variable under.
     * @param   mixed $var Variable to store in session.
     */
    function set($name, $var)
    {
        GLOBAL $_SESSION;
        $_SESSION[$name] = $var;
    }
    
    
    /**
     * Returns the session variable specified by $name
     * 
     * @access  public
     * @param   string $name The name of variable to be returned.
     * @return  mixed The session variable.
     */
    function get($name)
    {
        GLOBAL $_SESSION;
        return @$_SESSION[$name];
    }
    
    
    /**
     * Returns true if the session variable $name is set, false otherwise.
     * 
     * @access  public
     * @param   string $name The name of the variable to check.
     * @return  boolean If the variable is set
     */
    function is_set($name)
    {
        GLOBAL $_SESSION;
        return isset($_SESSION[$name]);
    }


    /**
     * Initialize the session
     *
     * @access  public
     * @param   integer $usr_id The ID of the user
     */
    function init($usr_id)
    {
        @session_start();

        // clear all old session variables
        $_SESSION = array();

        // regenerate ID to prevent session fixation
        session_regenerate_id();

        // set the IP in the session so we can check it later
        $_SESSION['login_ip'] = $_SERVER['REMOTE_ADDR'];

        // store user ID in session
        $_SESSION['usr_id'] = $usr_id;// XXX: Should we perform checks on this usr ID before accepting it?
    }


    /**
     * Verify that the current request to use the session has the same IP address as the request that started it.
     *
     * @access  public
     * @param   integer $usr_id The ID of the user
     */
    function verify($usr_id)
    {
        @session_start();

        // Don't check the IP of the session, since this caused problems for users that use a proxy farm that uses
        // a different IP address each page load.
        if (!Session::is_set('usr_id')) {
            Session::init($usr_id);
        }
    }


    /**
     * Destroys the current session
     */
    function destroy()
    {
        @session_destroy();
    }
}
