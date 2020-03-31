<nav class="navbar navbar-light bg-light justify-content-between">
	<a class="navbar-brand"><?php echo date('d-m-Y'); ?></a>
	<form class="form-inline" method="post" enctype="multipart/form-data">
		<input type="hidden" name="action" value="processStatement" />
		<input class="form-control" type="file" aria-label="Statement" name="statementfile"/>
		&nbsp;
		<button class="btn btn-dark" type="submit">Upload Statement</button>
	</form>
</nav>