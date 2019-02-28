<?php
//if(isset($_SERVER["REMOTE_USER"]) && $_SERVER["REMOTE_USER"] != "alec")
//	die;
//require_once("include/model.php");
require_once("include/view.php");
$action = isset($_GET["action"]) ? $_GET["action"] : "";
$main = new vLamps($action);
$page = new homepage($main);
$page->display();
?>
