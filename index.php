<?php
include_once ('includes/header.php');

$message = null;
if (!empty($_POST)) {
	$message = processFormdata();
}

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

	<?php if (!empty($message)): ?>
	<div class="row">
		<div class="col">
			<p class="<?php echo $message['type']; ?> p-3 mb-2 text-white">
				<strong><?php echo $message['text']; ?></strong>
			</p>
		</div>
	</div>
	<?php endif;?>

	<div class="card">
		<div class="card-body">
			<h5 class="card-title">Account Summary</h5>
			<p class="card-text">
				Cash Balance: £3,500.01<br /> Loan Balance: £6,500.60
			</p>
			<p class="card-text mb-2 text-muted">Total £10,560.80</p>
		</div>
	</div>

	<div class="row">
		<div class="col">
			<br />
		</div>
	</div>

	<div class="row">
		<div class="col">
			<div class="row">
				<div class="col">
					<strong>Date:</strong> 30-03-2020
				</div>
			</div>
			<div class="row">
				<div class="col">
					<strong>Transaction Type:</strong><br /> ATM Withdrawal
					NOTEMACHINE LTD
				</div>
			</div>
			<div class="row">
				<div class="col">
					<strong>Description:</strong><br /> ATM Withdrawal NOTEMACHINE
					LTD
				</div>
			</div>
			<div class="row">
				<div class="col">
					<strong>Income:</strong> £3,500.00
				</div>
			</div>
			<div class="row">
				<div class="col">
					<strong>Spend:</strong> £1,600
				</div>
			</div>
			<div class="row">
				<div class="col">
					<strong>Account:</strong> Felicia (Loan)
				</div>
				<div class="col">
					<a href="#" title="Edit Account"><i class="fas fa-edit"></i></a>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col">
			<hr />
			<div class="row">
				<div class="col">
					<strong>Date:</strong> 30-03-2020
				</div>
			</div>
			<div class="row">
				<div class="col">
					<strong>Transaction Type:</strong><br />
					ATM Withdrawal NOTEMACHINE LTD
				</div>
			</div>
			<div class="row">
				<div class="col">
					<strong>Description:</strong><br />
					ATM Withdrawal NOTEMACHINE LTD
				</div>
			</div>
			<div class="row">
				<div class="col">
					<strong>Income:</strong> £3,500.00
				</div>
			</div>
			<div class="row">
				<div class="col">
					<strong>Spend:</strong> £1,600
				</div>
			</div>
			<div class="row">
				<div class="col">
					<form class="form-inline">
						<strong>Account: &nbsp;</strong>
						<div class="row">
							<div class="col">
								<div class="form-group">
									<select class="form-control">
										<option>WOLVERHAMPTON C.C. (income)</option>
										<option>Granny Felicia (Expense)</option>
										<option>Ben (Loan)</option>
										<option>Monique (Loan)</option>
										<option>Felicia (Loan)</option>
									</select>
								</div>
							</div>

							<div class="col-1">
								<div class="form-group">
									<a href="#" title="Save Selection"><i class="fas fa-check"></i></a>
								</div>
							</div>

							<div class="col-1">
								<div class="form-group">
									<a href="#" title="Add Account"><i class="fas fa-plus text-danger"></i></a>
								</div>
							</div>

						</div> <!-- end form row -->
					</form>
				</div>
			</div>
		</div> <!-- end col -->
	</div> <!-- end row -->



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

			<div class="row" id="my-page-footer-1">
				<div class="col">
					<form>
						<div class="row">
							<div class="col">
								<div class="form-group">
									<select class="form-control">
										<option>WOLVERHAMPTON C.C. (income)</option>
										<option>Granny Felicia (Expense)</option>
										<option>Ben (Loan)</option>
										<option>Monique (Loan)</option>
										<option>Felicia (Loan)</option>
									</select>
								</div>
							</div>
							<div class="col-2">
								<div class="form-group">
									<a href="#" title="View Account"><i class="fas fa-eye"></i></a>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>


			<div class="row" id="my-page-footer-2">
				<div class="col">
					<div class="row">
						<div class="col">
							<p>Viewing Page: 1 : 20 records per page</p>
						</div>
					</div>
				</div>
				<div class="col">
					<form class="form-inline">
						<div class="form-row">
							<div class="col">
								<div class="form-group">
									<select class="form-control">
										<option>30</option>
										<option>50</option>
										<option>100</option>
										<option>250</option>
									</select>
								</div>
							</div>
							<div class="col-2">
								<div class="form-group">
									<a href="#" title="View Account"><i class="fas fa-list-alt"></i></a>
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
