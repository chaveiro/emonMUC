<?php
/*
	 Released under the GNU Affero General Public License.
	 See COPYRIGHT.txt and LICENSE.txt.

	 Device module contributed by Nuno Chaveiro nchaveiro(at)gmail.com 2015
	 ---------------------------------------------------------------------
	 Sponsored by http://archimetrics.co.uk/
*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Device
{
	private $muc;
	private $mysqli;
	private $redis;
	private $log;

	public function __construct($mysqli,$redis)
	{
		require_once "Modules/muc/muc_model.php";
		$this->muc = new Muc($mysqli,$redis);
		
		$this->mysqli = $mysqli;
		$this->redis = $redis;
		$this->log = new EmonLogger(__FILE__);
	}

	public function devicekey_session($devicekey_in)
	{
		$devicekey_in = $this->mysqli->real_escape_string($devicekey_in);
		$session = array();
		$time = time();

		//----------------------------------------------------
		// Check for devicekey login
		//----------------------------------------------------
		if($this->redis && $this->redis->exists("device:key:$devicekey_in"))
		{
			$session['userid'] = $this->redis->get("device:key:$devicekey_in:user");
			$session['read'] = 0;
			$session['write'] = 1;
			$session['admin'] = 0;
			$session['lang'] = "en"; // API access is always in english
			$session['username'] = "API";
			$session['deviceid'] = $this->redis->get("device:key:$devicekey_in:device");
			$session['nodeid'] = $this->redis->get("device:key:$devicekey_in:node");
			$this->redis->hMset("device:lastvalue:".$session['device'], array('time' => $time));
		}
		else
		{
			$result = $this->mysqli->query("SELECT id, userid, nodeid FROM device WHERE devicekey='$devicekey_in'");
			if ($result->num_rows == 1)
			{
				$row = $result->fetch_array();
				if ($row['id'] != 0)
				{
					$session['userid'] = $row['userid'];
					$session['read'] = 0;
					$session['write'] = 1;
					$session['admin'] = 0;
					$session['lang'] = "en"; // API access is always in english
					$session['username'] = "API";
					$session['deviceid'] = $row['id'];
					$session['nodeid'] = $row['nodeid'];
					
					if ($this->redis) {
						$this->redis->set("device:key:$devicekey_in:user",$row['userid']);
						$this->redis->set("device:key:$devicekey_in:device",$row['id']);
						$this->redis->set("device:key:$devicekey_in:node",$row['nodeid']);
						$this->redis->hMset("device:lastvalue:".$row['id'], array('time' => $time));
					} else {
						//$time = date("Y-n-j H:i:s", $time);
						$this->mysqli->query("UPDATE device SET time='$time' WHERE id = '".$row['id']."'");
					}
				}
			}
		}

		return $session;
	}

	public function create($userid, $mucid, $driver, $config)
	{
		$userid = (int) $userid;
		$mucid = (int) $mucid;

		$config = (array) json_decode($config);

		$node = preg_replace('/[^\p{N}\p{L}_\s-:]/u','',$config['nodeid']);
		$name = preg_replace('/[^\p{N}\p{L}_\s-:]/u','',$config['name']);
		$description = preg_replace('/[^\p{N}\p{L}_\s-:]/u','',$config['description']);

		$type = preg_replace('/[^\p{N}\p{L}_\s-:]/u','',$config['type']);

		$result = $this->mysqli->query("SELECT id FROM device WHERE `name` = '$name'");
		if ($result->num_rows>0) {
			return array('success'=>false, 'message'=>'The device "'.$name.'" is already configured');
		}
		
		if ($mucid > 0) {
			$device = array( 'id' => $name );

			if (isset($config['address'])) $device['deviceAddress'] = $config['address'];
			if (isset($config['settings'])) $device['settings'] = $config['settings'];
			if (isset($config['config'])) $device = array_merge($device, (array) $config['config']);

			$data = array(
					'driver' => $driver,
					'configs' => $device
			);
			$response = $this->muc->request($mucid, 'devices/'.$name, 'POST', $data);
			if (isset($response["success"]) && !$response["success"]) {
				return $response;
			}
		}
		
		return $this->create_mysql($userid, $mucid, $node, $name, $description, $type);
	}
	
	private function create_mysql($userid, $mucid, $nodeid, $name, $description, $type) {

		$devicekey = md5(uniqid(mt_rand(), true));

		$this->mysqli->query("INSERT INTO device (userid, mucid, nodeid, name, description, type, devicekey)
				VALUES ('$userid','$mucid','$nodeid','$name','$description','$type','$devicekey')");

		$id = $this->mysqli->insert_id;
		if ($id > 0) {
			if ($this->redis) {
				$this->redis->sAdd("user:device:$userid", $id);
				$this->redis->hMSet("device:$id",array(
						'id'=>$id,
						'userid'=>$userid,
						'mucid'=>$mucid,
						'nodeid'=>$nodeid,
						'name'=>$name,
						'description'=>$description,
						'type'=>$type,
						'devicekey'=>$devicekey
				));
			}
		}
		else {
			return array('success'=>false, 'message'=>'Unknown error while adding Device');
		}
		return array('success'=>true, 'id'=>$id, 'message'=>'Device successfully added');
	}

	public function exists($id)
	{
		$id = intval($id);
		
		static $device_exists_cache = array(); // Array to hold the cache
		if (isset($device_exists_cache[$id])) {
			$deviceexists = $device_exists_cache[$id]; // Retrieve from static cache
		} else {
			$deviceexists = false;
			if ($this->redis) {
				if (!$this->redis->exists("device:$id")) {
					if ($this->load_redis_device($id)) {
						$deviceexists = true;
					}
				} else {
					$deviceexists = true;
				}
			} else {
				$result = $this->mysqli->query("SELECT id FROM device WHERE id = '$id'");
				if ($result->num_rows>0) {
					$deviceexists = true;
				}
			}
			// Cache it
			$device_exists_cache[$id] = $deviceexists;
		}
		return $deviceexists;
	}

	public function get_list($userid)
	{
		if ($this->redis) {
			$devices= $this->get_redis_list($userid);
		} else {
			$devices= $this->get_mysql_list($userid);
		}
		$configs = $this->request_list($userid);
		$devices = $this->init_device($userid, $devices, $configs);

		$result = array();
		foreach($devices as $device) {
			if ($device['mucid'] > 0) {
				foreach($configs as $config) {
					if($config['id'] === $device['name'] && $config['mucid'] === $device['mucid']) {
						$result[] = $this->get_device($device, $config);
						break;
					}
				}
			}
			else $result[] = $this->get_device($device, null);
		}
		
		return $result;
	}
	
	private function init_device($userid, $devices, $configs) {

		$reload = false;
		foreach($configs as $device) {
			$exists = false;
			foreach($devices as $d) {
				if($d['name'] === $device['id'] && $d['mucid'] === $device['mucid']) {
					$exists = true;
					break;
				}
			}
			if (!$exists) {
				$result = $this->create_mysql($userid, $device['mucid'], $device['driver'], $device['id'], '', '');
				if ($result['success']) {
					$reload = true;
				}
			}
		}
		
		if ($reload) {
			if ($this->redis) {
				$devices = $this->get_redis_list($userid);
			} else {
				$devices = $this->get_mysql_list($userid);
			}
		}
		return $devices;
	}
	
	private function request_list($userid)
	{
		$userid = (int) $userid;
		$devices = array();
		
		foreach($this->muc->get_list($userid) as $muc) {
			// Get drivers of all registered MUCs and add identifying location description
			$response = $this->muc->request($muc['id'], 'devices/configs', 'GET', null);
			if (isset($response["configs"])) {
				foreach($response['configs'] as $device) {
					if (!isset($device['disabled'])) $device['disabled'] = false;
					if (!isset($device['channels'])) $device['channels'] = array();
					
					$device['mucid'] = $muc['id'];
					
					$devices[] = $device;
				}
			}
		}
		return $devices;
	}

	private function get_redis_list($userid)
	{
		$userid = (int) $userid;
		if (!$this->redis->exists("user:device:$userid")) $this->load_redis($userid);

		$devices = array();
		$deviceids = $this->redis->sMembers("user:device:$userid");
		foreach ($deviceids as $id)
		{
			$row = $this->redis->hGetAll("device:$id");
			$lastvalue = $this->redis->hMget("device:lastvalue:".$id,array('time'));
			$row['time'] = $lastvalue['time'];
			$devices[] = $row;
		}
		return $devices;
	}

	private function get_mysql_list($userid)
	{
		$userid = (int) $userid;
		$devices = array();

		$result = $this->mysqli->query("SELECT id, userid, mucid, nodeid, name, description, type, devicekey, time FROM device WHERE userid = '$userid'");
		while ($row = (array) $result->fetch_object())
		{
			$device = array(
				'id'=>$row['id'],
				'userid'=>$row['userid'],
				'mucid'=>$row['mucid'],
				'nodeid'=>$row['nodeid'],
				'name'=>$row['name'],
				'description'=>$row['description'],
				'type'=>$row['type'],
				'devicekey'=>$row['devicekey'],
				'time'=>$row['time']
			);
			
			$devices[] = $device;
		}
		return $devices;
	}

	public function get_states($userid)
	{
		$userid = (int) $userid;

		if ($this->redis) {
			$devices= $this->get_redis_list($userid);
		} else {
			$devices= $this->get_mysql_list($userid);
		}
		
		$states = array();
		foreach($this->muc->get_list($userid) as $muc) {
			// Get drivers of all registered MUCs and add identifying location description
			$response = $this->muc->request($muc['id'], 'devices/states', 'GET', null);
			if (isset($response["states"])) {
				foreach($response['states'] as $state) {
					foreach($devices as $device) {
						if ($device['mucid'] === $muc['id'] && $device['name'] === $state['id']) {
							$states[] = array(
									'id'=>$device['id'],
									'userid'=>$muc['userid'],
									'mucid'=>$muc['id'],
									'name'=>$state['id'],
									'state'=>$state['state']
							);
						}
					}
				}
			}
		}
		return $states;
	}

	public function info($mucid, $id, $driver)
	{
		$mucid = (int) $mucid;

		if ($id !== '' && is_numeric($id)) {
			$id = (int) $id;
			
			if ($this->redis) {
				if (!$this->redis->exists("device:$id")) $this->load_redis_device($id);
				$name = $this->redis->hget("device:$id","name");
			}
			else {
				$result = $this->mysqli->query("SELECT name FROM device WHERE `id` = '$id'");
				$device = (array) $result->fetch_object();
				$name = $device['name'];
			}
			$response = $this->muc->request($mucid, 'devices/'.$name.'/infos/parameters', 'GET', null);
		}
		else {
			$response = $this->muc->request($mucid, 'drivers/'.$driver.'/infos/parameters/device', 'GET', null);
		}
		if (isset($response["success"]) && !$response["success"]) {
			return $response;
		}
		return $response['infos'];
	}

	public function get($id)
	{
		$id = (int) $id;

		$device = $this->get_device_db($id);
		$mucid = (int) $device['mucid'];

		$response = $this->muc->request($mucid, 'devices/'.$device['name'].'/configs', 'GET', null);
		if (isset($response["success"]) && !$response["success"]) {
			return $this->get_device($device, null);
		}
		$config = (array) $response['configs'];
		return $this->get_device($device, $config);
	}

	public function get_device_db($id)
	{
		$id = (int) $id;

		if ($this->redis) {
			return (array) $this->get_device_redis($id);
		}
		else {
			return (array) $this->get_device_mysql($id);
		}
	}

	public function get_device_name($mucid, $name)
	{
		$mucid = (int) $mucid;

		$result = $this->mysqli->query("SELECT id, userid, mucid, nodeid, name, description, type, devicekey, time FROM device WHERE mucid = '$mucid' AND name = '$name'");
		$device = (array) $result->fetch_object();

		$response = $this->muc->request($mucid, 'devices/'.$device['name'].'/configs', 'GET', null);
		if (isset($response["success"]) && !$response["success"]) {
			return $this->get_device($device, null);
		}
		$config = (array) $response['configs'];
		return $this->get_device($device, $config);
	}
	
	private function get_device_redis($id)
	{
		if (!$this->redis->exists("device:$id")) $this->load_redis_device($id);
		$device = (array) $this->redis->hGetAll("device:$id");
		$lastvalue = $this->redis->hMget("device:lastvalue:".$id,array('time'));
		$device['time'] = $lastvalue['time'];
		
		return $device;
	}
	
	private function get_device_mysql($id)
	{
		$result = $this->mysqli->query("SELECT id, userid, mucid, nodeid, name, description, type, devicekey, time FROM device WHERE id = '$id'");
		return (array) $result->fetch_object();
	}
	
	private function get_device($device, $config)
	{
		if (isset($config)) {
			$mucid = $device['mucid'];
			$driver = $config['driver'];
			if (isset($config['disabled'])) {
				$disabled = $config['disabled'];
			}
			else $disabled = false;
		}
		else {
			$mucid = -1;
			$driver = 'standalone';
			$disabled = false;
		}
		
		$result = array(
			'id'=>$device['id'],
			'userid'=>$device['userid'],
			'mucid'=>$mucid,
			'driver'=>$driver,
			'nodeid'=>$device['nodeid'],
			'name'=>$device['name'],
			'description'=>$device['description'],
			'type'=>$device['type'],
			'devicekey'=>$device['devicekey'],
			'time'=>$device['time'],
			'disabled'=>$disabled
		);
		if (isset($config)) {
			if (isset($config['deviceAddress'])) $result['address'] = $config['deviceAddress'];
			if (isset($config['settings'])) $result['settings'] = $config['settings'];
			
			$deviceconfig = $this->get_config($config);
			if (count($deviceconfig) > 0) $result['config'] = $deviceconfig;
		}
		if (isset($config['channels'])) $result['channels'] = $config['channels'];
		else $result['channels'] = array();
		
		return $result;
	}
	
	private function get_config($device)
	{
		$config = array();
		foreach($device as $key => $value) {
			if (strcmp($key, 'id') !== 0 && strcmp($key, 'mucid') !== 0 && strcmp($key, 'driver') !== 0 && strcmp($key, 'disabled') !== 0 && 
					strcmp($key, 'deviceAddress') !== 0 && strcmp($key, 'settings') !== 0 && strcmp($key, 'channels') !== 0) {
				
				$config[$key] = $value;
			}
		}
		return $config;
	}
	
	private function parse_config($config, $type)
	{
		if (isset($config[$type])) {
			return $config[$type];
		}
		else {
			$arr = array();
			foreach ($config as $key=>$value) {
				$arr[] = $key.':'.$value;
			}
			return implode(",", $arr);
		}
	}

	public function update($mucid, $id, $device)
	{
		$mucid = (int) $mucid;
		$id = (int) $id;

		$device = (array) json_decode($device);
		$name = $device['name'];
		if ($this->redis) {
			$dbdevice = $this->get_device_redis($id);
		}
		else {
			$dbdevice = $this->get_device_mysql($id);
		}
		$fields = array();

		// Repeat this line changing the field name to add fields that can be updated:
		if ($device['nodeid'] !== $dbdevice['nodeid']) {
			$node = preg_replace('/[^\p{L}_\p{N}\s-:]/u','',$device['nodeid']);
			$fields[] = "`nodeid` = '".$node."'";
		}
		if ($device['name'] !== $dbdevice['name']) {
			$name = preg_replace('/[^\p{L}_\p{N}\s-:]/u','',$device['name']);
			$fields[] = "`name` = '".$name."'";
		}
		if ($device['description'] !== $dbdevice['description']) $fields[] = "`description` = '".preg_replace('/[^\p{L}_\p{N}\s-:]/u','',$device['description'])."'";
		if ($device['type'] !== $dbdevice['type']) $fields[] = "`type` = '".preg_replace('/[^\/\|\,\w\s-:]/','',$device['type'])."'";

		if ($mucid > 0) {
			$config = array( 'id' => $name );
			if (isset($device['disabled'])) $config['disabled'] = $device['disabled'];

			if (isset($device['address'])) $config['deviceAddress'] = $device['address'];
			if (isset($device['settings'])) $config['settings'] = $device['settings'];
			if (isset($device['config'])) $config = array_merge($config, (array) $device['config']);

			$response = $this->muc->request($mucid, 'devices/'.$dbdevice['name'].'/configs', 'PUT', array('configs' => $config));
			if (isset($response["success"]) && !$response["success"]) {
				return $response;
			}
			
			if ($device['nodeid'] !== $dbdevice['nodeid']) {
				// Update controllers channel log settings
				$channelids = (array) $device['channels'];
				if (count($channelids) > 0) {
					require_once "Modules/channel/channel_model.php";
					$channel = new Channel($this->muc, $this->mysqli, $this->redis);
						
					$channels = $channel->get_list($dbdevice['userid']);
					foreach($channelids as $channelid) {
						foreach ($channels as $ch) {
							$localchannel = (array) $ch;
							if ($localchannel['name'] === $channelid) {
								$localchannel['settings']['nodeid'] = $node;
								$channel->update($mucid, $channelid, json_encode($localchannel));
			
								$inputid = $ch['settings']['inputid'];
								$this->mysqli->query("UPDATE channel SET ´nodeid´ = '$node' WHERE `id` = '$inputid'");
								if ($this->redis) $this->redis->hset("input:$inputid",'nodeid',$node);
							}
						}
					}
				}
			}
		}

		if (count($fields)) {
			// Convert to a comma seperated string for the mysql query
			$fieldstr = implode(",", $fields);
			$this->mysqli->query("UPDATE device SET ".$fieldstr." WHERE `id` = '$id'");
			
			if ($this->mysqli->affected_rows>0){
				// Update redis
				if ($this->redis) {
					if ($device['nodeid'] !== $dbdevice['nodeid']) $this->redis->hset("device:$id",'nodeid',$device['nodeid']);
					if ($device['name'] !== $dbdevice['name']) $this->redis->hset("device:$id",'name',$device['name']);
					if ($device['description'] !== $dbdevice['description']) $this->redis->hset("device:$id",'description',$device['description']);
					if ($device['type'] !== $dbdevice['type']) $this->redis->hset("device:$id",'type',$device['type']);
				}
			}
			else {
				return array('success'=>false, 'message'=>'Device could not be updated');
			}
		}
		return array('success'=>true, 'message'=>'Device successfully updated');
	}

	public function delete($userid, $mucid, $id)
	{
		$userid = (int) $userid;
		$mucid = (int) $mucid;

		if ($mucid > 0) {
			if ($this->redis) {
				$device = $this->get_device_redis($id);
			}
			else {
				$device = $this->get_device_mysql($id);
			}
			$this->muc->request($mucid, 'devices/'.$device['name'], 'DELETE', null);
		}
		
		$this->mysqli->query("DELETE FROM device WHERE userid = '$userid' AND id = '$id'");
		
		// Remove from redis
		if ($this->redis) {
			$this->redis->del("device:$id");
			$this->redis->srem("user:device:$userid",$id);
		}
		
		// Clear static cache
		if (isset($device_exists_cache[$id])) { unset($device_exists_cache[$id]); }
		
		return array('success'=>true, 'message'=>'Device successfully removed');
	}

	private function load_redis($userid)
	{
		$this->redis->delete("user:device:$userid");
		$result = $this->mysqli->query("SELECT id, userid, mucid, nodeid, name, description, type, devicekey, time FROM device WHERE userid = '$userid'");
		while ($row = (array) $result->fetch_object())
		{
			$this->redis->sAdd("user:device:$userid", $row['id']);
			$this->redis->hMSet("device:".$row['id'],array(
				'id'=>$row['id'],
				'userid'=>$row['userid'],
				'mucid'=>$row['mucid'],
				'nodeid'=>$row['nodeid'],
				'name'=>$row['name'],
				'description'=>$row['description'],
				'type'=>$row['type'],
				'devicekey'=>$row['devicekey']
			));
		}
	}

	private function load_redis_device($id)
	{
		$result = $this->mysqli->query("SELECT id, userid, mucid, nodeid, name, description, type, devicekey, time FROM device WHERE id = '$id'");
		$row = (array) $result->fetch_object();
		if (!$row) {
			$this->log->warn("Device model: Requested device with id=$id does not exist");
			return false;
		}
		
		$this->redis->hMSet("device:".$id,array(
			'id'=>$row['id'],
			'userid'=>$row['userid'],
			'mucid'=>$row['mucid'],
			'nodeid'=>$row['nodeid'],
			'name'=>$row['name'],
			'description'=>$row['description'],
			'type'=>$row['type'],
			'devicekey'=>$row['devicekey']
		));
	}
}