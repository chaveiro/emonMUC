<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function device_controller()
{
	global $session,$route,$mysqli,$user,$redis;

	$result = false;
	
	require_once "Modules/device/device_model.php";
	$device = new Device($mysqli,$redis);

	require_once "Modules/device/device_template.php";
	$template = new Template($mysqli,$redis);

	if ($route->format == 'html')
	{
		if ($route->action == "view" && $session['write']) {
			$device_templates = $template->get_list();
			$result = view("Modules/device/Views/device_view.php",array('device_templates'=>$device_templates));
		}
		if ($route->action == 'api') $result = view("Modules/device/Views/device_api.php", array());
	}

	if ($route->format == 'json')
	{
		if ($route->action == "create") {
			if ($session['userid']>0 && $session['write']) {
				$result = $device->create($session['userid'], get('mucid'), get('driver'), get('config'));
			}
		}
		else if ($route->action == 'list') {
			if ($session['userid']>0 && $session['write']) $result = $device->get_list($session['userid']);
		}
		else if ($route->action == 'states') {
			if ($session['userid']>0 && $session['write']) $result = $device->get_states($session['userid']);
		}
		else if ($route->action == 'info' && get('driver') != null) {
			if ($session['userid']>0 && $session['write']) $result = $device->info(get('mucid'), get('id'), get('driver'));
		}
		elseif ($route->action == "template") {
			if ($session['userid']>0 && $session['write']) {
				if ($route->subaction == "list") $result = $template->get_list();
				else if ($route->subaction == "get") $result = $template->get(get('driver'), get('type'));
				else if ($route->subaction == "init") $result = $template->init($session['userid'], get('mucid'), get('driver'), get('config'));
			}
		}
		else if ($route->action == 'get' && get('name') != null) {
			if ($session['userid']>0 && $session['write']) $result = $device->get_device_name(get('mucid'), get('name'));
		}
		else {
			$deviceid = (int) get('id');
			if ($device->exists($deviceid)) // if the device exists
			{
				$deviceget = $device->get($deviceid);
				if (isset($session['write']) && $session['write'] && $session['userid']>0 && $deviceget['userid']==$session['userid']) {
					if ($route->action == "info") $result = $device->info(get('mucid'), $deviceid, null);
					else if ($route->action == "get") $result = $deviceget;
					else if ($route->action == 'update') $result = $device->update(get('mucid'), $deviceid, get('config'));
					else if ($route->action == "delete") $result = $device->delete($session['userid'], get('mucid'), $deviceid);
				}
			}
			else
			{
				$result = array('success'=>false, 'message'=>'Device does not exist');
			}
		}
	}

	return array('content'=>$result);
}