<?php

include_once('config.php');
require_once('db.php');

$db = new dbfunctions($dbconfig);

function testdbconnection() {
	global $db;

	return $db->testconnection();
}

function processFormdata() {
	$message = array('text' => '', 'type' => 'bg-success');

	switch ($_POST['action']){
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
	}

	return $message;

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

function  saveStatementrecord($record, $todaysdate, $laststatementdate){
	global $db;

	// Check the date is not today
	$recorddate = DateTime::createFromFormat('d M Y', strtolower($record['date']));
	if ($recorddate < $todaysdate) {
		// Check the date is past the last statement date.
		if ((!$laststatementdate) || ($recorddate > $laststatementdate)) {
			unset($record['balance']);
			if ($record['debit']) {
				$amount = $record['debit'];
			} else {
				$amount = 0 - $record['credit'];
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