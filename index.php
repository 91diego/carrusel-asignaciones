<?php

	include "functions/Log.php";
	include "functions/AssignmentsCarousel.php";

	writeToLog($_REQUEST, " INCOMING DATA ");

	$leadId = $_REQUEST["id"];
	$projectName = $_REQUEST["desarrollo"];
	$type = $_REQUEST["tipo"];

	$result = searchDuplicateRecord($leadId);
	if (!empty($result)) {
		
		$data = $result[0];
		assignments($leadId, $projectName, $type, $data);
	} else {
		$data = 0;
		assignments($leadId, $projectName, $type, 0);
	}

?>