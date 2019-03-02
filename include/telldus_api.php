<?php
require_once("constants.php");
function createTelldusConfigFromDB()
{
	$mysql = new mysql();
	//const $configFile = "/etc/tellstick.conf";
	$configFile = "/etc/tellstick.conf";
	$header = 'user = "nobody"
group = "plugdev"
deviceNode = "/dev/tellstick"
ignoreControllerConfirmation = "false"

controller {
  id = 1
  # name = ""
  type = 1
  serial = "67108863"
}
';

	$file = fopen($configFile,"w");
	if(!$file)
		die("Unable to open ".$configFile." for writing");

	fwrite($file,$header);

	$res = $mysql->query("SELECT * FROM telldus");
	while($row = mysqli_fetch_assoc($res))
	{
		fwrite($file,'device {'."\n");
		fwrite($file,'  id = '.$row["id"]."\n");
		fwrite($file,'  name = "'.$row["name"]."\"\n");
		fwrite($file,'  controller = 0'."\n");
		fwrite($file,'  protocol = "arctech"'."\n");

		if($row["dimable"])
			fwrite($file,'  model = "selflearning-dimmer:nexa"'."\n");
		else
			fwrite($file,'  model = "selflearning-switch:nexa"'."\n");

		fwrite($file,'  parameters {'."\n");
		fwrite($file,'    house = "'.$row["house"].'"'."\n");
		fwrite($file,'    unit = "'.$row["unit"]."\"\n");
		fwrite($file,'  }'."\n");
		fwrite($file,'}'."\n");
	}

	fclose($file);
	telldusRestart();
}

function telldusRestart()
{
	$pid = (int) exec("pgrep telldusd");
	if($pid > 0)
	{
		exec("kill $pid");

		for($i=0;$i<20;$i++)
		{
			$pid = (int) exec("pgrep telldusd");
			if($pid == 0)
				break;
			sleep(1);
		}
	}

	if($pid == 0)
		exec("/etc/init.d/telldusd start");
	else
		print "Unable to restart telldusd";
}

function getScenes()
{
	$mysql = new mysql();
	$scenes = [];
	$res = $mysql->query("SELECT * FROM telldusScene");
	while($row = mysqli_fetch_assoc($res))
	{
		$scenes[] = new scene($row["id"]);
	}

	return $scenes;
}

class scene extends mysql implements JsonSerializable
{
	private $name;
	private $events;
	private $id;
	private $update;

	public function __construct($id=0)
	{
		parent::__construct();
		$id = (int) $id;
		$this->id = $id;
		$this->events = [];
		if($id > 0)
		{
			$this->gid=(int)$id;
			$name = mysqli_fetch_assoc($this->query("SELECT * FROM telldusScene WHERE id=$id"))["name"];
			$this->name = $name;
			$res = $this->query("SELECT * FROM telldusEvent WHERE sceneid='$id' ORDER BY id");
			while($row = mysqli_fetch_assoc($res))
			{
				$this->events[] = new telldusEvent($row["id"]);
			}
		}
	}

	public function getEvents()
	{
		return $this->events;
	}

	public function addEvent($event)
	{
		$id = (int) $this->id;
		if($id > 0)
		{
			$event->setScene($this->id);
			$this->events[] = $event;
		}
	}

	public function setName($name)
	{
		$id = (int) $this->id;
		$this->name = $name;
		$this->update();
	}

	public function getName()
	{
		return isset($this->name) ? $this->name : "";
	}

	public function delete()
	{
		$id = (int) $this->id;
		if($id > 0)
		{
			foreach($this->events as $event)
			{
				$event->delete();
			}

			$this->query("DELETE FROM telldusScene WHERE id=$id");
		}
	}

	public function getId()
	{
		return isset($this->id) ? (int) $this->id : 0;
	}

	public function execute()
	{
		foreach($this->events as $event)
		{
			$event->execute();
		}
	}

	public function update()
	{
		$sqlname = $this->sqlesc($this->name);
		if(isset($this->id) && $this->id > 0)
		{
			$this->query("UPDATE telldusScene SET name=$sqlname WHERE id=$id");
		}
		else
		{
			$this->query("INSERT INTO telldusScene (name) VALUES ($sqlname)");
			$this->id = $this->insert_id();
		}
		$this->update = false;
	}

	public function __destruct()
	{
		if($this->update)
		{
			$this->update();
		}
	}

	public function jsonSerialize()
	{
		return array("id"=>(int)$this->id,"name"=>$this->name);
	}
}

class telldusGroups extends mysql// implements JsonSerializable
{
    private $telldusGroups;
    public function __construct()
    {
        parent::__construct();
        $tellduses = [];
        $res = $this->query("SELECT distinct(gid) from telldusGroup");
        while($row = mysqli_fetch_assoc($res))
        {
			print $row["gid"];
			continue;
            $tds = new tellduses();
            $this->telldusGroups[] = $tds->getById($row["gid"]);
        }
    }

    public function getTelldusGroups()
    {
        return $this->telldusGroups;
    }
}

class tellduses extends mysql implements JsonSerializable
{
	private $gid;
	private $lamps;
	public function __construct()
	{
		parent::__construct();
	}

	public function getById($id)
	{
		if(isset($this->lamps))
			return false;

		$this->gid=(int)$id;
		$res = $this->query("SELECT * FROM telldusGroup WHERE gid='$id' ORDER BY tid");
		while($row = mysqli_fetch_assoc($res))
		{
			$this->lamps[] = new telldus($row["tid"]);
		}
	}

	public function getTellduses()
	{
		if(!isset($this->lamps))
			return array();

		return $this->lamps;
	}

	public function on()
	{
		if(!isset($this->lamps))
			return false;

		foreach($this->lamps as $lamp)
		{
			$lamp->on();
		}
	}

	public function off()
	{
		if(!isset($this->lamps))
			return false;

		foreach($this->lamps as $lamp)
			$lamp->off();
	}

	public function toggle()
	{
		if(!isset($this->lamps))
			return false;

		foreach($this->lamps as $lamp)
			$lamp->toggle();
	}

	public function jsonSerialize()
	{
		return $this->getTellduses();
	}
}

class telldusParent extends mysql
{
	protected $id = 0;
	protected $state = 0;
	protected $dimable = false;
	protected $dimlevel = 0;
	protected $name = "";
	protected $house = 0;
	protected $unit = 0;

	protected function validHouse($value)
	{
		preg_match("/[A-P]/", $value, $match);
		return isset($match[0]) || ($value > 0 && $value <= 67108863);
	}

	protected function validUnit($value)
	{
		return $value > 0 && $value <=16;
	}

	protected function getRandomHouse()
	{
		return rand(1,67108863);
	}

	protected function getRandomUnit()
	{
		return rand(1,16);
	}

	public function __construct()
	{
		parent::__construct();
	}
}

class telldusCreate extends telldusParent
{
	private $telldus;
	public function __construct($name,$dimable=false,$unit=0,$house="")
	{
		parent::__construct();
		if(!isset($name))
			return;

		$name_esc = $this->sqlesc($name);
		$dimable_esc = $this->sqlesc((int) $dimable);
		$this->dimable = (int) $dimable;
		$res = $this->query("SELECT id FROM telldus ORDER BY id ASC");
		$id = 1;
        $this->house = $this->validHouse($house) ? $house : $this->getRandomHouse();
        $this->unit = $this->validUnit($unit) ? $unit : $this->getRandomHouse();

        $house_esc = $this->sqlesc($this->house);
		while($row = mysqli_fetch_assoc($res))
		{
			if($row["id"] != $id)
				break;
			$id++;
		}
		$this->id = $id;
		$res = $this->query("INSERT INTO telldus (id,name,dimable,house,unit) VALUES ('".$this->id."',$name_esc,$dimable_esc,$house_esc,".$this->unit.")");

		$telldus = new telldus($this->id);
		$telldus->addToGroup(0);

		$this->telldus = $telldus;
	}

	public function getTelldus()
	{
		return $this->telldus;
	}
}

class telldus extends telldusParent implements JsonSerializable
{
	private $writeToFile = false;

	public function __construct($id)
	{
		parent::__construct();
		$this->id=(int)$id;

		if(!$this->id)
			die("Invalid id");
		$res = mysqli_fetch_assoc($this->query("SELECT * FROM telldus where id=$id"));
		if($res)
		{
			$this->name=$res["name"];
			$this->id=$res["id"];
			$this->state=$res["state"];
			$this->unit=$res["unit"];
			$this->house=$res["house"];
			if($res["dimable"])
			{
				$this->dimable = 1;
				$this->dimlevel = $res["dimlevel"];
			}
		}
	}

	public function __toString()
	{
		return $this->id.":".$this->name;
	}

	function toggle()
	{
		if($this->state == 1)
			$this->off();
		else
			$this->on();
	}

	public function on()
	{
		$id = $this->id;

		if($this->dimable)
			$this->dim($this->dimlevel);
		else
			$this->tdtool("-n".$this->id);

		$this->state=1;
		$this->query("UPDATE telldus set state=1 where id=$id");
	}

	public function off()
	{
		$id = $this->id;
		$this->tdtool("-f".$this->id);
		$this->state=0;
		$this->query("UPDATE telldus set state=0 where id=$id");
	}

	public function fade($dimlevel)
	{
		$this->dim($dimlevel);
	}

	public function dim($dimlevel)
	{
		$dimlevel = (int) $dimlevel;
		$id = $this->id;
		if(!$this->dimable)
			return;

		$this->tdtool("--dimlevel ".$dimlevel." --dim ".$this->id);
		$this->dimlevel = $dimlevel;
		$this->query("UPDATE telldus set dimlevel=$dimlevel, state=1 where id=$id");
	}

	public function incDim()
	{
		if($this->dimlevel < 255)
			$this->dim($this->dimlevel+1);
		return $this->dimlevel;
	}

	public function decDim()
	{
		if($this->dimlevel > 0)
			$this->dim($this->dimlevel-1);
		return $this->dimlevel;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getState()
	{
		return $this->state;
	}

	public function getDimlevel()
	{
		return $this->dimlevel;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getStatus()
	{
		return $this->name.": ".($this->state ? "On" : "Off")." ".($this->dimable ? $this->dimlevel : "");
	}

	public function getDimable()
	{
		return $this->dimable;
	}

	public function getHouse()
	{
		return $this->house;
	}

	public function getUnit()
	{
		return $this->unit;
	}

	public function setHouse($house)
	{
		$this->house = $this->validHouse($house) ? $house : getRandomHouse();
		$sql_house = $this->sqlesc($this->house);
		$sql_id = $this->sqlesc($this->id);
		$this->query("UPDATE telldus SET house=".$sql_house." WHERE id=".$sql_id);
	}

	public function setUnit($unit)
	{
        $this->unit = $this->validUnit($unit) ? $unit : getRandomUnit();
        $sql_unit = $this->sqlesc($this->unit);
		$sql_id = $this->sqlesc($this->id);
		$this->query("UPDATE telldus SET unit=".$sql_unit." WHERE id=".$sql_id);
	}

	public function setName($name)
	{
		if($name == $this->name)
			return;

		$sql_id = $this->sqlesc($this->id);
		$sql_name = $this->sqlesc($name);
		$this->query("UPDATE telldus SET name=".$sql_name." WHERE id=".$sql_id);
		$this->writeToFile = true;
	}

	public function setDimable($dimable)
	{
		if((int) $dimable == $this->dimable)
			return;

		$sql_id = $this->sqlesc($this->id);
		$sql_dimable = $this->sqlesc($dimable);
		$this->query("UPDATE telldus SET dimable=".$sql_dimable." WHERE id=".$sql_id);
		$this->writeToFile = true;
	}

	public function addToGroup($id)
	{
		$id = (int) $id;
		$res = $this->query("SELECT * FROM telldusGroup WHERE tid=".$this->sqlesc($this->id)." && gid=".$this->sqlesc($id));
		if($this->num_rows() > 0)
			return;

		$this->query("INSERT INTO telldusGroup (tid,gid) VALUES (".$this->sqlesc($this->id).",".$this->sqlesc($id).")");
	}

	public function removeFromGroup($id)
	{
		$id = (int) $id;
		$this->query("DELETE FROM telldusGroup WHERE gid=".$this->sqlesc($id)." && tid=".$this->sqlesc($this->id));
	}

	public function getGroups()
	{
		$array = array();
		$res = $this->query("SELECT * FROM telldusGroup WHERE tid=".$this->sqlesc($this->id));
		while($row = mysqli_fetch_assoc($res))
		{
			if($row["gid"] > 0)
				$array[] = $row["gid"];
		}

		if(!isset($array))
			return array();
		else
			return $array;
	}

	public function delete()
	{
		$this->query("DELETE FROM telldusGroup WHERE tid=".$this->sqlesc($this->id));
		$this->query("DELETE FROM telldus WHERE id=".$this->id);
		$this->writeToFile = true;
	}

	public function jsonSerialize()
	{
		return array("id"=>(int)$this->id,"name"=>$this->name,"state"=>(bool)$this->state,"dimable"=>(bool)$this->dimable,"dimlevel"=>(int)$this->dimlevel);
	}

	private function tdtool($args)
	{
		exec("tdtool ".$args,$ret);
	}

	public function program()
	{
		$args = $this->id;
		exec("tdtool -e ".$args,$ret);
	}

	public function __destruct()
	{
// 		if($this->writeToFile)
// 			createTelldusConfigFromDB();
	}
}

class telldusEvent extends mysql
{
    private $id;
    private $telldus;
    private $event;
    private $value;
    private $update;
    private $sceneid;

    public function __construct($id=0)
    {
        $id = (int) $id;
        parent::__construct();
        if($id > 0)
        {
            $res = $this->query("SELECT * from telldusEvent WHERE id=$id");
            if($obj = mysqli_fetch_object($res))
            {
                $this->setValues($obj->tid, $obj->event, $obj->value, $id, $obj->sceneid);
            }
        }
    }

    public function execute()
    {
        if($this->telldus)
        {
            switch($this->event)
            {
                case "on":
                    $this->telldus->on();
                    break;
                case "off":
                    $this->telldus->off();
                    break;
                case "fade":
                case "dim":
                    $this->telldus->dim($this->value);
                    break;
                default:
                    print "Unknown event";
                    break;
            }
        }
    }

    private function setValues($tid, $event, $value=0, $id=0, $sceneid=0)
    {
        $this->telldus = new Telldus($tid);
        $this->event = $event;
        $this->value = $value;
        $this->id=$id;
        $this->scene=$sceneid;
    }

    public function create($tid, $event, $value=0, $sceneid=0)
    {
        if(!$this->telldus)
        {
            $tid = (int) $tid;
            $value = (int) $value;
            $event = $this->assertEventSanity($event);
            $sceneid = $sceneid == 0 ? "NULL" : (int) $sceneid;
            $this->query("INSERT INTO telldusEvent (tid, event, value, sceneid) VALUES ($tid, '$event', $value, $sceneid)");
            $id = $this->insert_id();
            $this->setValues($tid, $event, $value, $id, $sceneid);
        }
        else
        {
            print "telldus is already defined";
        }
    }

    public function delete()
    {
        if($this->id)
        {
            $this->query("DELETE FROM telldusEvent WHERE id=".$this->id);
        }
    }

    private function assertEventSanity($event)
    {
        $validEvents = array("on", "off", "fade");
        return in_array($event, $validEvents) ? $event : die("Invalid event");
    }

    public function setTelldus($telldus)
    {
        $this->telldus = $telldus;
        $this->update = true;
    }

    public function getTelldus()
    {
        return $this->telldus;
    }

    public function getSceneId()
    {
		return $this->sceneid;
    }

    public function setScene($sceneid)
    {
		$sceneid = (int) $sceneid;
		$this->sceneid = $sceneid;
		$this->query("UPDATE telldusEvent SET sceneid=$sceneid");
    }

    public function setEvent($event)
    {
        $this->event = $this->assertEventSanity($event);
        $this->update = true;
    }

    public function getEvent()
    {
        return $this->event;
    }

    public function setValue($value)
    {
        $this->value = (int) $value;
        $this->update = true;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getId()
    {
        return $this->id;
    }

    public function __destruct()
    {
        if($this->update)
        {
            $tid = $this->telldus->getId();

            if($this->id)
            {
                $this->query("UPDATE telldusEvent SET tid=$tid, event='".$this->event."', value=".$this->value." WHERE id=".$this->id);
            }
            else
            {
                $this->query("INSERT INTO telldusEvent (tid, event, value) VALUES ($tid, '".$this->event."', ".$this->value.")");
            }
        }
    }
}

class telldusSchedules extends mysql
{
	private $events = array();
	public function __construct()
	{
		parent::__construct();
		$res = $this->query("SELECT telldusSchedule.id FROM telldusSchedule LEFT JOIN telldusEvent on telldusEvent.id=eventid ORDER BY tid, daysOfWeek, hours+0");
		//$res = $this->query("SELECT id FROM telldusSchedule GROUP BY daysOfWeek ORDER BY tid") or die(mysql_error());
		while($row = mysqli_fetch_assoc($res))
		{
			$this->events[] = new telldusSchedule($row["id"]);
		}
	}

	public function __toString()
	{
		$cron = array();
		foreach($this->events as $event)
		{
			$cron[] = $event->getCronRow();
		}

		return implode("\n",$cron);
	}

	public function getEvents()
	{
		return $this->events;
	}
}

class telldusSchedule extends mysql
{
	public $id;
	public $tid;
	public $event;
	public $value;
	public $minutes = "*";
	public $hours = "*";
	public $daysOfMonth = "*";
	public $months = "*";
	public $daysOfWeek = "*";

	public function __construct($id=0)
	{
		parent::__construct();
		$id = (int) $id;
		if($id > 0)
		{
			$res = $this->query("SELECT telldusSchedule.*, telldusEvent.event, telldusEvent.value, telldusEvent.tid FROM telldusSchedule LEFT JOIN telldusEvent ON telldusEvent.id=telldusSchedule.eventid WHERE telldusSchedule.id=$id");
			$obj = mysqli_fetch_object($res);
			if($obj)
			{
				$this->id = $obj->id;
				$this->tid = $obj->tid;
				$this->value = $obj->value;
				$this->event = $obj->event;
				$this->minutes = $obj->minutes;
				$this->hours = $obj->hours;
				$this->daysOfMonth = $obj->daysOfMonth;
				$this->months = $obj->months;
				$this->daysOfWeek = $obj->daysOfWeek;
			}
		}
	}

	public function getDaysOfWeek()
	{
		if($this->daysOfWeek == "*")
			return array(1,1,1,1,1,1,1);

		$array = array();
		$days = explode(",", $this->daysOfWeek);
		for($i=0;$i<7;$i++)
		{
			if(in_array($i,$days))
				$array[$i] = 1;
			else
				$array[$i] = 0;
		}

		return $array;
	}

	public function getCronRow()
	{
		if(!isset($this->event))
			return "";

        $tid = $this->tid;

		$return  = $this->minutes." ";
		$return .= $this->hours." ";
		$return .= $this->daysOfMonth." ";
		$return .= $this->months." ";
		$return .= $this->daysOfWeek." ";
		$return .= "   curl \"localhost/telldus_ajax.php?id=$tid&action=".$this->event;

		if($this->event == "fade")
			$return .= " dimlevel=".$this->value;

        $return .= "\"";

		return $return;
	}

	public function save()
	{
		$array = array(	"minutes"=>$this->minutes,
						"hours"=>$this->hours,
						"daysOfMonth"=>$this->daysOfMonth,
						"months"=>$this->months,
						"daysOfWeek"=>$this->daysOfWeek);

		if(isset($this->id) && $this->id+0 > 0)
		{
			$id = (int) $this->id;

			foreach($array as $key=>$value)
			{
				if($value === 0 || $value === "")
					continue;

				$update[] = $key."=".$this->sqlesc($value);
			}

			$update = implode(",",$update);
			$res = $this->query("SELECT eventid FROM telldusSchedule WHERE id=$id");
			$row = mysqli_fetch_assoc($res);

            $telldusEvent = new telldusEvent($row["eventid"]);
            $telldusEvent->setEvent($this->event);
            $telldusEvent->setValue($this->value);
			$this->query("UPDATE telldusSchedule SET $update WHERE id=$id");
		}
		else
		{
            $telldusEvent = new telldusEvent();
            $telldusEvent->create($this->tid, $this->event, $this->value);
            $eventid = $telldusEvent->getId();
            $array["eventid"] = $eventid;

			$keys = array();
			$values = array();
			foreach($array as $key=>$value)
			{
				if($value === 0 || $value === "")
					continue;

				$keys[] = $key;
				$values[] = $this->sqlesc($value);
			}

			$keys = implode(",",$keys);
			$values = implode(",",$values);

			$this->query("INSERT INTO telldusSchedule ($keys) VALUES ($values)");
			$this->id=$this->insert_id();
		}

		new crontab();
	}

	public function delete()
	{
		if(!isset($this->id))
			return;

		$id = (int) $this->id;
		$this->query("DELETE FROM telldusSchedule WHERE id=$id");
		$telldusEvent = new telldusEvent($this->event);
		$telldusEvent->delete();
		new crontab();
	}
}

class crontab
{
	public function __construct()
	{
		//$objects[] = new rssfeeds();
		$objects[] = new telldusSchedules();

		$print = implode("\n",$objects);
		$print .= "\n";
		$file = fopen("/tmp/crontab","w");
		if(!$file)
		{
			die("failed to open crontab");
		}

		fwrite($file,$print);
		fclose($file);
		system("crontab /tmp/crontab");
		unlink("/tmp/crontab");
	}
}

class mysql
{
	private $handle = 0;
	private $error = true;
	private $link;
	protected $sql;
	public function __construct()
	{
		$this->sql = mysqlCore::getInstance();
		$this->link = $this->sql->getLink();
		mysqli_set_charset($this->link, 'utf8');
	}

	public function sqlesc($str, $fnutts=true)
	{
		if($str == "" && $str != "0")
			return "NULL";

		if($fnutts)
			return "'".mysqli_real_escape_string($this->link, $str)."'";
		else
			return mysqli_real_escape_string($this->link, $str);
	}

	public function query($query,$error = true)
	{
		if($res = @mysqli_query($this->link, $query))
		{
			$this->handle = $res;
			return $res;
		}
		elseif($this->error)
		{
            $bt = debug_backtrace();
            $error_string = "[".date("Y-m-d H:i:s")."] ".mysqli_error($this->link)." in ".$bt[0]["file"].":".$bt[0]["line"]."\n";
            print $error_string;
			//$file = fopen("/var/www/include/mysqlerror","a");
			//fwrite($file, $error_string);
			//fclose($file);
		}
	}

	public function insert_id()
	{
		return mysqli_insert_id($this->link);
	}

	public function num_rows()
	{
		return $this->handle->num_rows;
	}

	public function result($query)
	{
		die("deprecated function");
		$res = $this->query($query);
		return mysqli_result($this->link, $res,0);
	}

	public function passhash($str)
	{
		return md5("hdwkias".md5($str)."afnsafno");
	}

	public function fetch($func)
	{
		$res = $this->handle;
		if(!$res)
			return;

		while($row = mysqli_fetch_assoc($res))
		{
			$func($row);
		}
	}

	public function fetch_obj($func)
	{
		$res = $this->handle;
		if(!$res)
			return;

		while($row = mysqli_fetch_object($res))
		{
			$func($row);
		}
	}

	public function error()
	{
        return mysqli_error($this->link);
	}
}

class mysqlCore
{
	private static $instance = null;
	private $link = null;

	private function __construct()
	{
		$this->link = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
	}

	public function getLink()
	{
		return $this->link;
	}

	public function noError()
	{
		$this->errno = false;
	}

	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}

		return self::$instance;
	}

	public function __destruct()
	{
		mysqli_close($this->link);
	}
}
