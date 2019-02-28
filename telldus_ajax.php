<?php
header("Content-type:application/json");
if(isset($argv))
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
require_once("include/telldus_api.php");

if(isset($_GET["id"]))
{
	$id = (int) $_GET["id"];
	$telldus = new telldus($id);
}
else if(isset($_GET["gid"]))
{
	$gid = (int) $_GET["gid"];
	$telldus = new tellduses();
	$telldus->getById($gid);
}
else if(isset($_GET["sceneid"]))
{
	$scene = new scene((int) $_GET["sceneid"]);
	$_GET["action"] = "scene";
}
else
{
	$telldus = new tellduses();
	$telldus->getById(0);
}

if(!isset($_GET["action"]))
{
	$action = "list";
}
else
{
	$action = $_GET["action"];
}

switch($action)
{
	case "toggle":
	$telldus->toggle();
	break;

	case "on":
	$telldus->on();
	break;

	case "off":
	$telldus->off();
	break;

	case "fade":
	case "dim":
	$telldus->dim($_GET["dimlevel"]);
	break;

	case "incdim":
	print $telldus->incDim();
	break;

	case "decdim":
	print $telldus->decDim();
	break;

	case "scene":
	$scene->execute();
	break;

	case "list":
	default:
	$array = array_merge(getScenes(),$telldus->jsonSerialize());
	print json_encode($array);
	break;
}
?>
