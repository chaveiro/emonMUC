<?php
/*
	 Released under the GNU Affero General Public License.
	 See COPYRIGHT.txt and LICENSE.txt.

	 Driver module contributed by Adrian Minde Adrian_Minde(at)live.de 2016
	 ---------------------------------------------------------------------
	 Sponsored by http://isc-konstanz.de/
*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Driver
{
	private $muc;
	private $log;

	public function __construct($muc)
	{
		$this->muc = $muc;
		$this->log = new EmonLogger(__FILE__);
	}
	
	public function create($mucid, $name, $config)
	{
		$mucid = (int) $mucid;

		$config = (array) json_decode($config);

		$driver = array( 'id' => $config['name'] );
		if (isset($config['config'])) $driver = array_merge($driver, (array) $config['config']);

		$response = $this->muc->request($mucid, 'drivers/'.$name, 'POST', array('configs' => $driver));
		if (isset($response["success"]) && !$response["success"]) {
			return $response;
		}
		
		return array('success'=>true, 'message'=>'Driver successfully added');
	}

	public function get_list($userid)
	{
		$userid = (int) $userid;
		$drivers = array();
		
		foreach($this->muc->get_list($userid) as $muc) {
			// Get drivers of all registered MUCs and add identifying location description and parse their configuration
			$response = $this->muc->request($muc['id'], 'drivers/configs', 'GET', null);
			if (isset($response["configs"])) {
				foreach($response['configs'] as $row) {
					$driver = array(
						'userid'=>$muc['userid'],
						'mucid'=>$muc['id'],
						'muc'=>$muc['description'],
						'name'=>$row['id'],
						'disabled'=>false,
						'config'=>$this->get_config($row)
					);
					if (isset($row['disabled'])) $driver['disabled'] = $row['disabled'];
					if (isset($row['devices'])) $driver['devices'] = $row['devices'];
					else $driver['devices'] = array();
					
					$drivers[] = $driver;
				}
			}
		}
		return $drivers;
	}

	public function get_unconfigured($mucid)
	{
		$mucid = (int) $mucid;
		$drivers = array();

		$response = $this->muc->request($mucid, 'drivers', 'GET', null);
		if (isset($response["success"]) && !$response["success"]) {
			return $response;
		}
		$configured = $response['drivers'];
		
		$response = $this->muc->request($mucid, 'drivers/running', 'GET', null);
		if (isset($response["success"]) && !$response["success"]) {
			return $response;
		}
		$available = $response['drivers'];
		
		foreach($available as $driverid) {
			if (!in_array($driverid, $configured)) $drivers[] = $driverid;
		}
		return $drivers;
	}

	public function info($mucid, $name)
	{
		$mucid = (int) $mucid;

		$response = $this->muc->request($mucid, 'drivers/'.$name.'/infos/parameters', 'GET', null);
		if (isset($response["success"]) && !$response["success"]) {
			return $response;
		}
		return $response['infos'];
	}

	public function get($mucid, $name)
	{
		$mucid = (int) $mucid;
		
		$muc = $this->muc->get($mucid);
		$response = $this->muc->request($mucid, 'drivers/'.$name.'/configs', 'GET', null);
		if (isset($response["success"]) && !$response["success"]) {
			return $response;
		}
		$config = $response['configs'];

		$driver = array(
			'userid'=>$muc['userid'],
			'mucid'=>$mucid,
			'muc'=>$muc['description'],
			'name'=>$config['id'],
			'disabled'=>false,
			'config'=>$this->get_config($config)
		);
		if (isset($config['disabled'])) $driver['disabled'] = $config['disabled'];
		if (isset($config['devices'])) $driver['devices'] = $config['devices'];
		else $driver['devices'] = array();

		return $driver;
	}
	
	private function get_config($device)
	{
		$config = array();
		foreach($device as $key => $value) {
			if (strcmp($key, 'id') !== 0 && strcmp($key, 'devices') !== 0 && strcmp($key, 'disabled') !== 0) $config[$key] = $value;
		}
		return $config;
	}
	
	public function update($mucid, $name, $config)
	{
		$mucid = (int) $mucid;

		$config = (array) json_decode($config);
		
		$driver = array( 
				'id' => $config['name'],
				'disabled' => $config['disabled']
		);
		if (isset($config['config'])) $driver = array_merge($driver, (array) $config['config']);

		$response = $this->muc->request($mucid, 'drivers/'.$name.'/configs', 'PUT', array('configs' => $driver));
		if (isset($response["success"]) && !$response["success"]) {
			return $response;
		}
		
		return array('success'=>true, 'message'=>'Driver successfully updated');
	}

	public function delete($mucid, $name)
	{
		$mucid = (int) $mucid;
		
		$response = $this->muc->request($mucid, 'drivers/'.$name, 'DELETE', null);
		if (isset($response["success"]) && !$response["success"]) {
			return $response;
		}
		return array('success'=>true, 'message'=>'Driver successfully removed');
	}
}