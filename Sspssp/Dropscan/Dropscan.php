<?php
namespace Sspssp\Dropscan;

class Dropscan
{
	public function __construct()
	{
		$this->curl = new \Curl();
		#$curl->cookie_file = 'cookies';
	}
	public function login($user, $pw, $long = false)
	{
		$param = array("YumUserLogin"=>array("email" => $user, "password"=>$pw));
		if($long)
		{
			$param["YumUserLogin"]["rememberMe"]="on";
		}
		$res = $this->curl->post("https://secure.dropscan.de/login", $param);
		$this->boxes = $this->parseBoxIds($res->body);
		#var_dump($res);
	}
	
	public function getBoxIds()
	{
		return $this->boxes;
	}

	public function getInbox($boxid = NULL)
	{
		if($boxid == NULL)
		{
			$boxid = $this->boxes[0];
		}
		$res = $this->curl->get("https://secure.dropscan.de/scanbox/inbox?box=".$boxid);
		$letters = $this->getLetters($res);
		$letterDetails = array();
		foreach($letters as $letter)
		{
			$tmpDetails = $this->getLetterDates($letter, $res);
			$tmpDetails["id"] = $letter;
			$letterDetails[] = $tmpDetails;
		}
		return $letterDetails;
	}

	public function getScanns($boxid = NULL)
	{
		if($boxid == NULL)
		{
			$boxid = $this->boxes[0];
		}
		$res = $this->curl->get("https://secure.dropscan.de/scanbox/scans?box=".$boxid);
		$letters = $this->getLetters($res);
		$letterDetails = array();
		foreach($letters as $letter)
		{
			$tmpDetails = $this->getLetterDates($letter, $res);
			$tmpDetails["id"] = $letter;
			$letterDetails[] = $tmpDetails;
		}
		return $letterDetails;
	}
	public function getForwarded($boxid = NULL)
	{
		if($boxid == NULL)
		{
			$boxid = $this->boxes[0];
		}
		$res = $this->curl->get("https://secure.dropscan.de/scanbox/forwarded?box=".$boxid);
		$letters = $this->getLetters($res);
		$letterDetails = array();
		foreach($letters as $letter)
		{
			$tmpDetails = $this->getLetterDates($letter, $res);
			$tmpDetails["id"] = $letter;
			$letterDetails[] = $tmpDetails;
		}
		return $letterDetails;
	}
	public function getDestroyed($boxid = NULL)
	{
		if($boxid == NULL)
		{
			$boxid = $this->boxes[0];
		}
		$res = $this->curl->get("https://secure.dropscan.de/scanbox/destroyed?box=".$boxid);
		$letters = $this->getLetters($res);
		$letterDetails = array();
		foreach($letters as $letter)
		{
			$tmpDetails = $this->getLetterDates($letter, $res);
			$tmpDetails["id"] = $letter;
			$letterDetails[] = $tmpDetails;
		}
		return $letterDetails;
	}

	//Not Tested now
	public function scan($letter, $box = NULL)
	{
		if($box==NULL)
		{
			$box = $this->boxes[0];
		}
		$res = $this->curl->get("https://secure.dropscan.de/scanbox/request", array("request" => "scan", "box" => $box, "mailing" => $letter));
	}

	public function destroy($letter, $box = NULL)
	{
		if($box==NULL)
		{
			$box = $this->boxes[0];
		}
		$res = $this->curl->get("https://secure.dropscan.de/scanbox/request", array("request" => "destroy-mailing", "box" => $box, "mailing" => $letter));
	}

	public function getPreview($letterid, $size = 200)
	{
		$res = $this->curl->get("https://secure.dropscan.de/scanbox/front/?mailing=".$letterid."&size=".(int)$size);
		return $res->body;
	}

	public function getPDF($letterid, $box = NULL)
	{
		if($box==NULL)
		{
			$box = $this->boxes[0];
		}
		$res = $this->curl->get("https://secure.dropscan.de/scanbox/download", array("mailing" => $letterid, "box"=>$box, "step" => "3", "type"=>""));
		return $res->body;
	}

	//Helper Functions


	private function getLetters($res)
	{
		$regex = "@data-id=\'([a-z0-9]*)\'@";
		preg_match_all($regex, $res->body, $matches);
		return $matches[1];
	}
	private function getLetterDates($letterid, $res)
	{
		$tmpbody = substr($res->body, strpos($res->body, "data-id='".$letterid."'"));
		$tmpbody = substr($tmpbody, strpos($tmpbody, "<li class='time'>"));
		$tmpbody = substr($tmpbody, 0, strpos($tmpbody, "</ol>"));

		//Eingegangen
		$regex = "@Eingang am ([0-9]{2}\.[0-9]{2}\.[0-9]{2})@";
		preg_match($regex, $tmpbody, $matches);
		if(isset($matches[1])&&!empty($matches[1]))
		{
			$date["received"]=$matches[1];
		}

		//Gescannt
		$regex = "@Gescannt am ([0-9]{2}\.[0-9]{2}\.[0-9]{2})@";
		preg_match($regex, $tmpbody, $matches);
		if(isset($matches[1])&&!empty($matches[1]))
		{
			$date["scanned"]=$matches[1];
		}

		//Vernichtet
		$regex = "@Vernichtet am ([0-9]{2}\.[0-9]{2}\.[0-9]{2})@";
		preg_match($regex, $tmpbody, $matches);
		if(isset($matches[1])&&!empty($matches[1]))
		{
			$date["destroyed"]=$matches[1];
		}

		#var_dump($date);
		return $date;
	}

	private function parseBoxIds($requestBody)
	{
		$boxids = array();
		$regex = "@\/scanbox\/inbox\?box=([a-z0-9]*)@";
		preg_match_all($regex, $requestBody, $matches);
		foreach($matches[1] as $m) {
			if (!in_array($m, $boxids)) {
				$boxids[] = $m;
			}
		}
		return $boxids;
		#var_dump($matches);
	}


}