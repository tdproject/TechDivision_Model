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
class TechDivision_Model_Container_ImplementationTest
    extends PHPUnit_Framework_TestCase {

    protected function setUp()
    {

		$generator = new TechDivision_Generator_Implementation(
			'TechDivision/Model/META-INF/generator.xml'
		);

        $generator->initialize();
        $generator->generate();
    }

	/**
	 * This test checks container initialization itself.
	 *
	 * @return void
	 */
	public function testConstructor()
	{
	    // initialize a new container instance
        TechDivision_Model_Container_Implementation::getContainer(
            $sessionId = md5('test'),
            TechDivision_Model_Configuration_XML::getConfiguration(
                'TechDivision/Model/META-INF/deployment-descriptor.xml'
            )
        );
	}

	public function testCache()
	{
	    // initialize a new container instance
        $container = TechDivision_Model_Container_Implementation::getContainer(
            $sessionId = md5('test'),
            TechDivision_Model_Configuration_XML::getConfiguration(
                'TechDivision/Model/META-INF/deployment-descriptor.xml'
            )
        );

        $home = TechDivision_Model_Model_Utils_UserUtil::getHome();

        error_log("Start: {$this->memoryUsage()} KB");

        $tm = new TechDivision_Model_Manager_Transaction();

        $tm->beginTransaction();

        $ids = array();

        for ($i = 1; $i < 101; $i++) {

            error_log("Before $i: {$this->memoryUsage()} KB");

            $user = $home->epbCreate();

            $user->setFirstname(new TechDivision_Lang_String("full$i"));
            $user->setLastname(new TechDivision_Lang_String("name$i"));
            $user->setEmail(new TechDivision_Lang_String("test$i@user.de"));
            $user->setUsername(new TechDivision_Lang_String("username$i"));
            $user->setPassword(new TechDivision_Lang_String("test$i"));
            $userId = $user->create();

            error_log("After $i: {$this->memoryUsage()} KB");

            $u = $home->findByPrimaryKey($userId);

            $this->assertEquals($u->getUserId(), $userId);

            $ids[$i] = $userId;
        }

        $tm->commitTransaction();

        $tm->beginTransaction();

        foreach ($ids as $i => $userId) {
            $u = $home->findByPrimaryKey($userId);
            $this->assertEquals($u->getUsername(), new TechDivision_Lang_String("username$i"));

            $u->setUsername(new TechDivision_Lang_String("username" . ($i + 100)));
            $u->update();
        }

        $tm->commitTransaction();

        $container->getCache()->clean(Zend_Cache::CLEANING_MODE_ALL);

        error_log("Finished: {$this->memoryUsage()} KB");
	}

	public function memoryUsage()
	{
	    return memory_get_usage(true) / 1024;
	}
}