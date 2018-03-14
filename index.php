<?php

	/* 
	 * Save POSTMAN version of HTTP request 
	 * 
	 *  7-feb-2018	new		
	 */
	require_once "ApiToPostman.class.php";
	
	echo "Starting";
	
	// We may want the end resulting POSTMAN requests to be called on a different server,
	// so we can set this here
	$domain = 'pet-api.com.local';
	
	$atp = new \arcadia\ApiToPostman($domain);
	
	// we can specify the output file and what we want to do with it
	// note adding if file doesnt exists just does a create
	$atp->outputPostman('log/postman.json', 'add');
	
	echo " Finished";
?>