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

jimport('joomla.application.component.controlleradmin');

class CostbenefitprojectionControllerDiseases extends JControllerAdmin
{
	protected $text_prefix = 'COM_COSTBENEFITPROJECTION_DISEASES';

	public function getModel($name = 'Disease', $prefix = 'CostbenefitprojectionModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}
	public function goToDiseasecategories()
	{
		 
	}
	
	public function goToDiseasesdata()
	{
		
	}

}