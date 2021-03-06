<?php
/**
* 
* 	@version 	2.0.0 March 13, 2014
* 	@package 	Staff Health Cost Benefit Projection
* 	@author  	Vast Development Method <http://www.vdm.io>
* 	@copyright	Copyright (C) 2014 German Development Cooperation <http://www.giz.de>
* 	@license	GNU General Public License <http://www.gnu.org/copyleft/gpl.html>
*
**/
defined( '_JEXEC' ) or die;

jimport('joomla.application.component.modeladmin');
jimport('joomla.application.component.helper');
require_once JPATH_ADMINISTRATOR.'/components/com_costbenefitprojection/helpers/sum.php';

class CostbenefitprojectionModelDiseasedata extends JModelAdmin
{
	// the sum funtion
	protected $sum;
	
	public function getTable($type = 'Diseasedata', $prefix = 'CostbenefitprojectionTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	protected function loadFormData()
	{
		$data = JFactory::getApplication()->getUserState('com_costbenefitprojection.edit.diseasedata.data', array());

		if (empty($data)) {
			$data = $this->getItem();
		}
		
		// check point to see if the user is allowed to edit this item
		$user = JFactory::getUser();
		$userIs = $this->userIs($user->id);
		$itemOwner = $this->userIs($data->owner);
		if($itemOwner['id']){
			if ($userIs['type'] != 'admin'){
				if ($userIs['country'] == $itemOwner['country']){
					if (($userIs['type'] == 'service') && ($itemOwner['service'] != $userIs['id'])){
						$this->checkin();
						throw new Exception('ERROR! this item does not belong to you, so you may not edit it. <a href="javascript:history.go(-1)">Go back</a>');
					} 
				} else {
					$this->checkin();
					throw new Exception('ERROR! this item does not belong to you, so you may not edit it. <a href="javascript:history.go(-1)">Go back</a>');
				}
			}
		}
		return $data;
	}

	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_costbenefitprojection.diseasedata', 'diseasedata', array('control' => 'jform', 'load_data' => $loadData));

		return $form;
	}
	
	/**
	 * Method to change the published state of one or more records.
	 *
	 * @param   array    &$pks   A list of the primary keys to change.
	 * @param   integer  $value  The value of the published state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   11.1
	 */
	public function publish(&$pks, $value = 1)
	{	
		parent::publish($pks, $value);
		// Get a db connection.
		$db = JFactory::getDbo();
		if(is_array($pks)){
			// Create a new query object.
			$query = $db->getQuery(true);
			
			// Select all records from the user profile table where key begins with "custom.".
			// Order it by the ordering field.
			$query->select($db->quoteName('owner'));
			$query->from($db->quoteName('#__costbenefitprojection_diseasedata'));
			$query->where($db->quoteName('id') . ' IN (' . implode(',', $pks) . ')');
			// echo nl2br(str_replace('#__','giz_',$query)); die;
			// Reset the query using our newly populated query object.
			$db->setQuery($query);
			
			// Load the results as a list of stdClass objects (see later for more options on retrieving data).
			$results = $db->loadColumn();
			$results = array_unique($results);
		}
		if(is_array($results)){
			// set sum function
			$this->sum = new Sum();
			// sort results
			$i = 0;
			foreach($results as $owner){
				// Create a new query object.
				$query = $db->getQuery(true);
				
				// Select all records from the user profile table where key begins with "custom.".
				// Order it by the ordering field.
				$query->select($db->quoteName('disease_id'));
				$query->from($db->quoteName('#__costbenefitprojection_diseasedata'));
				$query->where($db->quoteName('owner') . ' = ' . $owner);
				$query->where($db->quoteName('published') . ' = 1');
				//echo nl2br(str_replace('#__','giz_',$query)); die;
				// Reset the query using our newly populated query object.
				$db->setQuery($query);
				
				// load the disease_ids of this owner that is published
				$disease_ids = $db->loadColumn();
				
				if(is_array($disease_ids)){
					$selected = array_unique($disease_ids);
					sort($selected);
					$selected = $this->sum->vdm->the($selected);
					$selected = json_encode($selected);
					$query = $db->getQuery(true);
	
					// Fields to update.
					$fields = array(
						$db->quoteName('profile_value') . ' = ' . $db->quote($selected)
					);
					 
					// Conditions for which records should be updated.
					$conditions = array(
						$db->quoteName('user_id') . ' = '.$owner, 
						$db->quoteName('profile_key') . ' = ' . $db->quote('gizprofile.diseases')
					);
					 
					$query->update($db->quoteName('#__user_profiles'))->set($fields)->where($conditions);
					 
					$db->setQuery($query);
					 
					$result = $db->query();
				} else {
					$query = $db->getQuery(true);
					
					$conditions = array(
						$db->quoteName('user_id') . ' = '.$owner,
						$db->quoteName('profile_key') . ' = ' . $db->quote('gizprofile.diseases')
					);
					 
					$query->delete($db->quoteName('#__user_profiles'));
					$query->where($conditions);
					 
					$db->setQuery($query);
					
					$result = $db->query();
				}
			}
			
			// do calculation
			foreach($results as $owner){
				$this->sum->save($owner);
			}
		}
		
		return true;
	}
	
	/**
	 * Method to save the form data.
	 *
	 * @param	array	The form data.
	 *
	 * @return	boolean	True on success.
	 * @since	1.6
	 */
	public function save($data)
	{
		if (isset($data['params']) && is_array($data['params'])) {
			$params = new JRegistry;
			$params->loadArray($data['params']);
			$data['params'] = (string)$params;

		}
		
		if (parent::save($data)) {

			return true;
		}


		return false;
	}
	
	/**
	 * Get internal user info in relation to this application.
	 *
	 * @return an associative array
	 *
	 */
	protected function userIs($id)
	{
		$userIs = array();
		$userIs['id'] = $id;
		$userIs['country'] = JUserHelper::getProfile($id)->gizprofile[country];
		$userIs['groups'] = JUserHelper::getUserGroups($id);
		$userIs['name'] = JFactory::getUser($id)->name;
		
		$AppGroups['admin'] = JComponentHelper::getParams('com_costbenefitprojection')->get('admin');
		$AppGroups['country'] = JComponentHelper::getParams('com_costbenefitprojection')->get('country');
		$AppGroups['service'] = JComponentHelper::getParams('com_costbenefitprojection')->get('service');		
		
		$admin_user = (count(array_intersect($AppGroups['admin'], $userIs['groups']))) ? true : false;
		$country_user = (count(array_intersect($AppGroups['country'], $userIs['groups']))) ? true : false;
		$service_user = (count(array_intersect($AppGroups['service'], $userIs['groups']))) ? true : false;
		
		if ($admin_user){
			$userIs['type'] = 'admin';
		} elseif ($country_user){
			$userIs['type'] = 'country';
		} elseif ($service_user){
			$userIs['type'] = 'service';
		} else {
			$userIs['service'] = JUserHelper::getProfile($id)->gizprofile[serviceprovider];
		}
		
		return $userIs;
	}
}