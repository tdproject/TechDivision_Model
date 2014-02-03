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
require_once 'TechDivision/Util/Interfaces/DataSource.php';
require_once 'TechDivision/Logger/Logger.php';
require_once 'TechDivision/Collections/ArrayList.php';
require_once 'TechDivision/Model/Container/Implementation.php';
require_once 'TechDivision/Model/Manager/Transaction.php';
require_once 'TechDivision/Model/Exceptions/ConnectionException.php';
require_once 'TechDivision/Model/Exceptions/QueryException.php';
require_once 'TechDivision/Model/Exceptions/PrepareException.php';
require_once 'TechDivision/Model/Exceptions/ExecuteException.php';
require_once 'TechDivision/Model/Exceptions/UnknownEventException.php';
require_once 'TechDivision/Model/Interfaces/Manager.php';

/**
 * This is the manager that acts as wrapper for the MySQLi class.
 *
 * @package TechDivision_Model
 * @author Tim Wagner <t.wagner@techdivision.com>
 * @copyright TechDivision GmbH
 * @link http://www.techdivision.com
 * @license GPL
 */
class TechDivision_Model_Manager_MySQLi
    extends TechDivision_Lang_Object
    implements TechDivision_Model_Interfaces_Manager {

	/**
	 * Holds the event name for event that was triggered before
	 * disconnecting from the database.
	 * @var string
	 */
	const EVENT_BEFORE_DISCONNECT = 'beforeDisconnect';

	/**
	 * Holds the event name for event that was triggered before a
	 * query was sent to the database.
	 * @var string
	 */
	const EVENT_BEFORE_QUERY = 'beforeQuery';

	/**
	 * Holds the event name for event that was triggered before a
	 * prepared statement was executed.
	 * @var string
	 */
	const EVENT_BEFORE_EXECUTE = 'beforeExecute';

	/**
	 * Holds the event name for event that was triggered before a
	 * prepared statement was prepared.
	 * @var string
	 */
	const EVENT_BEFORE_PREPARE = 'beforePrepare';

	/**
	 * Holds the event name for event that was triggered after
	 * disconnecting from the database.
	 * @var string
	 */
	const EVENT_AFTER_DISCONNECT = 'afterDisconnect';

	/**
	 * Holds the event name for event that was triggered after a query
	 * was sent to the database.
	 * @var string
	 */
	const EVENT_AFTER_QUERY = 'afterQuery';

	/**
	 * Holds the event name for event that was triggered after a prepared
	 * statement was executed.
	 * @var string
	 */
	const EVENT_AFTER_EXECUTE = 'afterExecute';

	/**
	 * Holds the event name for event that was triggered after a prepared
	 * statement was prepared.
	 * @var string
	 */
	const EVENT_AFTER_PREPARE = 'afterPrepare';

    /**
     * Holds the array with the registered events.
     * @var array
     */
    protected $_registeredEvents = array(
        TechDivision_Model_Manager_MySQLi::EVENT_BEFORE_DISCONNECT => '__destruct',
	    TechDivision_Model_Manager_MySQLi::EVENT_AFTER_DISCONNECT => '__destruct',
	    TechDivision_Model_Manager_MySQLi::EVENT_BEFORE_QUERY => 'query',
	    TechDivision_Model_Manager_MySQLi::EVENT_AFTER_QUERY => 'query',
	    TechDivision_Model_Manager_MySQLi::EVENT_BEFORE_EXECUTE => 'execute',
	    TechDivision_Model_Manager_MySQLi::EVENT_AFTER_EXECUTE => 'execute',
	    TechDivision_Model_Manager_MySQLi::EVENT_BEFORE_PREPARE => 'prepare',
	    TechDivision_Model_Manager_MySQLi::EVENT_AFTER_PREPARE => 'prepare'
	);

    /**
     * This member holds a reference to the container.
     * @var TechDivision_Model_Interfaces_Container
     */
    protected $_container = null;

    /**
     * This member holds the instance of the MySQLi class.
     * @var MySQLi
     */
    protected $_db = null;

    /**
     * Holds the array with the events to trigger.
     * @var array
     */
    protected $_events = array();

    /**
     * Holds the connection status TRUE if the database connection is established, else false.
     * @var boolean
     */
    protected $_connected = false;

	/**
	 * Holds the logger instance.
	 * @var TechDivision_Logger_Interfaces_Logger
	 */
	protected $_logger = null;

	/**
	 * The data source to use for creating the database connection.
	 * @var TechDivsiion_Util_Interfaces_DataSource
	 */
	protected $_dataSource = null;

    /**
     * The constructor needs a object of type DataSource to
	 * build the necessary dsn for the database connection.
     *
     * @param TechDivision_Model_Interfaces_Container
     * 		Holds a reference to the container
     * @param TechDivision_Util_Interfaces_DataSource $dataSource
     * 		The object with the connection information
     * @return void
     */
	public function __construct(
	    TechDivision_Model_Interfaces_Container $container,
	    TechDivision_Util_Interfaces_DataSource $dataSource) {
        // initialize the logger
        $this->_logger = TechDivision_Logger_Logger::forObject(
            $this
        );
        // sets the container reference
        $this->_container = $container;
        // set the data source
        $this->_dataSource = $dataSource;
		// switch magic qoutes off to force database manager to escape
		// values if necessary
		ini_set('magic_quotes_gpc', 'off');
    }

    /**
     * The destructor closes the database connection
     * and destroys the objects.
     *
     * @return void
     */
    public function __destruct()
    {
		// trigger the event before disconnect
		$this->_triggerEvent(
		    TechDivision_Model_Manager_MySQLi::EVENT_BEFORE_DISCONNECT
		);
		// destroy the connection instance
		unset($this->_db);
		// trigger the event after disconnect
		$this->_triggerEvent(
		    TechDivision_Model_Manager_MySQLi::EVENT_AFTER_DISCONNECT
		);
    }

    /**
     * This methdod establishes the connection to the
     * database specified by the data source.
     *
     * @return void
     * @throws TechDivision_Model_Exceptions_ConnectionException
     * 		Is thrown if the connection can't be established
     */
    protected function _connect()
    {
		// check if a connection is already esablished
		if (!$this->isConnected()) { // if not, try to establish one
 			// get the DSN
 			$ds = $this->getDataSource();
			$dsn = $ds->getConnectionString();
			// log the DSN
			$this->_getLogger()->debug(
				'Try to connect with ' . $dsn .
				' on data source ' . $this->getDataSourceName(),
			    __LINE__
			);
            // connect to the database
            $this->_db = new MySQLi(
                $ds->getHost(),
                $ds->getUser(),
                $ds->getPassword(),
                $ds->getDatabase(),
                $ds->getPort()
            );
            // always check if there was an error
            if (($errorNumber = mysqli_connect_errno())) {
                throw new TechDivision_Model_Exceptions_ConnectionException(
                    mysqli_connect_error()
                );
            }
			// set the connected flag
			$this->_connected = true;
			// turn autocommit off
			$this->_db->autocommit($ds->getAutocommit());
			// set the default connection encoding
			$this->_db->set_charset($ds->getEncoding());
            // log the the connection is successfully
            $this->_getLogger()->debug(
            	'Successfully connected with dsn ' . $dsn .
            	' on data source ' . $this->getDataSourceName(),
                __LINE__
            );
		}
    }

    /**
     * Returns the connection status.
     *
     * @return boolean
     * 		Is TRUE if the database connection is established, else FALSE
     * @see TechDivision_Model_Interfaces_Manager::isConnected()
     */
    public function isConnected()
    {
    	return $this->_connected;
    }

    /**
     * This method checks the type of the passed value. If it is
     * a string it adds quotes to the value and escapes it.
     *
     * @param mixed $value Holds the value to escape
     * @param string $type Holds the type of the value
     * @return mixed The escaped value
     * @throws Exception Is thrown if the value can't be escaped successfully
     */
    protected function _escape($value, $type)
    {
    	// if the passed value is NULL, return the "NULL" string
    	if (is_null($value)) {
    		return 'NULL';
    	}
    	// if the passed value is NOT NULL and a string
    	if (in_array($type, array('string', 'String'))) {
			// else escape the string
	    	if (($escaped = $this->_db->real_escape_string($value)) === false) {
				throw new Exception($this->_db->error);
	    	}
	    	// add quotes and return
			return "'" . $escaped . "'";
    	}
    	// if the passed value is NOT NULL and a boolean
    	if (in_array($type, array('boolean', 'Boolean'))) {
            // convert into a Boolean instance
    	    if ($type == 'boolean') {
    	        $value = new TechDivision_Lang_Boolean($value);
    	    }
    	    // check the value
    	    if ($value->booleanValue()) {
    	        // return 1 if Boolean is TRUE
    	        return 1;
    	    }
    	    // else FALSE
    	    return 0;
    	}
    	// return the untouched value
    	return $value;
    }

    /**
     * This method prepares the query passed as parameter and returns
     * the so called "prepared statement", that is a string here.
     *
     * The returned "prepared statement" has the ? parameters replaced
     * by the passed values and is a "fake" therefore. If the value is
     * a string then it is escaped additionally.
     *
     * @param string $query The query that should be prepared
     * @return string The "prepared statement"
     * @throws TechDivsion_Model_Exceptions_PrepareException
     * 		Is thrown when the query can not be prepared
     */
    protected function _prepare($query, $parameter, $parameterTypes)
    {
		// check if the passed parameter is of type array
    	if (is_array($parameter)) {
			// check if at least one parameter is passed
    		if (sizeof($parameter) > 0) {
				try {
				    // catch the exception if a parameter could not be escaped
				    // successfully establish a connection not already done
					$this->_connect();
					// trigger the event before prepare
					$this->_triggerEvent(
					    TechDivision_Model_Manager_MySQLi::EVENT_BEFORE_PREPARE,
					    array(
					    	'query' => $query
					    )
					);
		        	// initialize the "prepared statement"
		        	$stmt = "";
		        	// initialize the token
					$tok = strtok($query, "?");
					// initialize the counter for the tokens
					$counter = 0;
					// tokenize the query
					while ($tok !== false) {
						if (array_key_exists($counter, $parameter) &&
						    array_key_exists($counter, $parameterTypes)) {
							$stmt .= $tok . $this->_escape(
							    $parameter[$counter],
							    $parameterTypes[$counter]
							);
						} else {
							$stmt .= $tok;
						}
						// read the next token
					    $tok = strtok("?");
					    // raise the counter
					    $counter++;
					}
					// trigger the event after prepare
					$this->_triggerEvent(
					    TechDivision_Model_Manager_MySQLi::EVENT_AFTER_PREPARE,
					    array(
					    	'query' => $query
					    )
					);
					// return the prepared statement
					return $stmt;
				} catch(Exception $e) {
					throw new TechDivision_Model_Exceptions_PrepareException(
					    $e->getMessage()
					);
				}
    		}
    	}
    	// return the query untouched
    	return $query;
    }

    /**
     * This method executes the query passed as parameter
     * and returns the result as a MySQLi_Result object.
     *
     * Either the constant MYSQLI_USE_RESULT or MYSQLI_STORE_RESULT depending
     * on the desired behavior. By default, MYSQLI_STORE_RESULT is used.
     *
     * If you use MYSQLI_USE_RESULT all subsequent calls will return error
     * commands out of sync unless you call mysqli_free_result()
     *
     * @param string $query The query that should be executed
     * @param array $parameter The parameters for the prepared statement
     * @param string $resultType
     * 		The type of the objects to be initialized and returned
     * @param array $parameterTypes
     * 		The types of the parameters for the prepared statement
     * @param integer $resultMode The MySQLi query mode that should be used
     * @return array The result of the query
     * @throws TechDivsion_Model_Exceptions_QueryException
     * 		Is thrown when the query is not valid
     * @see TechDivision_Model_Interfaces_Manager::query($query, array $parameter = array(), array $parameterTypes = array(), $resultMode = MYSQLI_STORE_RESULT)
     */
    public function query(
        $query,
        array $parameter = array(),
        array $parameterTypes = array(),
        $resultType = 'stdClass',
        $resultMode = MYSQLI_STORE_RESULT) {
		// establish a connection not already done
		$this->_connect();
		// trigger the event before query
		$this->_triggerEvent(
		    TechDivision_Model_Manager_MySQLi::EVENT_BEFORE_QUERY,
		    array(
		    	'query' => $query,
		    	'parameter' => $parameter
		    )
		);
		// initialize the array for the result
		$res = array();
		// run the query and check that result is not an error
		$sql = $this->_prepare($query, $parameter, $parameterTypes);
        if (($result = $this->_db->query($sql, $resultMode)) === false) {
        	throw new TechDivision_Model_Exceptions_QueryException(
        	    $this->_db->error . ' when running query ' . $sql
        	);
        }

        if (empty($resultType)) {
        	throw new TechDivision_Model_Exceptions_QueryException(
        	    'Missing result type'
        	);
        }

    	// assemble the result
        while ($row = $result->fetch_object($resultType)){
            $res[] = $row;
        }
		// trigger the event after query
		$this->_triggerEvent(
		    TechDivision_Model_Manager_MySQLi::EVENT_AFTER_QUERY,
		    array(
		    	'query' => $query,
		    	'parameter' => $parameter
		    )
		);
		// return the resultset
		return $res;
    }

    /**
     * This method executes the query passed as parameter
     * and returns the number of affected rows.
     *
     * @param string $query The query that should be executed
     * @param array $parameter The parameters for the prepared statement
     * @param array $parameterTypes
     * 		The types of the parameters for the prepared statement
     * @return integer The number of rows affected by the query
     * @throws TechDivision_Model_Exceptions_ExecuteException
     * 		Is thrown when the query can not be executed
     * @see TechDivision_Model_Interfaces_Manager::execute($query, array $parameter = array(), array $parameterTypes = array())
     */
    public function execute(
        $query,
        array $parameter = array(),
        array $parameterTypes = array()) {
		// establish a connection not already done
		$this->_connect();
		// trigger the event before execute
		$this->_triggerEvent(
		    TechDivision_Model_Manager_MySQLi::EVENT_BEFORE_EXECUTE,
		    array(
		    	"query" => $query,
		    	"parameter" => $parameter
		    )
		);
		// initialize the variable for the number of affected rows
		$rowsAffected = 0;
		// run the query and check that result is not an error
		$sql = $this->_prepare($query, $parameter, $parameterTypes);
        if (($result = $this->_db->query($sql, MYSQLI_STORE_RESULT)) === false) {
        	throw new TechDivision_Model_Exceptions_ExecuteException(
        		'Running query ' . $sql .
        		' fails with message ' . $this->_db->error
            );
        } elseif ($result === true) {
 			// do nothing here becaus query is not one of SELECT, SHOW, DESCRIBE or EXPLAIN
        } else {
        	// get the affected rows count
		    $rowsAffected = $result->num_rows;
		}
		// trigger the event after execute
		$this->_triggerEvent(
		    TechDivision_Model_Manager_MySQLi::EVENT_AFTER_EXECUTE,
		    array(
		    	"query" => $query,
		    	"parameter" => $parameter
		    )
		);
		// and return it
        return $rowsAffected;
    }

    /**
     * This method returns the ID generated by a query on a table with a column
     * having the AUTO_INCREMENT attribute. If the last query wasn't an INSERT
     * or UPDATE statement or if the modified table does not have a column with
     * the AUTO_INCREMENT attribute, this function will return zero.
     *
     * @return integer The generated ID
     * @see TechDivision_Model_Interfaces_Manager::getLastInsertId()
     */
    public function getLastInsertId()
    {
    	return $this->_db->insert_id;
    }

    /**
     * Bind the parameter to the passed MySQLi_STMT object.
     *
     * @param MySQLi_STMT $stmt The prepared statement to bind the parameter to
     * @param array $parameter The parameter to bind
     * @param array $parameterTypes The parameter types
     * @return void
     */
    protected function _bindParameter(
        MySQLi_STMT $stmt,
        array $parameter,
        array $parameterTypes) {
		// check if the passed parameter is of type array
		if (is_array($parameter)) {
			// check if at least one parameter is passed
			if (sizeof($parameter) != 0) {
				// initialize the array with type shor cuts
	        	$types = array(
	        		'boolean' => 'i',
	        		'integer' => 'i',
	        		'double' => 'd',
	        		'string' => 's',
	        		'blob' => 'b'
	        	);
	    		// initialize the string for the types
				$t = "";
				// check if the parameter has to be escaped
				foreach ($parameterTypes as $key => $parameterType) {
					if (($isString = strcmp($type, 'string')) == 0) {
						$parameter[$key] = $this->_db->real_escape_string(
						    $parameter[$key]
						);
					}
					// add the type to the string
					$t .= $types[$parameterType];
				}
				// add the string with the types to the parameter array
				array_unshift($parameter, $t);
				// bind the parameters to the passed prepared statement
				if (!call_user_func_array(
				    array($stmt,'bind_param'), $parameter)) {
					throw new Exception($stmt->error);
				}
			}
		}
	}

    /**
     * This method prepares the query passed as parameter
     * and returns the prepared query.
     *
     * @param string $query The query that should be prepared
     * @return MySQLi_STMT The prepared query
     * @throws TechDivision_Model_Exceptions_PrepareException
     * 		Is thrown when the query can not be prepared
     * @see TechDivision_Model_Interfaces_Manager::prepareStmt($query)
     */
    public function prepareStmt($query)
    {
		// establish a connection not already done
		$this->_connect();
		// trigger the event before prepare
		$this->_triggerEvent(
		    TechDivision_Model_Manager_MySQLi::EVENT_BEFORE_PREPARE,
		    array(
		    	'query' => $query
		    )
		);
        // log the query
        $this->_getLogger()->debug(
        	'Now preparing query ' . $query .
        	' on data source ' . $this->getDataSourceName(),
            __LINE__
        );
        // prepare the query ...
		if(($stmt = $this->_db->prepare($query)) === false) {
			throw new TechDivision_Model_Exceptions_PrepareException(
			    $this->_db->error
			);
		}
		// trigger the event after prepare
		$this->_triggerEvent(
		    TechDivision_Model_Manager_MySQLi::EVENT_AFTER_PREPARE,
		    array(
		    	'query' => $query
		    )
		);
		// return the prepared statement
		return $stmt;
    }

    /**
     * This method executes the query passed as parameter
     * and returns the result as an ArrayList.
     *
     * @param $query The query that should be executed
     * @param array $parameter The parameters for the prepared statement
     * @param array $parameterTypes
     * 		The types of the parameters for the prepared statement
     * @return ArrayList The result of the query
     * @throws TechDivision_Model_Exceptions_QueryException
     * 		Is thrown when the query is not valid
     * @see TechDivision_Model_Interfaces_Manager::queryStmt(MySQLi_STMT $stmt, array $parameter = array(), array $parameterTypes = array())
     */
    public function queryStmt(
        MySQLi_STMT $stmt,
        array $parameter = array(),
        array $parameterTypes = array()) {
		// establish a connection not already done
		$this->_connect();
		// trigger the event before query
		$this->_triggerEvent(
		    TechDivision_Model_Manager_MySQLi::EVENT_BEFORE_QUERY,
		    array(
		    	'query' => $query,
		    	'parameter' => $parameter
		    )
		);
        // log the query
        $this->_getLogger()->debug(
        	'Now query prepared statement ' . $query .
        	' on data source ' . $this->getDataSourceName(),
            __LINE__
        );
		// bind the parameters to the prepared statement
        try {
        	$this->_bindParameter($stmt, $parameter, $parameterTypes);
        } catch(Exception $e) {
        	throw new TechDivision_Model_Exceptions_QueryException(
        	    $e->getMessage()
        	);
        }
		// execute the prepared statement
		if (!$stmt->execute()) {
            throw new TechDivision_Model_Exceptions_QueryException(
                $stmt->error
            );
		}
		// save the result
		if (!$stmt->store_result()) {
			throw new TechDivision_Model_Exceptions_QueryException(
			    $stmt->error
			);
		}
		// load the metadata  from the result
		if (($meta = $stmt->result_metadata()) === false) {
			throw new TechDivision_Model_Exceptions_QueryException(
			    $stmt->error
			);
		}
		// fetch field informations
		while ($column = $meta->fetch_field()) {
			if ($column === false) {
				throw new TechDivision_Model_Exceptions_QueryException(
					'Error when fetching field information'
				);
			}
		   	$bindVarsArray[] = &$row[$column->name];
		}
		// bind the result array
		if (!call_user_func_array(
		    array($stmt, 'bind_result'), $bindVarsArray)) {
			throw new TechDivision_Model_Exceptions_QueryException(
			    $stmt->error
			);
		}
		// initialize the result array
		$result = array();
		// fetch the result
		while (($fetched = $stmt->fetch())) {
			if (!$fetched) {
			    // if an error occurs while fetching a
			    // result, throw an Exception
				throw new TechDivision_Model_Exceptions_QueryException(
				    $stmt->error
				);
			}
			// add the fetched row to the result
			$result[] = $row;
		}
		// trigger the event after query
		$this->_triggerEvent(
		    TechDivision_Model_Manager_MySQLi::EVENT_AFTER_QUERY,
		    array(
		    	'query' => $query,
		    	'parameter' => $parameter
		    )
		);
		// return the resultset
		return $result;
    }

    /**
     * This method executes the query passed as parameter
     * and returns the number of affected rows.
     *
     * @param string $query The query that should be executed
     * @param array $parameter The parameters for the prepared statement
     * @param array $parameterTypes
     * 		The types of the parameters for the prepared statement
     * @return integer The number of rows affected by the query
     * @throws TechDivision_Model_Exceptions_ExecutionException
     * 		Is thrown when the query can not be executed
     * @see TechDivision_Model_Interfaces_Manager::executeStmt(MySQLi_STMT $stmt, array $parameter = array(), array $parameterTypes = array())
     */
    public function executeStmt(
        MySQLi_STMT $stmt,
        array $parameter = array(),
        array $parameterTypes = array()) {
		// establish a connection not already done
		$this->_connect();
		// trigger the event before execute
		$this->_triggerEvent(
		    TechDivision_Model_Manager_MySQLi::EVENT_BEFORE_EXECUTE,
		    array(
		    	'parameter' => $parameter
		    )
		);
		// initialize the variable for the number of affected rows
		$rowsAffected = 0;
		// bind the parameters to the prepared statement
        try {
        	$this->_bindParameter($stmt, $parameter, $parameterTypes);
        } catch(Exception $e) {
        	throw new ExecuteException($e->getMessage());
        }
		// execute the prepared statement
		if(!$stmt->execute()) {
            throw new TechDivision_Model_Exceptions_ExecuteException(
                $stmt->error
            );
		}
		// get the affected rows count
		$rowsAffected = $stmt->affected_rows;
		// trigger the event after execute
		$this->_triggerEvent(
		    TechDivision_Model_Manager_MySQLi::EVENT_AFTER_EXECUTE,
		    array(
		    	'query' => $query,
		    	'parameter' => $parameter
		    )
		);
		// and return it
        return $rowsAffected;
    }

    /**
     * This method returns the reference
     * to the conainer.
     *
     * @return TechDivision_Model_Interfaces_Container
     * 		Holds the container reference
     * @see TechDivision_Model_Interfaces_Manager::getContainer()
     */
    public function getContainer()
    {
    	return $this->_container;
    }

    /**
     * Returns the data source.
     *
     * @return TechDivision_Util_Interfaces_DataSource Holds the data source
     * @see TechDivision_Model_Interfaces_Manager::getDataSource()
     */
    public function getDataSource()
    {
		return $this->_dataSource;
    }

    /**
     * Returns the name of the used data source.
     *
     * @return string Holds the name of the used data source
     * @see TechDivision_Model_Interfaces_Manager::getDataSourceName()
     */
    public function getDataSourceName()
    {
		return $this->getDataSource()->getName();
    }

	/**
	 * @see TechDivision_Model_Interfaces_Manager::beginTransaction()
	 */
	public function beginTransaction()
	{
		// nothing to do
	}

	/**
	 * @see TechDivision_Model_Interfaces_Manager::commitTransaction()
	 */
	public function commitTransaction()
	{
		// establish a connection not already done
		$this->_connect();
		// commit the open transaction
		$this->_db->commit();
	}

	/**
	 * @see TechDivision_Model_Interfaces_Manager::rollbackTransaction()
	 */
	public function rollbackTransaction()
	{
		// establish a connection not already done
		$this->_connect();
		// rollback the open transaction
		$this->_db->rollback();
	}

    /**
     * This method return the actual logger instance.
     *
     * @return TechDivision_Logger_Interfaces_Logger
     * 		Holds the actual logger instance
     */
    protected function _getLogger()
    {
    	return $this->_logger;
    }

    /**
     * This method checks if the passed event exists, if
     * yes the callback is added to the internal list.
     *
     * @param string $event Holds the events name
     * @param object $object
     * 		Holds the object the function should be invoked on if
     * 		the event is fired
     * @param string $function
     * 		Holds the name of the function that should be invoked
     * 		if the event is fired
     * @return void
     * @throws TechDivision_Model_Exceptions_UnknownEventExeception
     * 		Is thrown if the event with the passed name is not registered
     * @see TechDivision_Model_Interfaces_Manager::addCallbackFunction($event, $object, $function)
     */
    public function addCallbackFunction($event, $object, $function)
    {
		// check if the passed event exists
		if(array_key_exists($event, $this->_registeredEvents)) {
		    // if yes, add it to the events to fire set the callback function
			$this->_events[$event] = array($object, $function);
			// log that the callback was successfully added
			$this->_getLogger()->debug(
				'Successfully add callback function ' .
			    $function . ' for event $event on data source ' .
			    $this->getDataSourceName(),
			    __LINE__
			);
		} else {
		    // throw an exception if the event is not registered
			throw new TechDivision_Model_Exceptions_UnknownEventException(
				'Try to add invalid event ' . $event
			);
		}
    }

    /**
     * This method invokes the function on the
     * object defined for the passed event when
     * the event is triggered.
     *
     * @param string $eventName Holds the name of the event to trigger
     * @param array $parameter The parameters
     * @return void
     */
    protected function _triggerEvent($eventName, array $parameter = array())
    {
    	// check if an event is set and should be triggered
		if(array_key_exists($eventName, $this->_events)) {
			// log that the event will be triggered now
			$this->_getLogger()->debug(
				'Now triggering event ' . $eventName . ' on data source ' .
			    $this->getDataSourceName(),
			    __LINE__
			);
			// load the event info
			$info = $this->_events[$eventName];
			// initialize the ReflectionObject
			$reflectionObject = new ReflectionObject($info[0]);
			// get the ReflectionMethod
			$reflectionMethod = $reflectionObject->getMethod($info[1]);
			// invoke the method
			$reflectionMethod->invokeArgs($info[0], array($parameter));
			// log that the method was successfully invokde
            $this->_getLogger()->debug(
            	'Successfully invoked callback method ' .
                $reflectionMethod->getName() . ' on object ' .
                $reflectionObject->getName() . ' on data source ' .
                $this->getDataSourceName(),
                __LINE__
            );
    	}
    }
}