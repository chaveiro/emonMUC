<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function channel_controller()
{
	global $session,$route,$mysqli,$user,$redis;

	$result = false;

	require_once "Modules/muc/muc_model.php";
	$muc = new Muc($mysqli, $redis);
	
	require_once "Modules/channel/channel_model.php";
	$channel = new Channel($mysqli, $redis);

	if ($route->format == 'html')
	{
		if ($route->action == "view" && $session['write']) $result = view("Modules/channel/Views/channel_view.php",array());
		if ($route->action == 'api') $result = view("Modules/channel/Views/channel_api.php", array());
	}

	if ($route->format == 'json')
	{
		if ($route->action == "create") {
			if ($session['userid']>0 && $session['write']) {
				$result = $channel->create($session['userid'],get('mucid'),get('device'),get('config'));
			}
		}
		else if ($route->action == 'list') {
			if ($session['userid']>0 && $session['write']) $result = $channel->get_list($session['userid']);
		}
		else if ($route->action == 'states') {
			if ($session['userid']>0 && $session['write']) $result = $channel->get_states($session['userid']);
		}
		else if ($route->action == 'info' && get('driver') != null) {
			if ($session['userid']>0 && $session['write']) $result = $channel->info(get('mucid'),null,get('driver'));
		}
		else {
			$mucid = (int) get('mucid');
			if ($muc->exists($mucid)) // if the controller exists
			{
				$mucget = $muc->get($mucid);
				if (isset($session['write']) && $session['write'] && $session['userid']>0 && $mucget['userid']==$session['userid']) {
					if ($route->action == "info") $result = $channel->info($mucid,get('name'),null);
					else if ($route->action == "get") $result = $channel->get($session['userid'],$mucid,get('name'));
					else if ($route->action == 'update') $result = $channel->update($mucid,get('name'),get('config'));
					else if ($route->action == "delete") $result = $channel->delete($mucid,get('name'));
				}
			}
			else
			{
				$result = array('success'=>false, 'message'=>'Channel does not exist');
			}
		}
	}

	return array('content'=>$result);
}