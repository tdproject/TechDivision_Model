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
require_once 'TechDivision/Util/AbstractDataSource.php';
require_once 'TechDivision/Util/XMLDataSource.php';
require_once 'TechDivision/Logger/Logger.php';
require_once 'TechDivision/Logger/Interfaces/Logger.php';
require_once 'TechDivision/Collections/HashMap.php';
require_once 'TechDivision/Collections/CollectionUtils.php';
require_once 'TechDivision/Model/Container/Utils.php';
require_once 'TechDivision/Model/Container/Entity.php';
require_once 'TechDivision/Model/Container/Session.php';
require_once 'TechDivision/Model/Manager/MySQLi.php';
require_once 'TechDivision/Model/Exceptions/ContainerConfigurationException.php';
require_once 'TechDivision/Model/Exceptions/InvalidDataSourceTypeException.php';
require_once 'TechDivision/Model/Configuration/Interfaces/Container.php';
require_once 'TechDivision/Model/Configuration/CachedXML.php';
require_once 'TechDivision/Model/Configuration/XML.php';
require_once 'TechDivision/Model/Predicates/FindSlaveByNamePredicate.php';

/**
 * This is the container that manages the epbs.
 *
 * The DataSource for the session management MUST have
 * the autocommit flag set to true, else the session
 * management won't work correctly.
 *
 * @package TechDivision_Model
 * @author Tim Wagner <t.wagner@techdivision.com>
 * @copyright TechDivision GmbH
 * @link http://www.techdivision.com
 * @license GPL
 */
class TechDivision_Model_Container_MockImplementation
    extends TechDivision_Lang_Object
    implements TechDivision_Model_Interfaces_Container {

	/**
	 * Holds the actual session id.
	 * @var string
	 */
	protected $_sessionId = null;

    /**
     * The constructor initializes the configuration, checks
     * if the necessary tables exists and creates it if not.
	 *
     * @param string $sessionId Holds the id of the session to use
     * @param TechDivision_Model_Container_Configuration_Interfaces_Container $containerConfiguration
     * 		Holds the container configuration
     * @return void
     */
    protected function __construct(
        $sessionId = null,
        TechDivision_Model_Container_Configuration_Interfaces_Container $containerConfiguration = null) {
		// set the session ID
		$this->_sessionId = $sessionId;
    }

	/**
	 * This method returns the instance
	 * of the container as singleton.
	 *
	 * @param string $sessionId Holds the id of the actual session
     * @param TechDivision_Model_Configuration_Interfaces_Container $containerConfiguration
     * 		Holds the container configuration
	 * @return TechDivision_Model_Container_Implementation
	 * 		Returns the instance of the container as singleton
	 * @see TechDivision_Model_Interfaces_Container::getContainer(
     *	   		$applicationName = null,
     *	   		$sessionId = null,
     *	    	TechDivision_Model_Configuration_Interfaces_Container $containerConfiguration = null)
	 */
	public static function getContainer(
	    $sessionId = null,
	    TechDivision_Model_Configuration_Interfaces_Container $containerConfiguration = null) {
		return new TechDivision_Model_Container_MockImplementation(
		    $sessionId,
		    $containerConfiguration
		);
	}

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
     * @see TechDivision_Model_Interfaces_Container::lookup($name, $key = null, $refresh = false)
     */
    public function lookup($name, $key = null, $refresh = false)
    {
		return;
    }

    /**
     * This method initializes a new entity identified
     * by the passed name registers the entity in the
     * internal structure and returns a reference to it.
     *
     * @param string $name The name of the entity the should be returned
     * @return TechDivision_Model_Interfaces_Entity
     * 		Holds a reference of the entity
     * @see TechDivision_Model_Interfaces_Container::register($name)
     */
    public function register($name)
    {
        return;
    }

    /**
     * This method returns the database manager used
     * by the entities with read/write access.
     *
     * @return TechDivision_Model_Interfaces_Manager Returns the database manager
     * @see TechDivision_Model_Interfaces_Container::getMasterManager()
     */
    public function getMasterManager()
    {
    	return;
    }

    /**
     * This method returns the database manager used
     * by the entities with read/write access.
     *
     * @return TechDivision_Model_Configuration_Interfaces_Manager
     * 		Returns the database manager
     * @see TechDivision_Model_Interfaces_Container::getSessionManager()
     */
    public function getSessionManager()
    {
    	return;
    }

    /**
     * This method returns one of the database managers used
     * by the entities with read only access.
     *
     * @param string $name Holds the name of the slave manager to use
     * @return TechDivision_Model_Configuration_Interfaces_Manager
     * 		Returns one the database managers with read only access
     * @return TechDivision_Model_Interfaces_Container::getSlaveManager($name = '')
     */
    public function getSlaveManager($name = '')
    {
    	return;
    }

    /**
	 * This method returns all slave managers.
	 *
	 * @return array Holds all slave database managers
	 * @see TechDivision_Model_Interfaces_Container::getSlaveManagers()
	 */
    public function getSlaveManagers()
    {
    	return;
    }

    /**
     * This method returns the dedicated database manager used
     * by the entities with the passed name and with read only access.
     *
     * @return TechDivision_Model_Interfaces_Manager
     * 		Returns the dedicated database manager with the requested name
     * @see TechDivision_Model_Interfaces_Container::getDedicatedManager($name)
     */
    public function getDedicatedManager($name)
    {
    	return;
    }

    /**
     * This method saves the session data in the database and
     * runs the garbage collector.
     *
     * @return void
     * @see TechDivision_Model_Interfaces_Container::saveSession()
     */
    public function saveSession()
    {
        return;
    }

    /**
     * This method forces the container to use the master
     * database manager only when requesting a slave.
     *
     * @return void
     * @see TechDivision_Model_Interfaces_Container::useMasterOnly()
     */
    public function useMasterOnly()
    {
		return;
    }

    /**
	 * This method returns the session id of the
	 * actual request.
	 *
	 * @return string Holds the requested session id
	 * @see TechDivision_Model_Interfaces_Container::getSessionId()
	 */
    public function getSessionId()
    {
    	return $this->_sessionId;
    }
}