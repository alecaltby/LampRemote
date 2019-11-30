<?php
include_once("include/view.php");

$action = "";
if(isset($_GET["action"]))
	$action = $_GET["action"];
else if(isset($_POST["action"]))
	$action = $_POST["action"];

switch($action)
{
	case "checkChanges":
		print system("./fetchChanges.sh");
	break;

	case "fetchChanges":
		print system("./fetchChanges.sh");
	break;

	case "programTelldus":
		if(isset($_GET["id"]) && $id = (int) $_GET["id"])
		{
			$telldus = new telldus($id);
			$telldus->program();
		}
	break;

    case "vTelldusScheduleVisualize":
	case "vTellduses":
	case "vTelldusEvents":
	case "vTelldusConfig":
	case "vTelldusSchedules":
        print new $action;
	break;

	case "telldusSchedule":
		if(!isset($_GET["tid"]))
		{
			die("tid not specified");
		}

		$explode = explode(":", $_GET["tid"]);
		$type = $explode[0];
		$tid = $explode[1];

		if($type != "event" && $type != "scene")
		{
			die("Invalid type: $type");
		}

		if($tid+0 == 0)
		{
			die("Invalid tid $tid");
		}

		if(isset($_GET["id"]) && ($id = (int) $_GET["id"]) > 0)
		{
			$schedule = new telldusSchedule($id);
			$event = $schedule->event;

			if($type == "event")
			{
				$event->setEvent($_GET["event"]);
				$event->setValue($_GET["value"]);
			}
		}
		else
		{
			$schedule = new telldusSchedule();
			if($type == "event")
			{
				$event = new telldusEvent();
				$event->create($tid, $_GET["event"], $_GET["value"]);
			}
			else
			{
				$event = new scene($tid);
			}
		}

		$schedule->minutes = $_GET["minutes"];
		$schedule->hours = $_GET["hours"];
		$schedule->daysOfMonth = $_GET["daysOfMonth"];
		$schedule->months = $_GET["months"];
		$schedule->daysOfWeek = $_GET["daysOfWeek"];
		$schedule->event = $event;
		$schedule->type = $type;
		$schedule->save();

	break;
	case "delTelldusSchedule":
		if(isset($_GET["id"]) && ($id = (int) $_GET["id"]) > 0)
		{
			$event = new telldusSchedule($_GET["id"]);
			$event->delete();
		}
	break;

	case "addTelldus":
		if(!isset($_GET["name"]) || !isset($_GET["dimable"]) || !isset($_GET["unit"]) || !isset($_GET["house"]) || !isset($_GET["groups"]))
			die("Missing input data: ".print_r($_GET,1));

		$telldusCreate = new telldusCreate($_GET["name"],$_GET["dimable"], $_GET["unit"], $_GET["house"]);
		$telldus = $telldusCreate->getTelldus();
		$groups = isset($_GET["groups"]) ? $_GET["groups"] : "";

		foreach(explode(",",$groups) as $group)
		{
			if((int) $group > 0)
				$telldus->addToGroup($group);
		}
		print new vTelldusConfigRow($telldus);
	break;

	case "editTelldus":
		if(!isset($_GET["name"]) || !isset($_GET["dimable"]) || !isset($_GET["unit"]) || !isset($_GET["house"]) || !isset($_GET["groups"]))
			die("Missing input data");

		$id = (int) $_GET["id"];
		$name = $_GET["name"];
		$dimable = (int) $_GET["dimable"];
		$groups = $_GET["groups"];
		$house = $_GET["house"];
		$unit = $_GET["unit"];

		$telldus = new telldus($id);
		$telldus->setName($name);
		$telldus->setDimable($dimable);
		$telldus->setHouse($house);
		$telldus->setUnit($unit);

		$newGroups = explode(",",$groups);
		$oldGroups = $telldus->getGroups();
		$diffGroups = array_merge(array_diff($oldGroups,$newGroups),array_diff($newGroups,$oldGroups));

		foreach($diffGroups as $gid)
		{
			if(in_array($gid,$newGroups))
				$telldus->addToGroup($gid);
			else
				$telldus->removeFromGroup($gid);
		}

	break;
	case "delTelldus":
		if(!isset($_GET["id"]))
			die("Missing input data");

		$telldus = new telldus($_GET["id"]);
		$telldus->delete();
	break;

	case "addScene":
		$name = $_GET["name"];
		$scene = new scene();
		$scene->setName($name);

		$divId = "scene".$scene->getId();
		$return = '<fieldset style="width: 500px; margin: 10px auto;">';
		$return .= '<legend align="left" class="onclick" onclick="$(\'#'.$divId.'\').slideToggle();"><h3>'.$scene->getName().'</h3></legend>';
		$return .= "<div class=\"\" id=\"$divId\">";
		$return .= "</div>";
		$return .= '<div style="text-align: center;">';
		$return .= '<a class="onclick" onclick="addEventToScene('.$scene->getId().');">L&auml;gg till</a> | ';
		$return .= '<a class="onclick" onclick="deleteScene('.$scene->getId().');">Ta bort</a>';
		$return .= '</div>';
		$return .= '</fieldset>';
		print $return;

	break;

	case "deleteScene":
		$id = (int) $_GET["sceneid"];
		$scene = new scene($id);
		$scene->delete();
	break;

	case "addEventToScene":
		$id = (int) $_GET["sceneid"];
		$event = new telldusEvent();
		$event->create(1, "on", 0, $id);
		print (new vEventEdit($event));
	break;

	case "deleteEvent":
		$id = (int) $_GET["eventid"];
		$event = new telldusEvent($id);
		$event->delete();
	break;

	case "editEvent":
		$id = (int) $_GET["eventid"];
		$e = new telldusEvent($id);

		$tid = (int) $_GET["telldusid"];
		$value = (int) $_GET["value"];
		$event = $_GET["event"];

		$e->setTelldus(new telldus($tid));
		$e->setValue($value);
		$e->setEvent($event);
		unset($e);

	case "listenCode":
// 		sleep(5);
// 		$str = "16:TDRawDeviceEvent78:class:command;protocol:arctech;model:codeswitch;house:G;unit:2;method:turnoff;i2s16:TDRawDeviceEvent79:class:command;protocol:sartano;model:codeswitch;code:1001011110;method:turnoff;i2s";
		$s = stream_socket_client('unix:///tmp/TelldusEvents');
		$str = stream_socket_recvfrom($s,1024);
		$array = explode(";", $str);
		$return = [];

		foreach($array as $value)
		{
			$exp = explode(":", $value);
			if($exp[0] == "unit")
			{
				$return["unit"] = $exp[1];
			}
			elseif($exp[0] == "house")
			{
				$return["house"] = $exp[1];
			}
		}

		print json_encode($return);
	break;

	case "reloadConfiguration":
		createTelldusConfigFromDB();
	break;


	default:
	break;
}
?>
