<?php


function getPagingHTML($url, $maxlinks, $page, $perpage, $recordcount, array $otherparams = array()) {
	$html = '';

	if (($maxlinks%2) === 0) {
		$maxlinks++;		// Make it odd.
	}
	//echo "<pre>maxlinks is $maxlinks</pre>";

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
	$halfway = floor($maxlinks/2);		// Where we want the requested page to follow.
	$pagelinks = array();
	$paginglinks = array();

	// if we have less than the maximum number of
	if ($maxpages <= $maxlinks) {
		for ($i = 0; $i < $maxlinks; $i++) {
			$pagelinks[$i] = $i + 1;
		}
	} else {

		for ($page = 1; $page < $maxpages + 1; $page++) {
			$pagelinks = array();
			$paginglinks = array();

		// Default
		$startindex = $page;
		$endindex = $startindex + ($maxlinks - $startindex);

		if ($page <= ($halfway + 1)) {  // First lot of pages.
			$startindex = 1;
			$endindex = $startindex + ($maxlinks - $startindex);

			if ($endindex < $maxpages) {
				$paginglinks['next'] = $endindex;
				$endindex--;
			}
			if ($endindex < ($maxpages - 1)) {
				$paginglinks['last'] = $maxpages;
				$paginglinks['next'] = $endindex;
				$endindex--;
			}
		} else if ($page >= ($maxpages - $halfway)) {	// Last lot of pages.
			$endindex = $maxpages;
			$startindex = $endindex - $maxlinks;
			if ($startindex > 1) {
				// Previous link
				$paginglinks['previous'] = $startindex;
				$startindex++;
			}
			if ($startindex > 1) {
				$paginglinks['first'] = 1;
				$startindex++;
				$paginglinks['previous'] = $startindex;
				$startindex++;
			}
		} else {		// All other pages.
			$startindex = $page - $halfway;
			$endindex = $page + $halfway;
			if ($startindex > 1) {
				// Previous link
 				$paginglinks['previous'] = $startindex;
 				$startindex++;
			}
			if ($startindex > 2) {
				$paginglinks['first'] = 1;
				$startindex++;
				$paginglinks['previous'] = $startindex;
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
		}






		// Link to 1st Page.
/* 		if (isset($paginglinks['previous']) && $paginglinks['previous'] > 1) {
			$paginglinks['first'] = 1;
			$paginglinks['previous'] = $startindex;
			$startindex++;
		}

		// Link to last page.
		if (isset($paginglinks['next']) && $paginglinks['next'] < ($maxpages - 1)) {
			$paginglinks['last'] = $maxpages;

			$paginglinks['next'] = $endindex;
			$endindex--;
			if (isset($paginglinks['previous'])) {
				$paginglinks['next'] = $endindex;
				$endindex--;
			}

		} */


		echo '<pre>' . "$startindex\t$endindex\n" . '</pre>';
		// 			continue;
		for ($i = $startindex; $i < ($endindex + 1); $i++) {
			$pagelinks[] = $i;
		}


		if ((count($pagelinks) + count($paginglinks)) !== $maxlinks) {
			echo '<pre>' . print_r($pagelinks, true) . '</pre>';
			echo '<pre>' . print_r($paginglinks, true) . '</pre>';
			die("Page: $page has " . (count($pagelinks) + count($paginglinks)) . " Items");
		}


		echo "<pre>Page: $page\tPer Page: $perpage\tMax Pages: $maxpages\tTotal Recs: $recordcount\tMax Links: $maxlinks</pre>\n";
		echo '<pre>' . print_r($pagelinks, true) . '</pre>';
		echo '<pre>' . print_r($paginglinks, true) . '</pre>';
		echo '<hr />';
		}

	}

	return '';

	$html = "
				<nav class='navbar navbar-expand-lg navbar-light bg-light'>
					<a class='navbar-brand'>Go To Page:</button>
					<button class='navbar-toggler' type='button' data-toggle='collapse' data-target='#navbarNavAltMarkup' aria-controls='navbarNavAltMarkup' aria-expanded='false' aria-label='Toggle navigation'>
						<span class='navbar-toggler-icon'></span>
					</button>
					<div class='collapse navbar-collapse' id='navbarNavAltMarkup'>
						<div class='navbar-nav'>
							<form action='$url' method='post' class='form-inline'>
								<input type='hidden' name='perpage' value='$perpage' />
								<input type='hidden' name='recordcount' value='$recordcount' />
	";

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
		$disabled = ($pagelink == $page) ? ' disabled' : '';
		$html .= "<button type='submit' class='btn btn-small $disabled' name='page' value='$pagelink'>$pagelink</button>\n";
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