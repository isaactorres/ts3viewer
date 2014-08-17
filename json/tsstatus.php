<?php
/**
 * TSStatus
 * @author Sebastien Gerard <seb@sebastien.me>
 * @link http://tsstatus.sebastien.me
 * TeamSpeak 3 Viewer
 * @author Isaac Torres
 * @link http://github.com/isaactorres
 * @version 2014-08-16
 **/

class TSStatus
{
	private $_host;
	private $_queryPort;
	private $_serverDatas;
	private $_channelDatas;
	private $_userDatas;
	private $_serverGroupFlags;
	private $_channelGroupFlags;
	private $_login;
	private $_password;
	private $_cacheFile;
	private $_cacheTime;
	private $_channelList;
	private $_useCommand;
	private $_socket;

	public $timeout;
	public $hideEmptyChannels;
	public $hideParentChannels;

	public function TSStatus($host, $queryPort)
	{
		$this->_host = $host;
		$this->_queryPort = $queryPort;

		$this->_socket = null;
		$this->_serverDatas = array();
		$this->_channelDatas = array();
		$this->_userDatas = array();
		$this->_serverGroupFlags = array();
		$this->_channelGroupFlags = array();
		$this->_login = false;
		$this->_password = false;
		$this->_cacheTime = 0;
		$this->_cacheFile = __FILE__ . ".cache";
		$this->_channelList = array();
		$this->_useCommand = "use port=9987";

		$this->timeout = 2;
		$this->hideEmptyChannels = false;
		$this->hideParentChannels = false;
	}

	public function useServerId($serverId)
	{
		$this->_useCommand = "use sid=$serverId";
	}

	public function useServerPort($serverPort)
	{
		$this->_useCommand = "use port=$serverPort";
	}

	public function setLoginPassword($login, $password)
	{
		$this->_login = $login;
		$this->_password = $password;
	}

	public function setCache($time, $file = false)
	{
		$this->_cacheTime = $time;
		if($file !== false) $this->_cacheFile = $file;
	}

	public function setServerGroupFlag($serverGroupId, $name)
	{
		$this->_serverGroupFlags[$serverGroupId] = $this->toHTML($name);
	}

	public function setChannelGroupFlag($channelGroupId, $name)
	{
		$this->_channelGroupFlags[$channelGroupId] = $this->toHTML($name);
	}

	public function limitToChannels($channel_whitelist)
	{
		$this->_channelList = explode(',', $channel_whitelist);
	}

	private function ts3decode($str, $reverse = false)
	{
		$find = array('\\\\', 	"\/", 		"\s", 		"\p", 		"\a", 	"\b", 	"\f", 		"\n", 		"\r", 	"\t", 	"\v");
		$rplc = array(chr(92),	chr(47),	chr(32),	chr(124),	chr(7),	chr(8),	chr(12),	chr(10),	chr(3),	chr(9),	chr(11));

		if(!$reverse) return str_replace($find, $rplc, $str);
		return str_replace($rplc, $find, $str);
	}

	private function toHTML($string)
	{
		return htmlentities($string, ENT_QUOTES, "UTF-8");
	}

	private function sortUsers($a, $b)
	{
		if($a["client_talk_power"] != $b["client_talk_power"]) return $a["client_talk_power"] > $b["client_talk_power"] ? -1 : 1;
		return strcasecmp($a["client_nickname"], $b["client_nickname"]);
	}

	private function parseLine($rawLine)
	{
		$datas = array();
		$rawItems = explode("|", $rawLine);
		foreach ($rawItems as $rawItem)
		{
			$rawDatas = explode(" ", $rawItem);
			$tempDatas = array();
			foreach($rawDatas as $rawData)
			{
				$ar = explode("=", $rawData, 2);
				$tempDatas[$ar[0]] = isset($ar[1]) ? $this->ts3decode($ar[1]) : "";
			}
			$datas[] = $tempDatas;
		}
		return $datas;
	}

	private function sendCommand($cmd)
	{
		fputs($this->_socket, "$cmd\n");
		$response = "";
		do
		{
			$response .= fread($this->_socket, 8096);
		}while(strpos($response, 'error id=') === false);
		if(strpos($response, "error id=0") === false)
		{
			throw new Exception("TS3 Server returned the following error: " . $this->ts3decode(trim($response)));
		}
		return $response;
	}

	private function queryServer()
	{
		$this->_socket = @fsockopen($this->_host, $this->_queryPort, $errno, $errstr, $this->timeout);
		if($this->_socket)
		{
			@socket_set_timeout($this->_socket, $this->timeout);
			$isTs3 = trim(fgets($this->_socket)) == "TS3";
			if(!$isTs3) throw new Exception("Not a Teamspeak 3 server/bad query port");

			if($this->_login !== false)
			{
				$this->sendCommand("login client_login_name=" . $this->_login . " client_login_password=" . $this->_password);
			}

			$response = "";
			$response .= $this->sendCommand($this->_useCommand);
			$response .= $this->sendCommand("serverinfo");
			$response .= $this->sendCommand("channellist -topic -flags -voice -limits");
			$response .= $this->sendCommand("clientlist -uid -away -voice -groups");
			$response .= $this->sendCommand("servergrouplist");
			$response .= $this->sendCommand("channelgrouplist");

			$this->disconnect();
			return $response;
		}
		else throw new Exception("Socket error: $errstr [$errno]");
	}

	private function disconnect()
	{
		@fputs($this->_socket, "quit\n");
		@fclose($this->_socket);
	}

	private function update()
	{
		$response = $this->queryServer();
		$lines = explode("error id=0 msg=ok\n\r", $response);
		if(count($lines) == 7)
		{
			$this->_serverDatas = $this->parseLine($lines[1]);
			$this->_serverDatas = $this->_serverDatas[0];

			$tmpChannels = $this->parseLine($lines[2]);
			$hide = count($this->_channelList) > 0 || $this->hideEmptyChannels;
			foreach ($tmpChannels as $channel)
			{
				$channel["show"] = !$hide;
				$this->_channelDatas[$channel["cid"]] = $channel;
			}

			$tmpUsers = $this->parseLine($lines[3]);
			usort($tmpUsers, array($this, "sortUsers"));
			foreach ($tmpUsers as $user)
			{
				if($user["client_type"] == 0)
				{
					if(!isset($this->_userDatas[$user["cid"]])) $this->_userDatas[$user["cid"]] = array();
					$this->_userDatas[$user["cid"]][] = $user;
				}
			}

			$serverGroups = $this->parseLine($lines[4]);
			foreach ($serverGroups as $sg) $this->setServerGroupFlag($sg["sgid"],  $sg["name"] );

			$channelGroups = $this->parseLine($lines[5]);
			foreach ($channelGroups as $cg) $this->setChannelGroupFlag($cg["cgid"], $cg["name"] );
		}
		else throw new Exception("Invalid server response");
	}

	private function setShowFlag($channelIds)
	{
		if(!is_array($channelIds)) $channelIds = array($channelIds);
		foreach ($channelIds as $cid)
		{
			if(isset($this->_channelDatas[$cid]))
			{
				$this->_channelDatas[$cid]["show"] = true;
				if(!$this->hideParentChannels && $this->_channelDatas[$cid]["pid"] != 0)
				{
					$this->setShowFlag($this->_channelDatas[$cid]["pid"]);
				}
			}
		}
	}

	private function getCache()
	{
		if($this->_cacheTime > 0 && file_exists($this->_cacheFile) && (filemtime($this->_cacheFile) + $this->_cacheTime >= time()) )
		{
			return file_get_contents($this->_cacheFile);
		}
		return false;
	}

	private function saveCache($content)
	{
		if($this->_cacheTime > 0)
		{
			if(!@file_put_contents($this->_cacheFile, $content))
			{
				throw new Exception("Unable to write to file: " . $this->_cacheFile);
			}
		}
	}

	private function renderUsers($channelId)
	{
		$content = "";
		if(isset($this->_userDatas[$channelId]))
		{
			foreach ($this->_userDatas[$channelId] as $user)
			{
				if($user["client_type"] == 0)
				{
					$client_id = $user["clid"];
					$name = $this->toHTML($user["client_nickname"]);

					$status = "online";
					if($user["client_away"] == 1) $status = "away";
					else if($user["client_flag_talking"] == 1) $status = "speaking";
					else if($user["client_output_hardware"] == 0) $status = "speakers_muted_hardware";
					else if($user["client_output_muted"] == 1) $status = "speakers_muted";
					else if($user["client_input_hardware"] == 0) $status = "microphone_muted_hardware";
					else if($user["client_input_muted"] == 1) $status = "microphone_muted";

					$flags = "[";
					if(isset($this->_channelGroupFlags[$user["client_channel_group_id"]]))
					{
						$flags .= '{"channel_group": "' . $this->_channelGroupFlags[$user["client_channel_group_id"]] . '"},';
					}
					$serverGroups = explode(",", $user["client_servergroups"]);
					foreach ($serverGroups as $serverGroup)
					{
						if(isset($this->_serverGroupFlags[$serverGroup]))
						{
							$flags .= '{"server_group": "' . $this->_serverGroupFlags[$serverGroup]. '"},';
						}
					}
					if(substr($flags, -1) === ",") $flags = substr($flags, 0, -1) . "]";
					else $flags .= "]";
					
					$content .= <<<JSON
{
	"client_id": "$client_id",
	"name": "$name",
	"status": "$status",
	"flags": $flags
},
JSON;
				}
			}
		}
		return $content;
	}

	private function renderChannels($channelId)
	{
		$host = $this->_host;
		$port = $this->_serverDatas["virtualserver_port"];
		$server_link = "ts3server://$host?port=$port";
		$content = "";
		foreach ($this->_channelDatas as $channel)
		{
			if($channel["pid"] == $channelId)
			{
				if($channel["show"])
				{
					$channel_id = $channel["cid"];
					$name = $this->toHTML($channel["channel_name"]);
					$topic = $this->toHTML($channel["channel_topic"]);
					$link = $server_link . "&cid=$channel_id";
					
					$status = "normal";
					if( $channel["channel_maxclients"] > -1 && ($channel["total_clients"] >= $channel["channel_maxclients"])) $status = "max_clients";
					else if( $channel["channel_maxfamilyclients"] > -1 && ($channel["total_clients_family"] >= $channel["channel_maxfamilyclients"])) $status = "max_clients";
					else if($channel["channel_flag_password"] == 1) $status = "password_protected";

					$flags = "[";
					if($channel["channel_flag_default"] == 1) $flags .= '{"flag": "default"},';
					if($channel["channel_needed_talk_power"] > 0) $flags .= '{"flag": "moderated"},';
					if($channel["channel_flag_password"] == 1) $flags .= '{"flag": "password_protected"}';
					if(substr($flags, -1) === ",") $flags = substr($flags, 0, -1) . "]";
					else $flags .= "]";

					$clients = "[";
					$clients .= $this->renderUsers($channel["cid"]);
					if(substr($clients, -1) === ",") $clients = substr($clients, 0, -1) . "]";
					else $clients .= "]";

					$childs = "[";
					$childs .= $this->renderChannels($channel["cid"]);
					if(substr($childs, -1) === ",") $childs = substr($childs, 0, -1) . "]";
					else $childs .= "]";

					$cid = $channel["cid"];

					$content .= <<<JSON
{
	"channel_id": "$channel_id",
	"name": "$name",
	"topic": "$topic",
	"link": "$link",
	"status": "$status",
	"flags": $flags,
	"clients": $clients,
	"channels": $childs
},
JSON;
				}
				else $content .= $this->renderChannels($channel["cid"]);
			}
		}
		return $content;
	}

	public function render()
	{
		try
		{
			$cache = $this->getCache();
			if($cache != false) return $cache;

			$this->update();

			if($this->hideEmptyChannels && count($this->_channelList) > 0) $this->setShowFlag(array_intersect($this->_channelList, array_keys($this->_userDatas)));
			else if($this->hideEmptyChannels) $this->setShowFlag(array_keys($this->_userDatas));
			else if(count($this->_channelList) > 0) $this->setShowFlag($this->_channelList);

			$server_id = $this->_serverDatas["virtualserver_id"];
			$host = $this->_host;
			$ip = $this->_serverDatas["virtualserver_ip"];
			$port = $this->_serverDatas["virtualserver_port"];
			$name = $this->toHTML($this->_serverDatas["virtualserver_name"]);
			$link = "ts3server://$host?port=$port";
			$host_url = $this->_serverDatas["virtualserver_hostbanner_url"];
			$image_url = $this->_serverDatas["virtualserver_hostbanner_gfx_url"];

			$channels = "[";
			$channels .= $this->renderChannels(0);
			if(substr($channels, -1) === ",") $channels = substr($channels, 0, -1) . "]";
			else $channels .= "]";

			$content = <<<JSON
{
	"server_id": "$server_id",
	"host": "$host",
	"ip": "$ip",
	"port": "$port",
	"name": "$name",
	"link": "$link",
	"host_url": "$host_url",
	"image_url": "$image_url",
	"channels": $channels
}
JSON;
			$this->saveCache($content);
		}
		catch (Exception $ex)
		{
			$this->disconnect();
			$error = trim(preg_replace('/\s+/', ' ', $ex->getMessage()));
			$error = str_replace("\"", "'", $error);
			$content = '{"error": "' . $error . '"}';
		}
		return $content;
	}
}
?>