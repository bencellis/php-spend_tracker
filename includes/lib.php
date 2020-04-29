<?php

include_once('config.php');
require_once('db.php');

$version = '20200429-01';
$db = new dbfunctions($dbconfig);

function testdbconnection() {
	global $db;

	return $db->testconnection();
}

function processPageParams() {
	$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
	$perpage= isset($_REQUEST['perpage']) ? $_REQUEST['perpage'] : 20;
	$filter = isset($_REQUEST['accountid']) ? $_REQUEST['accountid'] : null;
	return array($page, $perpage, $filter);
}


function processFormdata() {
	$message = array();

	if ($action = empty($_POST['action']) ? null : $_POST['action']) {
		$message = array('text' => '', 'type' => 'bg-success');
		switch ($action){
			case 'processStatement' :
				if (file_exists($_FILES['statementfile']['tmp_name'])) {
					// We open the file.
					if (($processedlines = _processStatement()) !== null) {
						$message['text'] = "Successfully processed $processedlines lines";
					} else {
						$message['text'] = 'Error processing the uploaded file - Possibly incorrect format.';
						$message['type'] = 'bg-danger';
					}
				}else{
					$message['text'] = 'No file has been uploaded.';
					$message['type'] = 'bg-danger';
				}
				break;
			case 'changeAccount' :
				if (!empty($_POST['newaccountid'])) {
					if (updateTransactionAccount()) {
						$message['text'] = 'Account has been successfully changed.';
					} else{
						$message['text'] = 'Account has not been updated.';
						$message['type'] = 'bg-danger';
					}
				}else{
					$message['text'] = 'No account has been selected.';
					$message['type'] = 'bg-danger';
				}
				break;
			case 'createNewAccount' :
				if (!$_POST['type']) {
					$message['text'] = 'For new accounts, account type must be selected - please try again.';
					$message['type'] = 'bg-danger';
				} else if (empty($_POST['name'])) {
					$message['text'] = 'For new accounts, an account name is required. - please try again.';
					$message['type'] = 'bg-danger';
				} else {
					// Sanitise the input
					if (!empty($_POST['bankref'])) {
						$bankref = trim(filter_input(INPUT_POST, 'bankref', FILTER_SANITIZE_STRING));
					}else {
						$bankref = null;
					}
					if ($name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING))){
						if (createNewAccount($name, $_POST['type'], $bankref)) {
							$message['text'] = 'New account created.';
						} else {
							$message['text'] = 'Database error saving the account.';
							$message['type'] = 'bg-danger';
						}
					}else{
						$message['text'] = 'New account name is invalid.';
						$message['type'] = 'bg-danger';
					}
				}
				break;
			case 'getCSV' :
				$accountid = empty($_POST['accountid']) ? 0 : $_POST['accountid'];

				if (!sendCSVfile($accountid)) {
					$message['text'] = 'Error - creating CSV.';
					$message['type'] = 'bg-danger';
				}
				break;
			default :
				$message = array();
		}
	}
	return $message;
}

function sendCSVfile($accountid) {
	global $db;

	if ($result = $db->getBankAccountTransactions($accountid)) {

		$f = fopen('php://output', 'w');
		$first = true;
		$balance = 0;
		while ($record = $result->fetch_array(MYSQLI_ASSOC)) {
			$csvline = array();
			if ($first) {
				if ($accountid) {
					$filename = str_replace(' ', '_', strtolower($record['name']));
					$filename = preg_replace("/[^a-z0-9_]/", "", $filename) . '.csv';
				}else{
					$filename = 'alltransactions.csv';
				}

				header('Content-Type: application/csv; charset=UTF-8');
				header('Content-Disposition: attachment; filename="' . $filename . '";');

				$first = false;

				// Write the header line
				$csvline = array(
					'Date',
					'Transaction type',
					'Description',
					'Paid in',
					'Paid out',
					'Charge Account'
				);
				fputcsv($f, $csvline);
			}

			$balance = $balance + $record['amount'];

			if ($record['amount'] >= 0) {
				$out = number_format($record['amount'], 2);
				$in = '';
			}else{
				$in = number_format(($record['amount'] - ($record['amount'] * 2)), 2);
				$out = '';
			}

			if (!$record['Account']) {
				$record['Account'] = 'Unallocated';
			}

			$csvline = array(
				DateTime::createFromFormat('Y-m-d', $record['date'])->format('d/m/Y'),
				$record['transactiontype'],
				$record['description'],
				$out,
				$in,
				$record['Account']
			);

			fputcsv($f, $csvline);
		}

		// Balance Line
		if ($balance >= 0) {
			$out = number_format($balance, 2);
			$in = '';
		}else{
			$in = number_format(($balance - ($balance * 2)), 2);
			$out = '';
		}
		$csvline = array(
				'',
				'BALANCE',
				'',
				$out,
				$in,
				''
		);
		fputcsv($f, $csvline);

		fclose($f);
		exit;
	} else {
		return false;
	}
}

function createNewAccount($name, $type, $bankref = null) {
	global $db;
	return $db->createNewAccount($name, $type, $bankref);
}

function updateTransactionAccount() {
	global $db;

	return $db->updateTransactionAccount($_POST['recid'], $_POST['newaccountid']);
}

function _processStatement() {
	global $db;

	$lines = 0;

	// Today's date - we will no process any transactions for today.
	$todaysdate = new DateTime();
	$todaysdate->settime(0,0);

	// Get the last date we updated the
	if ($laststatementdate = $db->getLastStatementDate()) {
		// $laststatementdate - 2020-03-25
		$laststatementdate = DateTime::createFromFormat('Y-m-d', $laststatementdate);
	}

	$headerline = array('date', 'transactiontype', 'description', 'debit', 'credit', 'balance');
	$indata = false;

	if (($handle = fopen($_FILES['statementfile']['tmp_name'], "r")) !== FALSE) {
		while (($data = fgetcsv($handle)) !== FALSE) {
			// Try and determine if we have the right CSV.
			if (!$lines) {
				if ($data[0] !== 'Account Name:') {
					$lines = null;
					break;
				}
			}
			$lines++;

			if ($data[0] == 'Date') {
				$indata = true;
				continue;
			}
			if (!$indata) {
				continue;
			}
			$record = array();
			$flds = count($data);
			for ($c=0; $c < $flds; $c++) {
				$fldval = $data[$c];
				if (preg_match('/^.[0-9]+(\.[0-9]{1,2})?$/', $fldval)) {
					$record[$headerline[$c]] = filter_var($fldval, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
				}else {
					$record[$headerline[$c]] =  $fldval;
				}
			}

			saveStatementrecord($record, $todaysdate, $laststatementdate);
		}
		fclose($handle);
	}else{
		return null;
	}
	return $lines;
}

function saveStatementrecord($record, $todaysdate, $laststatementdate){
	global $db;

	// Check the date is not today
	$recorddate = DateTime::createFromFormat('d M Y', strtolower($record['date']));
	if ($recorddate < $todaysdate) {
		// Check the date is past the last statement date.
		if ((!$laststatementdate) || ($recorddate > $laststatementdate)) {
			unset($record['balance']);
			if ($record['debit']) {
				$amount = 0 - $record['debit'];
			} else {
				$amount = $record['credit'];
			}
			unset($record['debit']);
			unset($record['credit']);
			$record['amount'] = $amount;

			// Fix up the date for mysql
			$record['date'] = $recorddate->format('Y-m-d');

			// Here we want the id for the account transaction - but let's get somedata 1st.
			if ($acct = getTransactionAcct($record['description'])) {
				$record['account'] = $acct;
			}
			// Save into the database.
			$db->saveStatementRecord($record);
		}
	}

	return true;
}

function getTransactionAcct($string) {
	global $db;

	// strip away stuff
	$string = trim(str_replace('Bank credit', '', $string));

	$string = preg_replace('/Withdrawal.+$/', '', $string);
	$string = preg_replace('/Credit.+$/', '', $string);

	$newstring = $db->getTransactionAcct(trim($string));

	return $newstring;

}

function getTransactions() {
	global $db;

	list($page, $perpage) = processPageParams();

	// check no filter for accountid //
	if (isset($_POST['action']) && ($_POST['action'] != 'clearAccountFilter')) {
		$accountid = isset($_POST['accountid']) ? $_POST['accountid'] : null;
	}else{
		$accountid = null;
	}

	return $db->getTransactions($page, $perpage, $accountid);
}

function getAccounts() {
	global $db;
	return $db->getAccounts();
}

function makeHTMLOptions($excludeid = null) {
	$html = '';
	$accounts = getAccounts();
	foreach ($accounts as $accid => $account) {
		if ($accid != $excludeid) {
			$html .= "<option value='$accid'>" . $account['name'] . ' (' . $account['type'] . ")</option>\n"; ;
		}
	}
	return $html;
}

function getSummaryDetails() {
	global $db;
	$summary = array();

	$summary['allaccountbalance'] = $db->getAllTransactions();
	$summary['accountid'] = 0;
	if (!empty($_POST['accountid'])) {
		$summary['accountid'] = $_POST['accountid'];
		$summary['acctaccountbalance'] = $db->getAllTransactions($_POST['accountid']);
	}
	$summary['loanaccountbalance'] = ($db->getLoanAccountBalance()) * -1; // Change to postive.

	$summary['grandtotal'] = $summary['allaccountbalance'] + $summary['loanaccountbalance'];

	return $summary;
}

