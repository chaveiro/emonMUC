<?php
/*
	 Released under the GNU Affero General Public License.
	 See COPYRIGHT.txt and LICENSE.txt.

	 MUC module contributed by Adrian Minde Adrian_Minde(at)live.de 2016
	 ---------------------------------------------------------------------
	 Sponsored by http://isc-konstanz.de/
*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Muc
{
	private $mysqli;
	private $redis;
	private $log;

	public function __construct($mysqli,$redis)
	{
		$this->mysqli = $mysqli;
		$this->redis = $redis;
		$this->log = new EmonLogger(__FILE__);
	}

	public function create($userid, $address, $description)
	{
		$userid = (int) $userid;
		$description = preg_replace('/[^\p{N}\p{L}_\s-:]/u','',$description);
		$password = md5(uniqid(mt_rand(), true));

		// Make sure, the defined address is valid
		if(substr_compare($address, '/', strlen($address)-1, 1) === 0) {
			$address = substr($address, 0, strlen($address)-1);
		}

		// TODO: Add ports to be configurable in settings
		if (substr($address, 0, 7) === 'http://') {
			$pre = 'http://';
			$in = substr($address, 7, strlen($address));
			$post = ':36666/rest/';
		}
		else if (substr($address, 0, 8) === 'https://') {
			$pre = 'https://';
			$in = substr($address, 8, strlen($address));
			$post = ':36667/rest/';
		}
		else {
			$pre = 'https://';
			$in = $address;
			$post = ':36667/rest/';
		}
		$address = $pre.$in.$post;
		
		$result = $this->mysqli->query("SELECT id, password FROM muc WHERE `address` = '$address'");
		if ($row = (array) $result->fetch_object()) {
			$id = $row['id'];
			$password = $row['password'];
		}
		else {
			$result = $this->mysqli->query("INSERT INTO muc (userid, address, description, password) VALUES ('$userid','$address','$description','$password')");
			$id = $this->mysqli->insert_id;
			if ($id > 0) {
				if ($this->redis) {
					$this->redis->sAdd("user:muc:$userid", $id);
					$this->redis->hMSet("muc:$id",array(
							'id'=>$id,
							'userid'=>$userid,
							'address'=>$address,
							'description'=>$description,
							'password'=>$password));
				}
			}
			else {
				return array('success'=>false, 'message'=>'Unknown error while adding MUC');
			}
		}
		
		// Request the muc to register the user
		$url = 'http://'.$in.':36666/rest/users';
		$data = array('id' => $id, 
				'password' => $password);
		
		$response = $this->sendHttpRequest(null, null, $url, 'POST', array('configs' => $data));
		if (isset($response["success"]) && !$response["success"]) {
			return $response;
		}
		
		// Try to delete default admin account if still existing
		$this->sendHttpRequest(null, null, $url, 'DELETE', array('configs' => array('id' => 'admin', 'password' => 'admin')));
		
		return array('success'=>true, 'id'=>$id, 'message'=>'MUC successfully registered');
	}

	public function exists($id)
	{
		$id = intval($id);
	
		static $muc_exists_cache = array(); // Array to hold the cache
		if (isset($muc_exists_cache[$id])) {
			$mucexists = $muc_exists_cache[$id]; // Retrieve from static cache
		} else {
			$mucexists = false;
			if ($this->redis) {
				if (!$this->redis->exists("muc:$id")) {
					if ($this->load_redis_muc($id)) {
						$mucexists = true;
					}
				} else {
					$mucexists = true;
				}
			} else {
				$result = $this->mysqli->query("SELECT id FROM muc WHERE id = '$id'");
				if ($result->num_rows>0) {
					$mucexists = true;
				}
			}
			// Cache it
			$muc_exists_cache[$id] = $mucexists;
		}
		return $mucexists;
	}

	public function get_list($userid)
	{
		if ($this->redis) {
			return $this->get_redis_list($userid);
		} else {
			return $this->get_mysql_list($userid);
		}
	}

	private function get_redis_list($userid)
	{
		$userid = (int) $userid;
		
		if (!$this->redis->exists("user:muc:$userid")) $this->load_redis($userid);

		$mucs = array();
		$mucids = $this->redis->sMembers("user:muc:$userid");
		foreach ($mucids as $id)
		{
			$row = $this->redis->hGetAll("muc:$id");
			$mucs[] = $row;
		}
		return $mucs;
	}

	private function get_mysql_list($userid)
	{
		$userid = (int) $userid;
		$mucs = array();

		$result = $this->mysqli->query("SELECT id, userid, address, description, password FROM muc WHERE userid = '$userid'");
		while ($row = (array) $result->fetch_object())
		{
			$muc = array(
				'id'=>$row['id'],
				'userid'=>$row['userid'],
				'address'=>$row['address'],
				'description'=>$row['description'],
				'password'=>$row['password']
			);
			
			$mucs[] = $muc;
		}
		return $mucs;
	}

	public function get($id)
	{
		$id = (int) $id;

		if ($this->redis) {
            if (!$this->redis->exists("muc:$id")) $this->load_redis_muc($id);
            return $this->redis->hGetAll("muc:$id");
		} else {
			$result = $this->mysqli->query("SELECT id,userid,address,description,password FROM muc WHERE id = '$id'");
            return (array) $result->fetch_object();
		}
	}

	public function set_fields($userid, $id, $fields)
	{
		$id = (int) $id;

		$fields = json_decode(stripslashes($fields));
		$array = array();
		$data = array('id' => $id);

		// Repeat this line changing the field address to add fields that can be updated:
		if (isset($fields->address)) {
			$address = $fields->address;
			
			// Make sure, the defined address is valid
			if(substr_compare($address, '/', strlen($address)-1, 1) !== 0) {
				$address = $address."/";
			}
			$array[] = "`address` = '".$address."'";
		}
		if (isset($fields->description)) {
			$description = preg_replace('/[^\p{L}_\p{N}\s-:]/u','',$fields->description);
			
			$array[] = "`description` = '".$description."'";
		}
		if (isset($fields->password)) {
			$password = preg_replace('/[^\p{L}_\p{N}\s-:]/u','',$fields->password);
			$result = $this->mysqli->query("SELECT password FROM muc WHERE password='$password'");
			if ($result->num_rows > 0)
			{
				return array('success'=>false, 'message'=>'Field password is invalid'); // is duplicate
			}
			
			$array[] = "`password` = '".$password."'";
			$data['password'] = $fields->password;

			$muc = $this->get($id);
			$data['oldPassword'] = $muc['password'];
		}

		if (count($data) > 1) {
			$response = $this->request($id, 'users', 'PUT', array('configs' => $data));
			if (isset($response["success"]) && !$response["success"]) {
				return $response;
			}
		}

		// Convert to a comma seperated string for the mysql query
		$fieldstr = implode(",",$array);
		$this->mysqli->query("UPDATE muc SET ".$fieldstr." WHERE `id` = '$id'");

		if ($this->mysqli->affected_rows>0){
			// Update redis
			if ($this->redis) {
				if (isset($fields->address)) $this->redis->hset("muc:$id",'address',$address);
				if (isset($fields->description)) $this->redis->hset("muc:$id",'description',$description);
				if (isset($fields->password)) $this->redis->hset("muc:$id",'password',$password);
			}
			
			return array('success'=>true, 'message'=>'Fields updated');
		} else {
			return array('success'=>false, 'message'=>'Fields could not be updated');
		}
	}

	public function delete($userid, $id)
	{
		$id = (int) $id;
		$userid = (int) $userid;
		
		$data = array('id' => $id);
		$response = $this->request($id, 'users', 'DELETE', array('configs' => $data));
		if (isset($response["success"]) && !$response["success"]) {
			return $response;
		}
		
		$this->mysqli->query("DELETE FROM muc WHERE `userid` = '$userid' AND `id` = '$id'");

		// Remove from redis
		if ($this->redis) {
			$this->redis->del("muc:$id");
			$this->redis->srem("user:muc:$userid",$id);
		}
		
		// Clear static cache
		if (isset($muc_exists_cache[$id])) { unset($muc_exists_cache[$id]); }
		
		return array('success'=>true, 'message'=>'MUC successfully removed');
	}

	public function request($id, $action, $type, $data)
	{
		$id = (int) $id;
		
		if (!$this->exists($id)) {
			return array('success'=>false, 'message'=>'MUC does not exist');
		}

		$muc = $this->get($id);
		
		$url = $muc['address'].$action;
		return $this->sendHttpRequest($id, $muc['password'], $url, $type, $data);
	}

	private function sendHttpRequest($username, $password, $url, $type, $data)
	{
		$ch = curl_init();
		curl_setopt_array($ch, array(
				CURLOPT_URL => $url,
				CURLOPT_CUSTOMREQUEST => $type,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				//TODO: This prevents curl from detecting man in the middle attacks. Implement SSL cert verification instead of unsafely disabling it
		 		//CURLOPT_CAINFO => "PATH_TO/cacert.pem");
				CURLOPT_SSL_VERIFYHOST => 0,
				CURLOPT_SSL_VERIFYPEER => 0
		));
		
		if (isset($data)) {
			$data_json = json_encode($data);
			curl_setopt_array($ch, array(
					CURLOPT_POSTFIELDS => $data_json,
					CURLOPT_HTTPHEADER => array(
							"Accept: application/json",
							'Content-Type: application/json',
							'Content-Length: '.strlen($data_json))
			));
		}
		else {
			curl_setopt_array($ch, array(
					CURLOPT_HTTPHEADER => array(
							"Accept: application/json",
							'Content-Type: application/json',
							'Content-Length: 0')
			));
		}
		
		if (isset($username) && isset($password)) {
			curl_setopt($ch, CURLOPT_USERPWD, $username.":".$password);
		}

		$response = curl_exec($ch);

		$errno = curl_errno($ch);
		if ($errno) {
			$message;
			 
			if ($errno == 7) {
				$message = 'No MUC found at '.$url;
			}
			else {
				$error = curl_error($ch);
				$message = 'Communication failed: '.$error;
			}
			
			return array('success'=>false, 'message'=>$message);
		}

//	 	$resultStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//	 	if ($resultStatus != 200) {
//	 		// the request did not complete as expected. Common errors are 4xx (not found, bad request, etc.)
//	 		// and 5xx (usually concerning errors/exceptions in the remote script execution)
			
//	 	}

		curl_close($ch);

		if (!isset($response) || trim($response)==='') {
// 			return array('success'=>false, 'message'=>'Unknown error');
			return array('success'=>true);
		}

		$result = json_decode($response, true);
		return $result;
	}

	private function load_redis($userid)
	{
		$this->redis->delete("user:muc:$userid");
		$result = $this->mysqli->query("SELECT id, userid, address, description, password FROM muc WHERE userid = '$userid'");
		while ($row = (array) $result->fetch_object())
		{
			$this->redis->sAdd("user:muc:$userid", $row['id']);
			$this->redis->hMSet("muc:".$row['id'],array(
				'id'=>$row['id'],
				'userid'=>$row['userid'],
				'address'=>$row['address'],
				'description'=>$row['description'],
				'password'=>$row['password']
			));
		}
	}

	private function load_redis_muc($id)
	{
		$result = $this->mysqli->query("SELECT id,userid,address,description,password FROM muc WHERE id = '$id'");
		$row = (array) $result->fetch_object();
		if (!$row) {
			$this->log->warn("MUC model: Requested MUC with id=$id does not exist");
			return false;
		}
			
		$this->redis->hMSet("muc:".$id,array(
				'id'=>$id,
				'userid'=>$row['userid'],
				'address'=>$row['address'],
				'description'=>$row['description'],
				'password'=>$row['password']
		));
	}
}