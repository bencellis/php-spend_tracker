<?php

class dbfunctions {

	private $mysqli = null;

	private $cacheaccts = array();

	private $cacheaccountcounts = array();

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
		die("(" . $this->mysqli->errno . ") " . $this->mysqli->error);
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

	public function getAccounts() {
		$sql = "SELECT * FROM accounts ORDER BY name ASC";
		$records = $this->mysqli->query($sql);
		return $this->_resultToArray($records);
	}

	public function getTransactions($page = 1, $perpage = 20, $accountid = null) {
		if (($page = $page - 1) < 0) {
			$page = 0;
		}
		$startrecord = $page * $perpage;
		// save for the pagination
		$this->_getTransactionCount($accountid);

		$sql = "SELECT * FROM bankaccount";
		if ($accountid) {
			$sql .= " WHERE account = $accountid";
		} else if ($accountid !== null) {
			$sql .= " WHERE account IS NULL";
		}
		$sql .= " ORDER BY `date` DESC
					LIMIT $startrecord, $perpage";

		$result = $this->mysqli->query($sql);
		return $this->_resultToArray($result);
	}

	private function _getTransactionCount($accountid = 0) {

		if (!empty($this->cacheaccountcounts[$accountid])) {
			$count = $this->cacheaccountcounts[$accountid];
		}else{
			$sql = "SELECT COUNT(*) as reccnt FROM bankaccount WHERE account ";
			if ( $accountid == 0 ) {
				$sql .= 'IS NULL';
			}else{
				$sql .= "= $accountid";
			}
			$result = $this->mysqli->query($sql);
			$record = $result->fetch_row();
			$count = $record[0];
			$this->cacheaccountcounts[$accountid] = $count;
		}

		return $count;
	}

	private function _resultToArray($dbresult) {
		// check for error 1st
		$results = array();
		if ($this->mysqli->errno) {
			$this->getLastError();
		} else {
			while ($record = $dbresult->fetch_array(MYSQLI_ASSOC)) {
				if (isset($record['recid']) || isset($record['id'])) {
					$id = isset($record['recid']) ? $record['recid'] : $record['id'];
					$results[$id] = $record;
				} else {
					$results[] = $record;
				}
			}
		}
		return $results;
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

	public function updateTransactionAccount($recid, $newaccountid) {
		$sql = "UPDATE bankaccount SET account = $newaccountid WHERE recid = $recid";
		return $this->mysqli->query($sql);
	}

	public function getAllTransactions($accountid = null) {
		$sql = "SELECT SUM(amount) FROM bankaccount";

		if ($accountid) {
			$sql .= " WHERE account = $accountid";
		}

		$record = $this->mysqli->query($sql);
		if ($result = $record->fetch_row()) {
			return $result[0];
		}else{
			return null;
		}
	}

	public function getLoanAccountBalance() {
		$sql = "SELECT SUM(amount)
					FROM bankaccount
					WHERE account IN (
						SELECT recid FROM accounts WHERE type = 'Loan'
					)";
		$record = $this->mysqli->query($sql);
		if ($result = $record->fetch_row()) {
			return $result[0];
		}else{
			return null;
		}
	}


}