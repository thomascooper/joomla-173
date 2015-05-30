<?php
/**
 * Copyright (C) 2015  freakedout (www.freakedout.de)
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Daniel Dimitrov (http://compojoom.com)
 **/

defined('_JEXEC') or die('RestrictedAccess');

require_once(JPATH_COMPONENT_ADMINISTRATOR . '/libraries/joomlamailer/interfaces/hotActivity.php');

/**
 * 	@author Daniel Dimitrov (http://compojoom.com)
 */
class joomailerVirtuemartActivity  implements hotActivity {
	private static $instance = null;

	/**
	 * Implements the singleton pattern
	 * @return object
	 */
	public static function getInstance() {
		if (self::$instance == null) {
			self::$instance = new joomailerVirtuemartActivity();
		}
		return self::$instance;
	}

		/**
	 * @return object $results
	 */
	public function getActivity() {
		$userId = JRequest::getInt('uid');

    	$db = JFactory::getDBO();
    	$query = 'SELECT FROM_UNIXTIME(o.cdate) as crdate, i.order_item_name as title, FORMAT(i.product_item_price,2) as price, "virtuemart" AS joomailerProductCategory'
				. ' FROM ' . $db->qn('#__vm_orders') . ' AS o '
				. ' LEFT JOIN ' . $db->qn('#__vm_order_item') . ' AS i '
				. ' ON o.order_id = i.order_id'
				. ' WHERE o.user_id = '. $db->q($userId)
				. ' AND i.order_status = ' . $db->q('S')
				. ' ORDER BY crdate DESC';

		$db->setQuery($query, 0, 5);

		$results = $db->loadObjectList('crdate');

		return $results;
	}

	/**
	 *
	 * @return int
	 */
	public function getHotnessValue() {
		$hotnessValue = 0;

		$allSubscriptions = $this->getAllProducts();
		$userSubscriptions = $this->getProductsUserBought();

		if ($userSubscriptions == 1) {
			$hotnessValue = 1;
		}
		if (($userSubscriptions > 1) && ($userSubscriptions != $allSubscriptions)) {
			$hotnessValue = 2;
		}
		if ($allSubscriptions != 0 && ($userSubscriptions == $allSubscriptions || $userSubscriptions > $allSubscriptions)) {
			$hotnessValue = 3;
		}

		return $hotnessValue;
	}

	public function getAllProducts() {
		$db = JFactory::getDBO();
		$query = 'SELECT count(product_id) as count FROM ' . $db->qn('#__vm_product')
				. ' WHERE ' . $db->qn('product_publish') . '=' . $db->q('Y')
				. ' AND ' . $db->qn('product_parent_id') . '=' . $db->q(0);

		$db->setQuery($query);
		return $db->loadObject()->count;
	}

	public function getProductsUserBought() {
		$userId = JRequest::getInt('uid');
		$db = JFactory::getDBO();
		$date = JFactory::getDate();
		$now = $date->toMySQL();
		$query = 'SELECT count(i.order_item_id) as count FROM ' .  $db->qn('#__vm_order_item') . ' AS i'
				. ' LEFT JOIN ' . $db->qn('#__vm_orders') . ' AS o '
				. ' ON o.order_id = i.order_id'
				. ' WHERE o.user_id = '. $db->q($userId)
				. ' AND i.order_status = ' . $db->q('S');

		$db->setQuery($query);

		return $db->loadObject()->count;
	}

	public function getAllUserHotnessValue() {
		$hotnessValue = 0;

		$allSubscriptions = $this->getAllProducts();
		$allUserSubscriptions = $this->countProductsPerUser();

		foreach($allUserSubscriptions as $key => $value) {
			if ($value['count'] == 1) {
				$hotnessValue = 1;
			}
			if (($value['count'] > 1) && ($value['count'] != $allSubscriptions)) {
				$hotnessValue = 2;
			}
			if ($value['count'] == $allSubscriptions || $value['count'] > $allSubscriptions) {
				$hotnessValue = 3;
			}
			$allUserSubscriptions[$key]['hotness'] = $hotnessValue;
		}

		return $allUserSubscriptions;
	}

	private function countProductsPerUser() {
		$db = JFactory::getDBO();
		$query = 'SELECT COUNT(i.order_item_id) AS count, u.email AS email '
				. ' FROM ' . $db->qn('#__vm_order_item') . ' AS i'
				. ' LEFT JOIN ' . $db->qn('#__vm_orders') . ' AS o'
				. ' ON i.order_id = o.order_id'
				. ' LEFT JOIN ' . $db->qn('#__users') . ' AS u'
				. ' ON o.user_id = u.id'
				. ' WHERE i.order_status = ' . $db->q('S')
				. ' GROUP BY ' . $db->qn('u.email');
		$db->setQuery($query);
		$result = $db->loadAssocList('email');

		return $result;
	}
}