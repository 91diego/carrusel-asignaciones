<?php

	include "functions/Log.php";
	include "functions/AssignmentsCarousel.php";

	writeToLog($_REQUEST, " INCOMING DATA ");

	$leadId = $_REQUEST["id"];
	$projectName = $_REQUEST["desarrollo"];
	$type = $_REQUEST["tipo"];

	print_r(searchDuplicateRecord($leadId)); exit;
	assignments($leadId, $projectName, $type);

?>