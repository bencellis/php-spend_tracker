<?php

class dbfunctions {

	private $mysqli = null;

	private $cacheaccts = array();

	public function __construct(array $settings, $forcenew = false) {
		// TODO if we already exist return existing connection
		$message = '';
		$this->mysqli = new mysqli($settings['dbserver'], $settings['dbuser'], $settings['dbpasswd'], $settings['dbname'], $settings['dbport']);
		if ($this->mysqli->connect_errno) {
			$message =  "Failed to connect to MySQL: (" .  $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error;
		}elseif ($this->mysqli->error) {
			$message =  "Failed to connect to MySQL: (" . $this->mysqli->errno . ") " . $this->mysqli->error;
		}

		if ($message) {
			die($message);
		}
	}

	public function getLastError() {
		return "(" . $this->mysqli->errno . ") " . $this->mysqli->error;
	}

	public function closedb() {
		$this->mysqli->disconnect();
	}

	public function testconnection() {
		return $this->mysqli->get_server_info();
	}

	public function getLastStatementDate() {
		$sql = "SELECT MAX(date) FROM bankaccount";

		$record = $this->mysqli->query($sql);
		if ($result = $record->fetch_row()) {
			return $result[0];
		}else{
			return null;
		}
	}

	public function saveStatementRecord($record) {
		$ssql = '';
		foreach ($record as $fld => $value) {
			if ($ssql) {
				$ssql .= ', ';
			}
			if (is_numeric($value)) {
				$ssql .= "$fld = $value";
			}else{
				$ssql .= "$fld = '$value'";
			}
		}
		$sql = 'INSERT INTO bankaccount SET ' . $ssql;

		if (!$this->mysqli->query($sql)) {
			die($this->getLastError());
		}

	}

	public function getTransactionAcct($str) {
		if (isset($this->cacheaccts[$str])) {
			return $this->cacheaccts[$str];
		} else {
			$sql = "SELECT recid FROM accounts WHERE bankref LIKE '%$str%'";
			$record = $this->mysqli->query($sql);
			if ($result = $record->fetch_row()) {
				$this->cacheaccts[$str] = $result[0];
				return $result[0];
			}else{
				return null;
			}
		}
	}
}