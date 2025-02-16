<?php 
/**
 * ====================================================================================
 *                           某某短域名系统
 * ----------------------------------------------------------------------------------
 * @copyright This software is exclusively sold at CodeCanyon.net. If you have downloaded this
 *  from another site or received it from someone else than me, then you are engaged
 *  in an illegal activity. You must delete this software immediately or buy a proper
 *  license from https://www.domain.com
 *
 *  Thank you for your cooperation and don't hesitate to contact me if anything :)
 * ====================================================================================
 *
 * @author 某某 (https://www.domain.com)
 * @link https://www.domain.com
 * @license https://www.domain.com/license
 * @package 某某短域名系统
 * @subpackage App Request Handler
 */
	if(!file_exists("includes/config.php")){
		header("Location: install.php");
		exit;
	}
	include("includes/config.php");
	
	// Run the app
	$app->run();