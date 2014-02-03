<?php

/**
 * License: GNU General Public License
 *
 * Copyright (c) 2009 TechDivision GmbH.  All rights reserved.
 * Note: Original work copyright to respective authors
 *
 * This file is part of TechDivision GmbH - Connect.
 *
 * TechDivision_Model is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * TechDivision_Model is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 * USA.
 *
 * @package TechDivision_Model
 */

require_once 'TechDivision/Lang/Object.php';
require_once 'TechDivision/Model/Interfaces/Session.php';

/**
 * This is the container to handle the information of
 * a storable in the container.
 *
 * @package TechDivision_Model
 * @author Tim Wagner <t.wagner@techdivision.com>
 * @copyright TechDivision GmbH
 * @link http://www.techdivision.com
 * @license GPL
 */
class TechDivision_Model_Container_Session extends TechDivision_Lang_Object
{

	/**
	 * The cache tag.
	 * @var string
	 */
	const CACHE_TAG = 'container_session';

    /**
     * Holds the array with the beans.
     * @var array
     */
    protected $_sessions = array();

    /**
     * This method adds a session to
     * the internal array.
     *
     * @param TechDivision_Model_Interfaces_Session $session
     * 		The session bean that should be registered in the internal array
     */
    public function add(TechDivision_Model_Interfaces_Session $session)
    {
        $this->_sessions[$session->getClass()] = $session;
    }

    /**
     * This method looks in the internal hash map
     * for the storable with the passed key. If
     * it is found the storable is returned.
     *
     * @param string $class
     * 		Holds the class name of the session that should be returned
     * @return TechDivision_Model_Interfaces_Session
     * 		Returns the session bean with the passed class name
     * @throws Exception
     * 		Is thrown if no session with the passed class name is
     * 		registered in the container
     */
    public function lookup($class)
    {
        if (!array_key_exists($class, $this->_sessions)) {
        	throw new Exception(
        		'Class ' . $class . ' not registered in this session'
        	);
        }
        return $this->_sessions[$class];
    }

    /**
     * Removes the passed session bean from the container.
     *
     * @param string $class
     * 		The class name of the session bean that has to be remove from the container
     */
    public function remove($class)
    {
    	if ($this->exists($class)) {
        	unset($this->_sessions[$class]);
    	}
    }

    /**
     * This method checks if the session with the passed classname
     * already exists in the container.
     *
     * @param string $class Holds the classname the check for
     * @return boolean TRUE if the class already exists in the container
     */
    public function exists($class)
    {
		return array_key_exists($class, $this->_sessions);
    }
}