<?php

include_once('config.php');
require_once('db.php');

$db = new dbfunctions($dbconfig);

function testdbconnection() {
	global $db;

	return $db->testconnection();
}

function processPageParams() {
	$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
	$perpage= isset($_REQUEST['perpage']) ? $_REQUEST['perpage'] : 20;
	return array($page, $perpage);
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
		case 'viewAccount' :
		case 'changePagination' :
		case 'clearAccountFilter' :
			break;		// dealth with elsewhere.
		default :
			die('<pre>' . print_r($_REQUEST, true) . '</pre>');
	}

	return $message;

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
	if (!empty($_POST['accountid'])) {
		$summary['acctaccountbalance'] = $db->getAllTransactions($_POST['accountid']);
	}
	$summary['loanaccountbalance'] = $db->getLoanAccountBalance();
	$summary['grandtotal'] = $summary['allaccountbalance'] + $summary['loanaccountbalance'];

	return $summary;
}


function getPagingHTML($url, $page, $perpage, $recordcount, array $otherparams = array()) {
	$html = '';

	if ($recordcount < $perpage) {
		return $html;		// No paging bar.
	}

	if (($page * $perpage) > $recordcount) {
		return $html;
	}

	$extraparams = '';
	if (!empty($otherparams)) {
		foreach ($otherparams as $key => $val) {
			if (!empty($val)) {
				if ($extraparams) {
					$extraparams . '&';
				}
				$extraparams .= $key . '=' . $val;
			}
		}
	}

	$maxpages = (int) ceil($recordcount/$perpage);
	$maxlinks = 13;
	$halfway = floor($maxlinks/2);
	$pagelinks = array();

	// if we have less than the maximum number of
	if ($maxpages <= $maxlinks) {
		for ($i = 0; $i < $maxlinks; $i++) {
			$pagelinks[$i] = $i + 1;
		}
	} else {
		for ($page = 1; $page < $maxpages + 1; $page++) {
			$pagelinks = array();

			// Default
			$startindex = $page;
			$endindex = $startindex + ($maxlinks - $startindex);

			//4 scenarios

			// when page is greater than halfway index is page less halfway to maxitems

			// when page is less than

			// when page is maxpages - $half

			if ($page <= ($halfway + 1)) {				// when page is less that halfway - index is 1 - 13
				$startindex = 1;
				$endindex = $startindex + ($maxlinks - $startindex);
				if ($endindex < $maxlinks) {
					$pagelinks['next'] = $endindex;
					$endindex--;
				}
			} else if ($page >= ($maxpages - $halfway)) {
				$endindex = $maxpages;
				$startindex = $endindex - $maxlinks;
				if ($startindex > 1) {
					// Previous link
					$pagelinks['previous'] = $startindex;
					$startindex++;
				}
			} else if ($page < ($maxpages - $halfway)) {
				$startindex = $page - ($halfway + 1);
				if ($startindex > 1) {
					// Previous link
					$pagelinks['previous'] = $startindex;
					$startindex++;
				}
				$endindex = $page + $halfway;
				if ($endindex > $maxlinks) {
					$pagelinks['next'] = $endindex;
					$endindex--;
				}
			} else if ($page > $halfway) {
				$startindex = $page - ($halfway + 1);
				if ($startindex > 1) {
					// Previous link
					$pagelinks['previous'] = $startindex;
					$startindex++;
				}
				$endindex = $page + $halfway;
				if ($endindex > $maxlinks) {
					$pagelinks['next'] = $endindex;
					$endindex--;
				}
			}

 			echo '<pre>' . "$startindex\t$endindex\n" . '</pre>';
// 			continue;
			for ($i = $startindex; $i < ($endindex + 1); $i++) {
					$pagelinks[$i] = $i;
			}

			echo "<pre>Page: $page\tPer Page: $perpage\tMax Pages: $maxpages\tTotal Recs: $recordcount</pre>\n";
			echo '<pre>' . print_r($pagelinks, true) . '</pre>';
			echo '<hr />';
		}

	}



	$html = '
	<nav class="navbar navbar-expand-lg navbar-light bg-light">
    	<a class="navbar-brand">Go To Page:</a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="navbarNavAltMarkup">
			<div class="navbar-nav">';

	$html . "<form class='form-inline' action='$url' method='post'>
				<input type='hidden' name='perpage' value='$perpage' />
				<input type='hidden' name='recordcount' value='$recordcount' />
		";

// 	<a class="nav-item nav-link" href="#">
// 	<i class="fas fa-hand-point-left"></i>
// 	</a>
// 	<a class="nav-item nav-link disabled" href="#">10</a>
// 	<a class="nav-item nav-link" href="#">11</a>
// 	<a class="nav-item nav-link" href="#">12</a>
// 	<a class="nav-item nav-link" href="#">13</a>
// 	<a class="nav-item nav-link" href="#">14</a>
// 	<a class="nav-item nav-link" href="#">15</a>
// 	<a class="nav-item nav-link" href="#">16</a>
// 	<a class="nav-item nav-link" href="#">17</a>
// 	<a class="nav-item nav-link" href="#">18</a>
// 	<a class="nav-item nav-link" href="#">19</a>
// 	<a class="nav-item nav-link" href="#">
// 	<i class="fas fa-hand-point-right"></i>
// 	</a>

	$html .= '
				</form>
			</div>
		</div>
	</nav>';

	return $html;
}
