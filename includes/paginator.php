<?php


function getPagingHTML($url, $maxlinks, $page, $perpage, $recordcount, array $otherparams = array(), $method = 'post') {
	$html = '';

	if ($recordcount < $perpage) {
		return $html;		// No paging bar.
	}

	if (($maxlinks%2) === 0) {
		$maxlinks++;		// Make it odd.
	}

	$maxpages = (int) ceil($recordcount/$perpage);
	$halfway = floor($maxlinks/2);		// Where we want the requested page to follow.
	$pagelinks = array();
	$paginglinks = array();

	// If we have less than the maximum number of.
	if ($maxpages <= $maxlinks) {
		for ($i = 0; $i < $maxlinks; $i++) {
			$pagelinks[$i] = $i + 1;
		}
	} else {
		// Default.
		$startindex = $page;
		$endindex = $startindex + ($maxlinks - $startindex);

		if ($page <= ($halfway + 1)) {  // First lot of pages.
			$startindex = 1;
			$endindex = $startindex + ($maxlinks - $startindex);
		} else if ($page >= ($maxpages - $halfway)) {	// Last lot of pages.
			$endindex = $maxpages;
			$startindex = $endindex - $maxlinks;
		} else {		// All other pages.
			$startindex = $page - $halfway;
			$endindex = $page + $halfway;
		}

		if ($startindex > 1) {
			$paginglinks['previous'] = $startindex;
			$startindex++;
		}

		if ($startindex > 1) {
			$paginglinks['first'] = 1;
			$startindex++;
			$paginglinks['previous'] = $startindex;
			if ($endindex == $maxpages) {
				$startindex++;
			}
		}

		if ($endindex < $maxpages) {
			$paginglinks['next'] = $endindex;
			$endindex--;
		}
		if ($endindex < ($maxpages - 1)) {
			$paginglinks['last'] = $maxpages;
			$paginglinks['next'] = $endindex;
			$endindex--;
		}

		for ($i = $startindex; $i < ($endindex + 1); $i++) {
			$pagelinks[] = $i;
		}
	}

	$html .= "
				<nav class='navbar navbar-expand-lg navbar-light bg-light'>
					<a class='navbar-brand'>Go To Page:</a>
					<button class='navbar-toggler' type='button' data-toggle='collapse' data-target='#navbarNavAltMarkup' aria-controls='navbarNavAltMarkup' aria-expanded='false' aria-label='Toggle navigation'>
						<span class='navbar-toggler-icon'></span>
					</button>
					<div class='collapse navbar-collapse' id='navbarNavAltMarkup'>
						<div class='navbar-nav'>
							<form action='$url' method='$method' class='form-inline'>
								<input type='hidden' name='perpage' value='$perpage' />
								<input type='hidden' name='recordcount' value='$recordcount' />
	";

	if (!empty($otherparams)) {
		foreach ($otherparams as $key => $val) {
			if (!empty($val)) {
				$html .= "<input type='hidden' name='$key' value='$val' />\n";
			}else{
				$html .= "<input type='hidden' name='$key' value='' />\n";
			}
		}
	}

	if (!empty($paginglinks['first'])) {
		$label = 'First page';
		$pagelink = $paginglinks['first'];
		$html .= "
				 <button type='submit' class='btn btn-small' name='page' value='$pagelink' title='$label' aria-label='$label'>
					<i class='fas fa-step-backward'></i>
				</button>
		";
	}

	if (!empty($paginglinks['previous'])) {
		$label = 'Previous page';
		$pagelink = $paginglinks['previous'];
		$html .= "
				 <button type='submit' class='btn btn-small' name='page' value='$pagelink' title='$label' aria-label='$label'>
					<i class='fas fa-hand-point-left'></i>
				</button>
		";
	}

	foreach ($pagelinks as $pagelink) {
		// $disabled = ($pagelink == $page) ? ' disabled' : '';
		if ($pagelink == $page) {
			$html .= "<button class='btn btn-small' disabled >$pagelink</button>\n";
		}else{
			$html .= "<button type='submit' class='btn btn-small' name='page' value='$pagelink'>$pagelink</button>\n";
		}
	}

	if (!empty($paginglinks['next'])) {
		$label = 'Next page';
		$pagelink = $paginglinks['next'];
		$html .= "
				 <button type='submit' class='btn btn-small' name='page' value='$pagelink' title='$label' aria-label='$label'>
					<i class='fas fa-hand-point-right'></i>
				</button>
		";
	}

	if (!empty($paginglinks['last'])) {
		$label = 'Last page';
		$pagelink = $paginglinks['last'];
		$html .= "
				 <button type='submit' class='btn btn-small' name='page' value='$pagelink' title='$label' aria-label='$label'>
					<i class='fas fa-step-forward'></i>
				</button>
		";
	}

	$html .= '
				</form>
			</div>
		</div>
	</nav>';

	return $html;
}