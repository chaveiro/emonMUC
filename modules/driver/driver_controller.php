<?php
/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project:
 http://openenergymonitor.org
 */

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function driver_controller()
{
    global $mysqli, $redis, $user, $session, $route;

	$result = false;

	require_once "Modules/muc/muc_model.php";
	$muc = new Muc($mysqli, $redis);
	
	require_once "Modules/driver/driver_model.php";
	$driver = new Driver($muc);

	if ($route->format == 'html')
	{
		if ($route->action == "view" && $session['write']) $result = view("Modules/driver/Views/driver_view.php",array());
		if ($route->action == 'api') $result = view("Modules/driver/Views/driver_api.php", array());
	}

	if ($route->format == 'json')
	{
	if ($route->action == "create") {
			if ($session['userid']>0 && $session['write']) $result = $driver->create(get('mucid'),get('name'),get('config'));
		}
		else if ($route->action == 'list') {
			if ($session['userid']>0 && $session['write']) $result = $driver->get_list($session['userid']);
		}
		else {
			$mucid = (int) get('mucid');
			if ($muc->exists($mucid)) // if the controller exists
			{
				$mucget = $muc->get($mucid);
				if (isset($session['write']) && $session['write'] && $session['userid']>0 && $mucget['userid']==$session['userid']) {
					if ($route->action == "unconfigured") $result = $driver->get_unconfigured($mucid);
					else if ($route->action == "info") $result = $driver->info($mucid,get('name'));
					else if ($route->action == "get") $result = $driver->get($mucid,get('name'));
					else if ($route->action == 'update') $result = $driver->update($mucid,get('name'),get('config'));
					else if ($route->action == "delete") $result = $driver->delete($mucid,get('name'));
				}
			}
		}
	}

	return array('content'=>$result);
}