<?php

	include "functions/Log.php";
	include "functions/AssignmentsCarousel.php";

	writeToLog($_REQUEST, " INCOMING DATA ");

	$leadId = $_REQUEST["id"];
	$projectName = $_REQUEST["desarrollo"];
	$type = $_REQUEST["tipo"];

	assignments($leadId, $projectName, $type);

?>