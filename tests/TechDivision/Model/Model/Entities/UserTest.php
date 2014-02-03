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

require_once 'TechDivision/Generator/Implementation.php';
require_once 'TechDivision/Model/Container/Implementation.php';

/**
 * This is the test for the container implementation class.
 *
 * @package TechDivision_Model
 * @author Tim Wagner <t.wagner@techdivision.com>
 * @copyright TechDivision GmbH
 * @link http://www.techdivision.com
 * @license GPL
 */
class TechDivision_Model_Model_Entities_UserTest
    extends PHPUnit_Framework_TestCase {

    /**
     * Generates the necessary files.
     *
     * @return void
     */
    protected function setUp()
    {
        // initialize the generator
		$generator = new TechDivision_Generator_Implementation(
			'TechDivision/Model/META-INF/generator.xml'
		);
        // and generate the sources
        $generator->initialize();
        $generator->generate();
    }

    /**
     * Tests the load method of an entity bean.
     *
     * @return void
     */
	public function testLoad()
	{
	    // initialize a new container instance
        $container = TechDivision_Model_Container_Implementation::getContainer(
            $sessionId = md5('test'),
            TechDivision_Model_Configuration_XML::getConfiguration(
                'TechDivision/Model/META-INF/deployment-descriptor.xml'
            )
        );
        // create an instance of the entity bean
        $user = new TechDivision_Model_Model_Entities_User();
        // connect it to the container instance
        $user->connect($container);
        // try to load it
        $user->load(new TechDivision_Lang_Integer(1));
        // check the values of the getter methods
        $this->assertEquals($user->getUserId(), new TechDivision_Lang_Integer(1));
        $this->assertEquals($user->getFirstname(), new TechDivision_Lang_String('Hans'));
        $this->assertEquals($user->getLastname(), new TechDivision_Lang_String('Mustermann'));
        $this->assertEquals($user->getEmail(), new TechDivision_Lang_String('hm@mustermann.de'));
        $this->assertEquals($user->getPassword(), new TechDivision_Lang_String('musti01'));
        $this->assertEquals($user->getUsername(), new TechDivision_Lang_String('mustermannh'));
	}

	/**
	 * This test checks if after calling the delete method of an entity
	 * bean an exception will be thrown if the bean will be loaded again.
	 *
	 * @return void
	 */
	public function testDelete()
	{
	    // set the excpected exception
	    $this->setExpectedException('TechDivision_Model_Model_Exceptions_UserFindException');
	    // initialize a new container instance
        $container = TechDivision_Model_Container_Implementation::getContainer(
            $sessionId = md5('test'),
            TechDivision_Model_Configuration_XML::getConfiguration(
                'TechDivision/Model/META-INF/deployment-descriptor.xml'
            )
        );
        // create an instance of the entity bean
        $user = new TechDivision_Model_Model_Entities_User();
        // connect it to the container instance
        $user->connect($container);
        // load and delete it
        $user->load(new TechDivision_Lang_Integer(1));
        $user->delete();
        // try to reload it -> exception has to be thrown!
        $user->load(new TechDivision_Lang_Integer(1));
	}

	/**
	 * This test checks that updated values will be loaded correctly.
	 *
	 * @return void
	 */
	public function testUpdate()
	{
	    // set the excpected exception
	    $this->setExpectedException('TechDivision_Model_Model_Exceptions_UserFindException');
	    // initialize a new container instance
        $container = TechDivision_Model_Container_Implementation::getContainer(
            $sessionId = md5('test'),
            TechDivision_Model_Configuration_XML::getConfiguration(
                'TechDivision/Model/META-INF/deployment-descriptor.xml'
            )
        );
        // create an instance of the entity bean
        $user = new TechDivision_Model_Model_Entities_User();
        // connect it to the container instance
        $user->connect($container);
        // load and delete it
        $user->load(new TechDivision_Lang_Integer(1));
        // update the values
        $user->setFirstname(new TechDivision_Lang_String('Franz'));
        $user->setLastname(new TechDivision_Lang_String('Huber'));
        $user->setEmail(new TechDivision_Lang_String('fh@mustermann.de'));
        $user->setPassword(new TechDivision_Lang_String('franzi01'));
        $user->setUsername(new TechDivision_Lang_String('huberf'));
        $user->update();
        // check the values of the getter methods
        $this->assertEquals($user->getUserId(), new TechDivision_Lang_Integer(1));
        $this->assertEquals($user->getFirstname(), new TechDivision_Lang_String('Franz'));
        $this->assertEquals($user->getLastname(), new TechDivision_Lang_String('Huber'));
        $this->assertEquals($user->getEmail(), new TechDivision_Lang_String('fh@mustermann.de'));
        $this->assertEquals($user->getPassword(), new TechDivision_Lang_String('franzi01'));
        $this->assertEquals($user->getUsername(), new TechDivision_Lang_String('huberf'));
        // load the user again
        $user->load(new TechDivision_Lang_Integer(1));
        // check the values of the getter methods
        $this->assertEquals($user->getUserId(), new TechDivision_Lang_Integer(1));
        $this->assertEquals($user->getFirstname(), new TechDivision_Lang_String('Franz'));
        $this->assertEquals($user->getLastname(), new TechDivision_Lang_String('Huber'));
        $this->assertEquals($user->getEmail(), new TechDivision_Lang_String('fh@mustermann.de'));
        $this->assertEquals($user->getPassword(), new TechDivision_Lang_String('franzi01'));
        $this->assertEquals($user->getUsername(), new TechDivision_Lang_String('huberf'));
	}
}