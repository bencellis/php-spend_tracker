<?php

require_once('includes/lib.php');

// Deal with forms.
if (!empty($_POST)) {
	$message = processFormdata();
}

include_once ('includes/header.php');
require_once('includes/paginator.php');

$message = null;

$postback = strtok($_SERVER["REQUEST_URI"], '?');; 	// Remove any get params.

list($page, $perpage, $filter) = processPageParams();
$ishome = ($page == 1);

$accounts = getAccounts();

//die('<pre>' . print_r($accounts, true) . '</pre>');

// All processing done we can move on.
list ($recordcount, $transactions) = getTransactions();

$htmloptions = makeHTMLOptions();

$summary = $ishome ? getSummaryDetails() : array();
$balances = $ishome ? getBalanceDetails() : array();

?>

<div class="container">
	<div class="row">
		<div class="col">
			<div class="row">
				<div class="col">
					<h1><?php echo $title; ?></h1>
				</div>
			</div>
			<div class="row">
				<div class="col">
					<?php include_once('includes/navigation.php'); ?>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col">
			<br />
		</div>
	</div>

	<?php if (!empty($message['text'])): ?>
	<div class="row">
		<div class="col">
			<p class="<?php echo $message['type']; ?> p-3 mb-2 text-white">
				<strong><?php echo $message['text']; ?></strong>
			</p>
		</div>
	</div>
	<?php endif;?>

	<?php if (count($summary)): ?>
	<div class="row">
		<div class="col">
				<div class="card">
					<div class="card-body">
						<h5 class="card-title">Account Summary</h5>
						<p class="card-text">
							Cash Balance: £<?php echo number_format($summary['allaccountbalance'], 2); ?><br />
							Loan Balance: £<?php echo number_format($summary['loanaccountbalance'], 2); ?>
						</p>
						<p class="card-text mb-2 text-success"><strong>Fund Total:</strong> £<?php echo number_format($summary['grandtotal'], 2); ?></p>
						<?php if (!empty($summary['acctaccountbalance'])): ?>
							<p class="card-text text-info"><strong>This Account Total:</strong> £<?php echo number_format($summary['acctaccountbalance'], 2); ?>
						<?php endif; ?>
							<form class="form-inline" method="post" action="<?php echo $postback;?>" >
								<div class="hidden">
									<input type="hidden" name="accountid" value="<?php echo $summary['accountid']; ?>" />
									<input type="hidden" name="page" value="<?php echo $page; ?>" />
									<input type="hidden" name="perpage" value="<?php echo $perpage; ?>" />
									<input type="hidden" name="recordcount" value="<?php echo $recordcount; ?>" />
								</div>
								<div class="form-group">
									<button type="submit" name="action" value="getCSV" class="btn btn-success">Download CSV</button>
								</div>
							</form>
					</div>
				</div>
			</div>
			<div class="col">
				<div class="card">
					<div class="card-body">
						<h5 class="card-title">Share Summary</h5>
						<table class="table table-striped table-sm">
							<thead class="thead-dark">
								<tr class="text-center">
									<th scope="col">Name</th>
									<th scope="col">Share</th>
									<th scope="col">Loan Balance</th>
									<th scope="col">Share Value</th>
								</tr>
							</thead>
							<?php $eachshare = $summary['grandtotal']/count($balances); ?>
							<?php foreach ($balances as $balance): ?>
								<tr>
									<td><?php echo $balance['name']; ?></td>
									<td class="text-right"><?php echo number_format($eachshare, 2); ?></td>
									<td class="text-right"><?php echo number_format($balance['balance'], 2); ?></td>
									<td class="text-right"><?php echo number_format(($eachshare + $balance['balance']), 2); ?>
								</tr>
							<?php endforeach;?>
						</table>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<div class="row">
		<div class="col">
			<hr />
		</div>
	</div>
	<?php foreach ($transactions as $recid => $transaction): ?>
		<div class="row">
			<div class="col">
				<div class="row">
					<div class="col">
						<strong>Date: </strong><?php echo date('d/m/Y', strtotime($transaction['date'])); ?>
					</div>
				</div>
				<div class="row">
					<div class="col">
						<strong>Transaction Type:</strong><br /> <?php echo $transaction['transactiontype']; ?>
					</div>
				</div>
				<div class="row">
					<div class="col">
						<strong>Description:</strong><br /> <?php echo $transaction['description']; ?>
					</div>
				</div>
				<div class="row">
					<div class="col">
						<strong>Income:</strong>
							<?php
							if ($transaction['amount'] > 0) {
								echo '£' . number_format($transaction['amount'], 2);
							}else{
								echo '&nbsp;';
							}
							?>
					</div>
				</div>
				<div class="row">
					<div class="col">
						<strong>Spend:</strong>
							<?php
							if ($transaction['amount'] < 0) {
								echo '£' . number_format(($transaction['amount'] * -1), 2);
							}else{
								echo '&nbsp;';
							}
							?>
					</div>
				</div>

				<?php if ($transaction['account']): ?>
					<div class="row align-items-end">
						<div class="col">
							<strong>Account:</strong>
							<?php echo $accounts[$transaction['account']]['name']?>&nbsp;
							(<?php echo $accounts[$transaction['account']]['type']?>)
						</div>
					</div>
				<?php else: ?>
					<div class="row align-items-end">
						<div class="col">
							<strong>Account:</strong> Not Allocated.
						</div>
					</div>
				<?php endif; ?>
				<div class="row p-2">
					<div class="col">
						<form class="form-inline" method="post" action="<?php echo $postback;?>" >
							<div class="hidden">
								<input type="hidden" name="action" value="changeAccount" />
								<input type="hidden" name="recid" value="<?php echo $recid; ?>" />
								<input type="hidden" name="page" value="<?php echo $page; ?>" />
								<input type="hidden" name="perpage" value="<?php echo $perpage; ?>" />
								<?php if ($filter) :?>
									<input type="hidden" name="accountid" value="<?php echo $filter; ?>" />
								<?php endif; ?>
							</div>
							<strong>New Account: &nbsp;</strong>
							<div class="row align-items-end">
								<div class="col">
									<div class="form-group">
										<select name="newaccountid" class="form-control">
											<option value="0">-- Make a selection --</option>
											<?php echo $htmloptions; ?>
										</select>
									</div>
								</div>
								<div class="col-2">
									<div class="form-group">
										<button class="btn btn-outline-primary" type="submit" name="action" value="changeAccount"
												title="Save Selection">
											<i class="fas fa-check"></i>
										</button>
									</div>
								</div>
								<div class="col-2">
									<div class="form-group">
										<button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#addAcctModal" title="Add Account">
											<i class="fas fa-plus text-danger"></i>
										</button>
									</div>
								</div>
							</div> <!-- end form row -->
						</form>
					</div>
				</div>
			</div> <!-- end col -->
		</div> <!-- end row -->
		<div class="row">
			<div class="col">
				<hr />
			</div>
		</div>
	<?php endforeach; ?>

	<!-- Paging area -->
	<?php
		$params = array();
		if ($filter) {
			$params = array('accountid' => $filter);
		}
		echo getPagingHTML($postback, 7, $page, $perpage, $recordcount, $params);
	?>

	<div class="row">
		<div class="col">
			<hr />
		</div>
	</div>

	<div class="row bg-light">
		<div class="col">
			<h4>Options:</h4>
		</div>
	</div>

	<div class="row bg-light" id="my-page-footer">
		<div class="col">
			<div class="row align-items-end" id="my-page-footer-1">
				<form class="form-inline w-100 p-2" method="post" action="<?php echo $postback;?>" >
					<div class="col-8">
						<div class="form-group">
							<select name="accountid" class="form-control" title="Select Account to filter by" aria-label="Select Account to filter by">
								<option value="0">No Account Specified</option>
								<?php echo $htmloptions; ?>
							</select>
							<input type="hidden" name="page" value="<?php echo $page; ?>" />
							<input type="hidden" name="perpage" value="<?php echo $perpage; ?>" />
							<input type="hidden" name="recordcount" value="<?php echo $recordcount; ?>" />
						</div>
					</div>
					<div class="col-2">
						<div class="form-group">
							<button class="btn btn-outline-primary" type="submit" name="action" value="viewAccount"
									title="Filter by Account Selection" aria-label="Filter by Account Selection">
								<i class="fas fa-eye"></i>
							</button>
						</div>
					</div>
					<div class="col-2">
						<div class="form-group">
							<button class="btn btn-outline-primary" type="submit" name="action" value="clearAccountFilter"
									title="Clear Account Selection" aria-label="Clear Account Selection">
								<i class="fas fa-eye-slash"></i>
							</button>
						</div>
					</div>
				</form>
			</div>
			<div class="row align-items-end" id="my-page-footer-2">
				<form class="form-inline w-100 p-2"  method="post" action="<?php echo $postback;?>" >
					<div class="col">
						<p>Viewing Page: <?php echo $page; ?> : <?php echo $perpage; ?> records per page</p>
					</div>
					<div class="col-3">
						<div class="form-group">
							<select name="perpage" class="form-control">
								<option>30</option>
								<option>50</option>
								<option>100</option>
								<option>250</option>
							</select>
						</div>
					</div>
					<div class="col-2">
						<div class="form-group">
							<input type="hidden" name="page" value="<?php echo $page; ?>" />
							<input type="hidden" name="recordcount" value="<?php echo $recordcount; ?>" />
							<button class="btn btn-outline-primary" type="submit" name="action" value="changePagination"
									title="Show number of records per page." aria-label="Show number of records per page.">
								<i class="fas fa-list-alt"></i>
							</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
	<!-- Modal -->
	<div class="modal" id="addAcctModal" tabindex="-1" role="dialog" aria-labelledby="addAcctModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<form name="newaccount" id="id_newaccount" method="post" action="<?php echo $postback;?>" >
				<div class="hidden">
					<input type="hidden" name="action" value="createNewAccount" />
					<input type="hidden" name="recid" value="<?php echo $recid; ?>" />
					<input type="hidden" name="page" value="<?php echo $page; ?>" />
					<input type="hidden" name="perpage" value="<?php echo $perpage; ?>" />
					<input type="hidden" name="recordcount" value="<?php echo $recordcount; ?>" />
				</div>
				<div class="modal-content p-3">
					<div class="modal-header">
						<h5 class="modal-title" id="addAcctModalLabel">New Account</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="col">
									<div class="form-group">
										<label for="id_namefield">Name: <span class="small text-danger">(required) </span></label>
										<input type="text" class="form-control" name="name" id="id_namefield" placeholder="Account name">
									</div>
									<div class="form-group">
										<label for="id_accttype">Type: <span class="small text-danger">(required) </span></label>
										<select class="form-control" name="type" id="id_accttype">
											<option value='0'> -- Select -- </option>
											<option value='Expenditure'>Expenditure</option>
											<option value='Loan'>Loan</option>
											<option value='Income'>Income</option>
											<option value='Misc'>Misc</option>
											<option value='Investment'>Investment</option>
										</select>
									</div>
									<div class="form-group">
										<label for="id_bankreffield">Bank Reference:</label>
										<input type="text" class="form-control" name="bankref" id="id_bankreffield" placeholder="Bank Reference">
										<small id="bankrefHelp" class="form-text text-muted">Partial information thet helps identify the account on statements. (Optional)</small>
									</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
						<button type="submit" class="btn btn-primary">Save changes</button>
					</div>
				</div>
			</form>
		</div>
	</div>


</div> <!-- end container -->


<?php include_once('includes/footer.php'); ?>
