<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function muc_controller()
{
    global $mysqli, $redis, $user, $session, $route;

	$result = false;

	require_once "Modules/muc/muc_model.php";
	$muc = new Muc($mysqli,$redis);

	if ($route->format == 'html')
	{
		if ($route->action == "view" && $session['write']) $result = view("Modules/muc/Views/muc_view.php",array());
		if ($route->action == 'api') $result = view("Modules/muc/Views/muc_api.php", array());
	}

	if ($route->format == 'json')
	{
		if ($route->action == 'list') {
			if ($session['userid']>0 && $session['write']) $result = $muc->get_list($session['userid']);
		}
		elseif ($route->action == "create") {
			if ($session['userid']>0 && $session['write']) $result = $muc->create($session['userid'],get('address'),get('description'));
		}
		else if ($route->action == "config") {
			// Configuration may be retrieved with read key
			if ($session['userid']>0) $result = $muc->get_config($session['userid'],get('id'));
		}
		else {
			$mucid = (int) get('id');
			if ($muc->exists($mucid)) // if the muc exists
			{
				$mucget = $muc->get($mucid);
				if (isset($session['write']) && $session['write'] && $session['userid']>0 && $mucget['userid']==$session['userid']) {
					if ($route->action == "get") $result = $mucget;
					else if ($route->action == 'set') $result = $muc->set_fields($session['userid'],$mucid,get('fields'));
					else if ($route->action == "delete") $result = $muc->delete($session['userid'],$mucid);
				}
			}
			else
			{
				$result = array('success'=>false, 'message'=>'MUC does not exist');
			}
		}	 
	}

	return array('content'=>$result);
}