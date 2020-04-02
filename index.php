<?php
include_once ('includes/header.php');

$ishome = true;
$message = null;
$recordcnt = 0;

if (!empty($_POST)) {
	$message = processFormdata();
}

$postback = strtok($_SERVER["REQUEST_URI"], '?');; 	// Remove any get params.

list($page, $perpage) = processPageParams();
$accounts = getAccounts();

//die('<pre>' . print_r($accounts, true) . '</pre>');

// All processing done we can move on.
$transactions = getTransactions();
$htmloptions = makeHTMLOptions();

$summary = getSummaryDetails();
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

	<?php if ($summary): ?>
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
				<p><button class="btn btn-success">Download CSV</button></p>
			</div>
		</div>
	<?php endif; ?>

	<div class="row">
		<div class="col">
			<br />
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
							if ($transaction['amount'] < 0) {
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
							if ($transaction['amount'] > 0) {
								echo '£' . number_format($transaction['amount'], 2);
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
										<button class="btn btn-outline-primary" onclick="alert('Not Available'); return false;" title="Add Account">
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

	<!-- Paging arear -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand">Go To Page:</a>
	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
	</button>
	<div class="collapse navbar-collapse" id="navbarNavAltMarkup">
		<div class="navbar-nav">
			<form class="form-inline">
				 <button class="btn btn-small" href="#">
					<i class="fas fa-hand-point-left"></i>
				</a>
				 <button class="btn btn-small disabled" href="#">10</a>
				 <button class="btn btn-small" href="#">11</a>
				 <button class="btn btn-small" href="#">12</a>
				 <button class="btn btn-small" href="#">13</a>
				 <button class="btn btn-small" href="#">14</a>
				 <button class="btn btn-small" href="#">15</a>
				 <button class="btn btn-small" href="#">16</a>
				 <button class="btn btn-small" href="#">17</a>
				 <button class="btn btn-small" href="#">18</a>
				 <button class="btn btn-small" href="#">19</a>
				 <button class="btn btn-small" href="#">
					<i class="fas fa-hand-point-right"></i>
				</a>
		</form>
    </div>
  </div>
</nav>


	<!-- END OF PAGE DIV -->
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
					<div class="col">
						<div class="form-group">
							<select name="accountid" class="form-control" title="Select Account to filter by", aria-label="Select Account to filter by">
								<option value="0">No Account Specified</option>
								<?php echo $htmloptions; ?>
							</select>
							<input type="hidden" name="page" value="<?php echo $page; ?>" />
							<input type="hidden" name="perpage" value="<?php echo $perpage; ?>" />
							<input type="hidden" name="recordcnt" value="<?php echo $recordcnt; ?>" />
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
			<div class="row w-100 align-items-end" id="my-page-footer-2">
				<div class="col">
					<p>Viewing Page: <?php echo $page; ?> : <?php echo $perpage; ?> records per page</p>
				</div>
				<div class="col">
					<form class="form-inline w-100 p-2" method="post" action="<?php echo $postback;?>" >
						<div class="row align-items-end">
							<div class="col">
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
									<input type="hidden" name="recordcnt" value="<?php echo $recordcnt; ?>" />
									<button class="btn btn-outline-primary" type="submit" name="action" value="changePagination"
											title="Show number of records per page." aria-label="Show number of records per page.">
										<i class="fas fa-list-alt"></i>
									</button>
									</a>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

</div> <!-- end container -->

<?php include_once('includes/footer.php'); ?>
