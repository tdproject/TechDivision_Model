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
require_once 'TechDivision/Model/Container/Implementation.php';
require_once 'TechDivision/Model/Exceptions/TransactionCounterException.php';

/**
 * This is the transaction manager that is responsible for
 * the transaction handling in the container.
 *
 * @package TechDivision_Model
 * @author Tim Wagner <t.wagner@techdivision.com>
 * @copyright TechDivision GmbH
 * @link http://www.techdivision.com
 * @license GPL
 */
class TechDivision_Model_Manager_Transaction
    extends TechDivision_Lang_Object {

	/**
	 * Counts the actual open transactions.
	 * @var integer
	 */
	protected $_counter = 0;

	/**
	 * This method starts a transaction.
	 *
	 * @return void
	 */
	public function beginTransaction() 
	{
		TechDivision_Model_Container_Implementation::getContainer()
		    ->getMasterManager()
		    ->beginTransaction();
		$this->_counter++;
	}

	/**
	 * This method finish a transcation successfully.
	 *
	 * @return void
	 */
	public function commitTransaction()
	{
		TechDivision_Model_Container_Implementation::getContainer()
		    ->getMasterManager()
		    ->commitTransaction();
		$this->_counter--;
	}

	/**
	 * This method rolls a transaction back if an error occurs.
	 *
	 * @return void
	 */
	public function rollbackTransaction()
	{
		TechDivision_Model_Container_Implementation::getContainer()
		    ->getMasterManager()
		    ->rollbackTransaction();
		$this->_counter--;
	}
}