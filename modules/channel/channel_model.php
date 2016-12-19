<?php
/*
	 Released under the GNU Affero General Public License.
	 See COPYRIGHT.txt and LICENSE.txt.

	 Channel module contributed by Nuno Chaveiro nchaveiro(at)gmail.com 2015
	 ---------------------------------------------------------------------
	 Sponsored by http://archimetrics.co.uk/
*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Channel
{
	private $input;
	private $muc;
	private $mysqli;
	private $redis;
	private $log;

	public function __construct($mysqli,$redis)
	{
		require_once "Modules/input/input_model.php";
		$this->input = new Input($mysqli,$redis,null);

		require_once "Modules/muc/muc_model.php";
		$this->muc = new Muc($mysqli,$redis);
		
		$this->mysqli = $mysqli;
		$this->redis = $redis;
		$this->log = new EmonLogger(__FILE__);
	}
	
	public function create($userid, $mucid, $device, $config)
	{
		$userid = (int) $userid;
		$mucid = (int) $mucid;

		$config = (array) json_decode($config);

		$name = preg_replace('/[^\p{N}\p{L}_\s-:]/u','',$config['name']);
		$description = preg_replace('/[^\p{N}\p{L}_\s-:]/u','',$config['description']);

		$result = $this->mysqli->query("SELECT nodeid, devicekey FROM device WHERE `mucid` = '$mucid' AND `name` = '$device'");
		if ($result->num_rows > 0) {
			$row = (array) $result->fetch_object();
			$input = $this->get_mysql_input($userid, $row['nodeid'], $config['name']);
			$this->input->set_fields($input['id'], '{"description":"'.$description.'"}');
			$settings = array(
					'inputid' => $input['id'],
					'nodeid' => $row['nodeid'],
					'apikey' => $row['devicekey']
			);
		}
		else return array('success'=>false, 'message'=>'The associated device "'.$device.'" does not exist');

		$channel = array( 'id' => $config['name'] );

		if (isset($config['address'])) $channel['channelAddress'] = $config['address'];
		if (isset($settings)) $channel['loggingSettings'] = $this->parse_settings((array) $settings);
		if (isset($config['config'])) $channel = array_merge($channel, (array) $config['config']);

		$data = array(
				'device' => $device,
				'configs' => $channel
		);
		$response = $this->muc->request($mucid, 'channels/'.$name, 'POST', $data);
		if (isset($response["success"]) && !$response["success"]) {
			return $response;
		}
		
		return array('success'=>true, 'message'=>'Channel successfully added');
	}

	public function get_list($userid)
	{
		$userid = (int) $userid;
		$channels = array();
		
		foreach($this->muc->get_list($userid) as $muc) {
			// Get drivers of all registered MUCs and add identifying location description and parse their configuration
			$response = $this->muc->request($muc['id'], 'channels/configs', 'GET', null);
			if (isset($response["configs"])) {
				foreach($response['configs'] as $row) {
					$settings = $this->get_settings($userid, $muc['id'], $row);
					$input = $this->get_input($userid, $settings['inputid'], $settings['nodeid'], $row['id']);
					$channel = array(
						'userid'=>$muc['userid'],
						'mucid'=>$muc['id'],
						'device'=>$row['device'],
						'nodeid'=>$input['nodeid'],
						'name'=>$row['id'],
						'description'=>$input['description'],
						'disabled'=>false,
						'processList'=>$input['processList'],
						'time'=>$input['time'],
						'address'=>$row['channelAddress'],
						'settings'=>$settings,
						'config'=>$this->get_config($row)
					);
					if (isset($row['disabled'])) $channel['disabled'] = $row['disabled'];
					
					$channels[] = $channel;
				}
			}
		}
		return $channels;
	}

	public function get_states($userid)
	{
		$userid = (int) $userid;
		
		$states = array();
		foreach($this->muc->get_list($userid) as $muc) {
			// Get drivers of all registered MUCs and add identifying location description
			$response = $this->muc->request($muc['id'], 'channels/states', 'GET', null);
			if (isset($response["states"])) {
				foreach($response['states'] as $state) {
					$states[] = array(
							'userid'=>$muc['userid'],
							'mucid'=>$muc['id'],
							'name'=>$state['id'],
							'state'=>$state['state']
					);
				}
			}
		}
		return $states;
	}

	public function info($mucid, $name, $driver)
	{
		$mucid = (int) $mucid;

		if (isset($name)) {
			$response = $this->muc->request($mucid, 'channels/'.$name.'/infos/parameters', 'GET', null);
		}
		else {
			$response = $this->muc->request($mucid, 'drivers/'.$driver.'/infos/parameters/channel', 'GET', null);
		}
		if (isset($response["success"]) && !$response["success"]) {
			return $response;
		}
		return $response['infos'];
	}

	public function get($userid, $mucid, $name)
	{
		$mucid = (int) $mucid;
		
		$muc = $this->muc->get($mucid);
		$response = $this->muc->request($mucid, 'channels/'.$name.'/configs', 'GET', null);
		if (isset($response["success"]) && !$response["success"]) {
			return $response;
		}
		$config = $response['configs'];
		
		$settings = $this->get_settings($userid, $mucid, $config);
		$input = $this->get_input($userid, $settings['inputid'], $settings['nodeid'], $config['id']);
		$channel = array(
			'userid'=>$muc['userid'],
			'mucid'=>$muc['id'],
			'device'=>$config['device'],
			'nodeid'=>$input['nodeid'],
			'name'=>$config['id'],
			'description'=>$input['description'],
			'disabled'=>false,
			'processList'=>$input['processList'],
			'time'=>$input['time'],
			'address'=>$config['channelAddress'],
			'settings'=>$settings,
			'config'=>$this->get_config($config)
		);
		if (isset($config['disabled'])) $channel['disabled'] = $config['disabled'];
		
		return $channel;
	}

	private function get_settings($userid, $mucid, $channel)
	{
		$settings = array();
		if (isset($channel) ) {
			if(isset($channel['loggingSettings'])) {
				$str = $channel['loggingSettings'];
				if (strpos($str, ':') !== false) {
					$parameters = explode(',', $str);
					foreach ($parameters as $parameter) {
						$keyvalue = explode(':', $parameter);
						$settings[$keyvalue[0]] = $keyvalue[1];
					}
				}
			}

			if (count($settings) < 3) {
				$device = $channel['device'];
				$result = $this->mysqli->query("SELECT nodeid, devicekey FROM device WHERE `mucid` = '$mucid' AND `name` = '$device'");
				if ($result->num_rows > 0) {
					$device = (array) $result->fetch_object();
					$input = $this->get_mysql_input($userid, $device['nodeid'], $channel['id']);
					$settings = array(
							'inputid' => $input['id'],
							'nodeid' => $device['nodeid'],
							'apikey' => $device['devicekey']
					);
					
					$channel['loggingSettings'] = $this->parse_settings($settings);
					
					// Update channel configuration with new logging settings
					$this->muc->request($mucid, 'channels/'.$channel['id'].'/configs', 'PUT', array('configs' => $channel));
				}
			}
		}
		return $settings;
	}

	private function get_input($userid, $inputid, $nodeid, $name)
	{
		if ($this->redis) {
			// Get from redis cache
			return $this->get_redis_input($userid, $inputid, $nodeid, $name);
		}
		else {
			// Get from mysql db
			return $this->get_mysql_input($userid, $nodeid, $name);
		}
	}

	private function get_redis_input($userid, $inputid, $nodeid, $name)
	{
		$input = array();

		if (!$this->redis->exists("input:$inputid") && !$this->load_redis_input($inputid)) {
			// Input does not exist and needs to be re-created
			$inputid = $this->input->create_input($userid, $nodeid, $name);
		}
		
		$input['id'] = $inputid;
		$input['nodeid'] = $nodeid;
		$input['description'] = $this->redis->hget("input:$inputid",'description');
		$input['processList'] = $this->redis->hget("input:$inputid",'processList');
		$input['time'] = $this->redis->hget("input:lastvalue:$inputid",'time');
		
		return $input;
	}

	private function get_mysql_input($userid, $nodeid, $name)
	{
		$result = $this->mysqli->query("SELECT id, nodeid, description, processList, time FROM input WHERE nodeid = '$nodeid' AND `name` = '$name'");
		if ($result->num_rows > 0) {
			return (array) $result->fetch_object();
		}
		else {
			// Input does not exist and needs to be re-created
			$inputid = $this->input->create_input($userid, $nodeid, $name);
			$result = $this->mysqli->query("SELECT id, nodeid, description, processList, time FROM input WHERE id = '$inputid'");
			return (array) $result->fetch_object();
		}
	}

	private function get_config($channel)
	{
		$config = array();
		if (isset($channel)) {
			foreach($channel as $key => $value) {
				if (strcmp($key, 'id') !== 0 && strcmp($key, 'channelAddress')  && strcmp($key, 'loggingSettings') !== 0 && 
						strcmp($key, 'device') !== 0 && strcmp($key, 'disabled') !== 0) {
					
					$config[$key] = $value;
				}
			}
		}
		return $config;
	}

	private function parse_settings($settings)
	{
		$arr = array();
		foreach ($settings as $key=>$value) {
			$arr[] = $key.':'.$value;
		}
		return implode(",", $arr);
	}

	public function update($mucid, $name, $config)
	{
		$mucid = (int) $mucid;
		
		$config = (array) json_decode($config);
		
		$channel = array( 'id' => $config['name'] );
		
		$settings = (array) $config['settings'];
		if ($name !== $config['name']) {
			// Update input names, if channel name changed

			$inputid = $settings['inputid'];
			$inputname = $config['name'];
			$this->mysqli->query("UPDATE channel SET ´name´ = '$inputname' WHERE `id` = '$inputid'");
			if ($this->redis) $this->redis->hset("input:$inputid",'name',$inputname);
		}
		
		if (isset($config['disabled'])) $channel['disabled'] = $config['disabled'];
		if (isset($config['address'])) $channel['channelAddress'] = $config['address'];
		if (isset($config['settings'])) $channel['loggingSettings'] = $this->parse_settings($settings);
		if (isset($config['config'])) $channel = array_merge($channel, (array) $config['config']);

		$response = $this->muc->request($mucid, 'channels/'.$name.'/configs', 'PUT', array('configs' => $channel));
		if (isset($response["success"]) && !$response["success"]) {
			return $response;
		}
		
		return array('success'=>true, 'message'=>'Channel successfully updated');
	}

	public function delete($mucid, $name)
	{
		$mucid = (int) $mucid;
		
		$response = $this->muc->request($mucid, 'channels/'.$name, 'DELETE', null);
		if (isset($response["success"]) && !$response["success"]) {
			return $response;
		}
		return array('success'=>true, 'message'=>'Channel successfully removed');
	}

    private function load_redis_input($id)
    {
        $result = $this->mysqli->query("SELECT id, nodeid, name, description, processList FROM input WHERE id = '$id'");
        $row = (array) $result->fetch_object();

        $this->redis->hMSet("input:$id",array(
            'id'=>$id,
            'nodeid'=>$row['nodeid'],
            'name'=>$row['name'],
            'description'=>$row['description'],
            'processList'=>$row['processList']
        ));
    }
}