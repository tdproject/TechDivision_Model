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

require_once 'TechDivision/Model/Configuration/Interfaces/Container.php';

/**
 * Interface of all Container objects.
 *
 * @package TechDivision_Model
 * @author Tim Wagner <t.wagner@techdivision.com>
 * @copyright TechDivision GmbH
 * @link http://www.techdivision.com
 * @license GPL
 */
interface TechDivision_Model_Interfaces_Container
{

	/**
	 * This method returns the instance
	 * of the container as singleton.
  	 *
	 * @param string $sessionId Holds the id of the actual session
     * @param TechDivision_Model_Configuration_Interfaces_Container $containerConfiguration
     * 		Holds the container configuration
	 * @return TechDivision_Model_Container_Interfaces_Container
	 * 		Returns the instance of the container as singleton
	 */
	public static function getContainer(
	    $sessionId = null,
	    TechDivision_Model_Configuration_Interfaces_Container $containerConfiguration = null);

    /**
     * This method returns the dedicated database manager used
     * by the entities with the passed name and with read only access.
     *
     * @return TechDivision_Model_Interfaces_Manager
     * 		Returns the dedicated database manager with the requested name
     */
    public function getDedicatedManager($name);

    /**
     * This method returns the database manager used
     * by the entities with read/write access.
     *
     * @return TechDivision_Model_Interfaces_Manager Returns the database manager
     */
    public function getMasterManager();

    /**
	 * This method returns the session id of the
	 * actual request.
	 *
	 * @return string Holds the requested session id
	 */
    public function getSessionId();

    /**
     * This method returns one of the database managers used
     * by the entities with read only access.
     *
     * @param string $name Holds the name of the slave manager to use
     * @return TechDivision_Model_Configuration_Interfaces_Manager
     * 		Returns one the database managers with read only access
     */
    public function getSlaveManager($name = '');

    /**
	 * This method returns all slave managers.
	 *
	 * @return array Holds all slave database managers
	 */
    public function getSlaveManagers();

    /**
     * This method looks in the internal structure if an entity
     * with the passed name is registered. If yes, it checks if
     * the entity with the passed key is already initialized.
     * If yes it returns the initialized entity, if not, a new
     * entity is initialized, the data is loaded and the new
     * entity is returned.
     *
     * @param string $name The name of the epb that should be returned
     * @param $integer $key The key of the dataset the epb should be initialized
     * @param boolean $refresh
     * 		TRUE if the entity should be refreshed, else FALSE (default)
     * @return TechDivision_Model_Interfaces_Entity Holds the initialized entity
     * @throws TechDivision_Model_Exceptions_FindException
     * 		Is thrown if the entity could not be loaded
     * @throws TechDivision_Model_Exceptions_ContainerConfigurationException
     * 		Is thrown if the entity is not registered in the container
     */
    public function lookup($name, $key = null, $refresh = false);

    /**
     * This method initializes a new entity identified
     * by the passed name registers the entity in the
     * internal structure and returns a reference to it.
     *
     * @param string $name The name of the entity the should be returned
     * @return TechDivision_Model_Interfaces_Entity
     * 		Holds a reference of the entity
     */
    public function register($name);

    /**
     * This method forces the container to use the master
     * database manager only when requesting a slave.
     *
     * @return void
     */
    public function useMasterOnly();

}