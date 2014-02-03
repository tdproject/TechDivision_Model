<?php

/**
 * License: GNU General Public License
 *
 * Copyright (c) 2009 TechDivision GmbH.  All rights reserved.
 * Note: Original work copyright to respective authors
 *
 * This file is part of TechDivision GmbH - Connect.
 *
 * TechDivision_Lang is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * TechDivision_Lang is distributed in the hope that it will be useful,
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

require_once 'TechDivision/Model/Manager/MySQLi.php';
require_once 'TechDivision/Model/Container/MockImplementation.php';
require_once 'TechDivision/Util/XMLDataSource.php';

/**
 * This is the test for the MySQLi manager class.
 *
 * @package TechDivision_Model
 * @author Tim Wagner <t.wagner@techdivision.com>
 * @copyright TechDivision GmbH
 * @link http://www.techdivision.com
 * @license GPL
 */
class TechDivision_Model_Manager_MySQLiTest extends PHPUnit_Framework_TestCase {

	/**
	 * This test checks manager initialization itself.
	 *
	 * @return void
	 */
	function testConstructor()
	{
	    // initialize a new manager instance
        $manager = new TechDivision_Model_Manager_MySQLi(
            TechDivision_Model_Container_MockImplementation::getContainer(
                $sessionId = md5('test')
            ),
            TechDivision_Util_XMLDataSource::createByName(
            	$dataSourceName = 'test',
            	'TechDivision/Model/META-INF/ds.xml'
            )
        );
        // check the passed data source name
        $this->assertEquals(
            $dataSourceName,
            $manager->getDataSourceName()
        );
	}

	/**
	 * Tests the execute method.
	 *
	 * @return void
	 */
	function testExcecute()
	{
	    // initialize a new manager instance
        $manager = new TechDivision_Model_Manager_MySQLi(
            TechDivision_Model_Container_MockImplementation::getContainer(
                $sessionId = md5('test')
            ),
            TechDivision_Util_XMLDataSource::createByName(
            	$dataSourceName = 'test',
            	'TechDivision/Model/META-INF/ds.xml'
            )
        );
        // drop the table to prevent error
        $manager->execute('DROP TABLE IF EXISTS test');
        // create a new table
        $affectedRows = $manager->execute(
        	'CREATE TABLE test_temp (test_temp_id INT(11) NOT NULL)'
        );
        // check the affected rows
        $this->assertEquals(0, $affectedRows);
        // drop the table
        $affectedRows = $manager->execute(
        	'DROP TABLE test_temp'
        );
        // check the affected rows
        $this->assertEquals(0, $affectedRows);
	}

	/**
	 * Tests the execute method force to throw an
	 * exception.
	 *
	 * @return void
	 */
	function testExcecuteWithException()
	{
	    // set the expected exception
	    $this->setExpectedException(
	    	'TechDivision_Model_Exceptions_ExecuteException'
	    );
	    // initialize a new manager instance
        $manager = new TechDivision_Model_Manager_MySQLi(
            TechDivision_Model_Container_MockImplementation::getContainer(
                $sessionId = md5('test')
            ),
            TechDivision_Util_XMLDataSource::createByName(
            	$dataSourceName = 'test',
            	'TechDivision/Model/META-INF/ds.xml'
            )
        );
        // drop the table to prevent error
        $manager->execute('An invalid SQL statement');
	}

	/**
	 * Tests the query method with a simple SQL statement.
	 *
	 * @return void
	 */
	function testSimpleQuery()
	{
	    // initialize the array with the expected values to check for
	    $results = array(
	        1 => array(1, 'Foo'),
	        2 => array(2, 'Bar')
	    );
	    // initialize a new manager instance
        $manager = new TechDivision_Model_Manager_MySQLi(
            TechDivision_Model_Container_MockImplementation::getContainer(
                $sessionId = md5('test')
            ),
            TechDivision_Util_XMLDataSource::createByName(
            	$dataSourceName = 'test',
            	'TechDivision/Model/META-INF/ds.xml'
            )
        );
        // run a query
        $result = $manager->query('SELECT * FROM test_model');
        // check the size of the found rows
        $this->assertEquals(2, sizeof($result));
        // check the values
        foreach ($result as $row) {
            // load the id of the found row
            $id = $row->test_model_id;
            // assert the row's values
            $this->assertEquals($results[$id][0], $id);
            $this->assertEquals($results[$id][1], $row->val);
        }
	}

	/**
	 * Tests the query method with a parametrized
	 * SQL statement.
	 *
	 * @return void
	 */
	function testComplexQuery()
	{
	    // initialize the array with the expected values to check for
	    $results = array(
	        1 => array(1, 'Foo'),
	        2 => array(2, 'Bar')
	    );
	    // initialize a new manager instance
        $manager = new TechDivision_Model_Manager_MySQLi(
            TechDivision_Model_Container_MockImplementation::getContainer(
                $sessionId = md5('test')
            ),
            TechDivision_Util_XMLDataSource::createByName(
            	$dataSourceName = 'test',
            	'TechDivision/Model/META-INF/ds.xml'
            )
        );
        // run a query
        $result = $manager->query(
        	'SELECT * FROM test_model WHERE test_model_id = ?',
        	array(1),
        	array('integer')
        );
        // check the size of the found rows
        $this->assertEquals(1, sizeof($result));
        // check the values
        foreach ($result as $row) {
            // load the id of the found row
            $id = $row->test_model_id;
            // assert the row's values
            $this->assertEquals($results[$id][0], $id);
            $this->assertEquals($results[$id][1], $row->val);
        }
	}

	/**
	 * Tests the query forcing an exception.
	 *
	 * @return void
	 */
	function testQueryWithException()
	{
	    // set the expected exception
	    $this->setExpectedException(
	    	'TechDivision_Model_Exceptions_QueryException'
	    );
	    // initialize a new manager instance
        $manager = new TechDivision_Model_Manager_MySQLi(
            TechDivision_Model_Container_MockImplementation::getContainer(
                $sessionId = md5('test')
            ),
            TechDivision_Util_XMLDataSource::createByName(
            	$dataSourceName = 'test',
            	'TechDivision/Model/META-INF/ds.xml'
            )
        );
        // run a query
        $result = $manager->query('Invalid SQL statement');
	}
}