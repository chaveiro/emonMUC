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

class Template
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

	public function get_list()
	{
		$templates = array();
		
		$contents = array();
		foreach (glob("Modules/device/template/*.json") as $file) {
			$content = json_decode(file_get_contents($file));
			$contents[basename($file, ".json")] = $content;
		}
		$templates['standalone'] = $contents;
		foreach (array_filter(glob('Modules/device/template/*'), 'is_dir') as $dir) {
			$contents = array();
			foreach (glob("Modules/device/template/".basename($dir)."/*.json") as $file) {
				$content = json_decode(file_get_contents($file));
				$contents[basename($file, ".json")] = $content;
			}
			$templates[basename($dir)] = $contents;
		}
		return $templates;
	}
	
	public function get($driver, $type)
	{
		if (isset($driver) && $driver === 'standalone') {
			$file = "Modules/device/template/".$type.".json";
		}
		else if (isset($driver)) {
			$file = "Modules/device/template/".$driver."/".$type.".json";
		}
		else return array('success'=>false, 'message'=>"Template file not found");
		
		return json_decode(file_get_contents($file));
	}

	public function init($userid, $mucid, $driver, $device)
	{
		$device = (array) json_decode($device);

		if (isset($device['type']) && $device['type']) {
			$result = $this->get($driver, $device['type']);
			if (isset($result->success) && $result->success !== true) {
				return $result;
			}
			$template = $result;
			
			$node = $device['nodeid'];
			$name = $device['name'];

			// Create feeds
			$feeds = $template->feeds;
			$result = $this->create_feeds($userid, $name, $node, $feeds);
			if ($result["success"] !== true) {
				return array('success'=>false, 'message'=>'Error while creating device feeds: ' . $result['message']);
			}

			// Create inputs
			if (isset($template->inputs)) {
				$inputs = $template->inputs;
				$result = $this->create_inputs($userid, $name, $node, $inputs);
				if ($result !== true) {
					return array('success'=>false, 'message'=>'Error while creating device inputs.');
				}
			}

			// Create channels
			if (isset($template->channels)) {
				$channels = $template->channels;
				$result = $this->create_channels($userid, $mucid, $name, $node, $channels);
				if ($result !== true) {
					return array('success'=>false, 'message'=>'Error while creating device channels.');
				}
				if (isset($inputs)) {
					$inputs = (object) array_merge((array) $inputs, (array) $channels);
				}
				else {
					$inputs = $channels;
				}
			}
			
			// Create inputs processes
			$result = $this->create_inputs_processes($feeds, $inputs);
			if ($result["success"] !== true) {
				return array('success'=>false, 'message'=>'Error while creating the inputs process list: ' . $result['message']);
			}
			
			// Create feeds processes
			$result = $this->create_feeds_processes($feeds, $inputs);
			if ($result["success"] !== true) {
				return array('success'=>false, 'message'=>'Error while creating device feeds process list: ' . $result['message']);
			}
		}
		return array('success'=>true, 'message'=>'Device initialized');
	}

	// Create the feeds
	private function create_feeds($userid, $device, $node, &$feedArray)
	{
		global $feed_settings;
		require_once "Modules/feed/feed_model.php";
		$feed = new Feed($this->mysqli, $this->redis, $feed_settings);

		foreach($feedArray as $f) {
			// Create each feed
			$name = str_replace("DEVICE", $device, $f->name);
			
			$id = $feed->get_id($userid, $name);
			if (!$id) {
				if (property_exists($f, "tag")) {
					$tag = $f->tag;
				} else {
					$tag = $node;
				}
				$datatype = constant($f->type); // DataType::
				$engine = constant($f->engine); // Engine::
				if (property_exists($f, "interval")) {
					$options_in[] = array();
					$options_in['interval'] = $f->interval;
				} else {
					$options_in = null;
				}
				$this->log->info("create_feeds() userid=$userid tag=$tag name=$name datatype=$datatype engine=$engine");
				$result = $feed->create($userid,$tag,$name,$datatype,$engine,$options_in);
				if($result["success"] !== true) {
					return $result;
				}
				$id = $result["feedid"];
			}
			$f->feedId = $id; // Assign the feed id to the feeds array
		}
		return array('success'=>true);
	}

	// Create the inputs
	private function create_inputs($userid, $device, $node, &$inputArray)
	{
		require_once "Modules/input/input_model.php";
		$input = new Input($this->mysqli, $this->redis, null);

		foreach($inputArray as $i) {
			// Create each input
			$name = str_replace("DEVICE", $device, $i->name);
			
			$description = $i->description;
			if(property_exists($i, "node")) {
				$nodeid = $i->node;
			} else {
				$nodeid = $node;
			}
			
			$result = $this->mysqli->query("SELECT id FROM input WHERE `nodeid` = '$nodeid' AND `name` = '$name'");
			if ($result->num_rows>0) {
				$row = $result->fetch_object();
				$inputId = $row->id;
			}
			else {
				$this->log->info("create_inputs() userid=$userid nodeid=$nodeid name=$name description=$description");
				$inputId = $input->create_input($userid, $nodeid, $name);
				if(!$input->exists($inputId)) {
					return false;
				}
				$input->set_fields($inputId, '{"description":"'.$description.'"}');
			}
			$i->inputId = $inputId; // Assign the created input id to the inputs array
		}
		return true;
	}

	// Create the channels
	private function create_channels($userid, $mucid, $device, $node, &$channelArray)
	{
		require_once "Modules/channel/channel_model.php";
		$channel = new Channel($this->mysqli, $this->redis);

		foreach($channelArray as $c) {
			// Create each channel
			$name = str_replace("DEVICE", $device, $c->name);
			$c->name = $name;

			if(property_exists($c, "node")) {
				$nodeid = $c->node;
			} else {
				$nodeid = $node;
			}
			
			$this->log->info("create_channels() userid=$userid nodeid=$nodeid name=$name description=$c->description");
			$channel->create($userid, $mucid, $device, json_encode($c));
			
			$result = $this->mysqli->query("SELECT id FROM input WHERE `nodeid` = '$nodeid' AND `name` = '$name'");
			if ($row = (array) $result->fetch_object()) {
				$c->inputId = $row['id']; // Assign the created input id to the channels array, acting as inputs
			}
		}
		return true;
	}

	// Create the inputs process lists
	private function create_inputs_processes($feedArray, $inputArray)
	{
		require_once "Modules/input/input_model.php";
		$input = new Input($this->mysqli, $this->redis, null);

		foreach($inputArray as $i) {
			// for each input
			if (isset($i->processList)) {
				$inputId = $i->inputId;
				$result = $this->convertTemplateProcessList($feedArray, $inputArray, $i->processList);
				if (isset($result["success"])) {
					return $result; // success is only filled if it was an error
				}

				$processes = implode(",", $result);
				if ($processes != "") {
					$this->log->info("create_inputs_processes() calling input->set_processlist inputId=$inputId processes=$processes");
					$input->set_processlist($inputId, $processes);
				}
			}
		}

		return array('success'=>true);
	}

	private function create_feeds_processes($feedArray, $inputArray)
	{
		global $feed_settings;
		require_once "Modules/feed/feed_model.php";
		$feed = new Feed($this->mysqli, $this->redis, $feed_settings);

		foreach($feedArray as $f) {
			// for each feed
			if ((@constant($f->engine) == Engine::VIRTUALFEED) && isset($f->processList)) {
				$feedId = $f->feedId;
				$result = $this->convertTemplateProcessList($feedArray, $inputArray, $f->processList);
				if (isset($result["success"])) {
					return $result; // success is only filled if it was an error
				}

				$processes = implode(",", $result);
				if ($processes != "") {
					$this->log->info("create_feeds_processes() calling feed->set_processlist feedId=$feedId processes=$processes");
					$feed->set_processlist($feedId, $processes);
				}
			}
		}

		return array('success'=>true);
	}
	
	// Converts template processList
	private function convertTemplateProcessList($feedArray, $inputArray, $processArray)
	{
		$resultProcesslist = array();
		if (is_array($processArray)) {
			require_once "Modules/process/process_model.php";
			$process = new Process(null,null,null,null);
			$process_list = $process->get_process_list(); // emoncms supported processes

			// create each processlist
			foreach($processArray as $p) {
				$proc_name = $p->process;
				if (!isset($process_list[$proc_name])) {
					$this->log->error("convertProcess() Process '$proc_name' not supported. Module missing?");
					return array('success'=>false, 'message'=>"Process '$proc_name' not supported. Module missing?");
				}

				// Arguments
				if(isset($p->arguments)) {
					if(isset($p->arguments->type)) {
						$type = @constant($p->arguments->type); // ProcessArg::
						$process_type = $process_list[$proc_name][1]; // get emoncms process ProcessArg

						if ($process_type != $type) {
							$this->log->error("convertProcess() Bad device template. Missmatch ProcessArg type. Got '$type' expected '$process_type'. process='$proc_name' type='".$p->arguments->type."'");
							return array('success'=>false, 'message'=>"Bad device template. Missmatch ProcessArg type. Got '$type' expected '$process_type'. process='$proc_name' type='".$p->arguments->type."'");
						}

						if (isset($p->arguments->value)) {
							$value = $p->arguments->value;
						} else {
							$this->log->error("convertProcess() Bad device template. Undefined argument value. process='$proc_name' type='".$p->arguments->type."'");
							return array('success'=>false, 'message'=>"Bad device template. Undefined argument value. process='$proc_name' type='".$p->arguments->type."'");
						}

						if ($type === ProcessArg::VALUE) {
						} else if ($type === ProcessArg::INPUTID) {
							$temp = $this->searchArray($inputArray,'name',$value); // return input array that matches $inputArray[]['name']=$value
							if ($temp->inputId > 0) {
								$value = $temp->inputId;
							} else {
								$this->log->error("convertProcess() Bad device template. Input name '$value' was not found. process='$proc_name' type='".$p->arguments->type."'");
								return array('success'=>false, 'message'=>"Bad device template. Input name '$value' was not found. process='$proc_name' type='".$p->arguments->type."'");
							}
						} else if ($type === ProcessArg::FEEDID) {
							$temp = $this->searchArray($feedArray,'name',$value); // return feed array that matches $feedArray[]['name']=$value
							if ($temp->feedId > 0) {
								$value = $temp->feedId;
							} else {
								$this->log->error("convertProcess() Bad device template. Feed name '$value' was not found. process='$proc_name' type='".$p->arguments->type."'");
								return array('success'=>false, 'message'=>"Bad device template. Feed name '$value' was not found. process='$proc_name' type='".$p->arguments->type."'");
							}
						} else if ($type === ProcessArg::NONE) {
							$value = 0;
						} else if ($type === ProcessArg::TEXT) {
//						} else if ($type === ProcessArg::SCHEDULEID) { //not supporte for now
						} else {
								$this->log->error("convertProcess() Bad device template. Unsuported argument type. process='$proc_name' type='".$p->arguments->type."'");
								return array('success'=>false, 'message'=>"Bad device template. Unsuported argument type. process='$proc_name' type='".$p->arguments->type."'");
						}

					} else {
						$this->log->error("convertProcess() Bad device template. Argument type is missing, set to NONE if not required. process='$proc_name' type='".$p->arguments->type."'");
						return array('success'=>false, 'message'=>"Bad device template. Argument type is missing, set to NONE if not required. process='$proc_name' type='".$p->arguments->type."'");
					}

					$this->log->info("convertProcess() process process='$proc_name' type='".$p->arguments->type."' value='" . $value . "'");
					$resultProcesslist[] = $proc_name.":".$value;

				} else {
					$this->log->error("convertProcess() Bad device template. Missing processlist arguments. process='$proc_name'");
					return array('success'=>false, 'message'=>"Bad device template. Missing processlist arguments. process='$proc_name'");
				}
			}
		}
		return $resultProcesslist;
	}

	private function searchArray($array, $key, $val) {
		foreach ($array as $item)
			if (isset($item->$key) && $item->$key == $val)
				return $item;
		return null;
	}
}