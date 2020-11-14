<?php
require_once("include/telldus_api.php");

interface view
{
	public function display();
}

class vLamps
{
	private $action;

	public function __construct($action="vTellduses")
	{
		$this->action = $action;
	}

	public function __toString()
	{
		print $this;
	}

	public function display()
	{
		$left = new column('leftColumn');
		$left->setWidth(150);
		$left->addClass('submenu');
		$right = new column('rightColumn');
		$right->setWidth(740);

		$submenu = new submenu();
		$submenu->add('Devices','vTellduses','rightColumn');
		$submenu->add('Scenes','vTelldusEvents','rightColumn');
		$submenu->add('Schedule','vTelldusSchedules','rightColumn');
        //$submenu->add('Visualize','vTelldusScheduleVisualize','rightColumn');
		$submenu->add('Configuration','vTelldusConfig','rightColumn');

		$left->add($submenu);

		switch($this->action)
		{
			case "vTellduses":
			case "vTelldusEvents":
			case "vTelldusSchedules":
			case "vTelldusScheduleVisualize":
			case "vTelldusConfig":
				$right->add(new $this->action);
			break;

			default:
				$right->add(new vTellduses());
		}

		print '<div class="separator4">';
		$left->display();
		$right->display();

		print '<div class="footer"></div>';
		print '</div>';

	}
}

class vRunning implements view
{
	public function __construct()
	{
	}
	
	public function __toString()
	{
		return file_get_contents("running-stats/index.html");
	}
	
	public function display()
	{
		print $this;
	}
}

class vTellduses implements view
{
	private $tellduses;
	private $scenes;

	public function __construct()
	{
		$tellduses = new tellduses();
		$tellduses->getById(0);
		$this->tellduses = $tellduses->getTellduses();
		$this->scenes = getScenes();
	}

	public function __toString()
	{
		if(count($this->tellduses) == 0 && count($this->scenes) == 0)
		{
			$return = "<h3 align=\"center\">No devices registered, go to configuration to add a device</h3>";
		}
		else
		{
			$return = '<div style="width: 300px; margin: 0 auto;">';

			foreach($this->scenes as $s)
				$return .= new vScene($s);
			foreach($this->tellduses as $t)
				$return .= new vTelldus($t);

			$return .= '</div>';
		}

		return $return;
	}

	public function display()
	{
		print $this;
	}
}

class vTelldus implements view
{
	private $width=300;
	private $telldus;

	public function __construct($telldus)
	{
		$this->telldus=$telldus;
	}

	public function __toString()
	{
		$w = $this->width;
		$t = $this->telldus;

		$return = '<fieldset style="width: '.$w.'px;">';
		$return .= '<legend>'.$t->getName().'</legend>';

		$return .= '<div class="left" style="width:'.($w*0.2).' px;">';
		$return .= 'On: <input type="radio" onclick="telldus('.$t->getId().',\'on\')" name="lamp'.$t->getId().'" '.($t->getState() ? "checked":"" ).' /><br />';
		$return .= 'Off: <input type="radio" onclick="telldus('.$t->getId().',\'off\')" name="lamp'.$t->getId().'" '.($t->getState() ? "":"checked" ).' />';
		$return .= '</div>';

		if($t->getDimable())
		{
            $return .= '<div class="right">';
            $return .= '<input onchange="telldus('.$t->getId().', \'fade\', this.value);" style="height: 30px; width: 200px; border-radius: 15px; background-color: rgba(0,0,0,0);" type="range" min="0" max="100" value="'.$t->getDimlevel().'">';
            $return .= '</div>';
		}

//		$return .= '<div class="left box" style="width:'.$w*0.2.'px;">';
//		$return .= '</div>';

		$return .= '</fieldset>';

		return $return;
	}

	public function display()
	{
		print $this;
	}

	public function setWidth($width)
	{
		$this->width = (int) $width;
	}
}

class vtelldusEvents implements view
{
	public function __construct()
	{
	}

	public function display()
	{
		print $this;
	}

	public function __toString()
	{
		$sceneEdit = new vSceneEdit();
		return "".$sceneEdit;
	}
}

class vEventEdit implements view
{
	private $event;
	public function __construct($event)
	{
		$this->event = $event;
	}

	public function display()
	{
		print $this;
	}

	private function getTelldusSelectList($selectedId)
	{
		$tellduses = new tellduses();
		$tellduses->getById(0);

		$return = "<select onchange=\"editEvent(".$this->event->getId().")\" name=\"telldusid\">";
		foreach($tellduses->getTellduses() as $t)
		{
			$selected = $selectedId == $t->getId() ? 'selected="selected"' : '';
			$return .= '<option value="'.$t->getId().'" '.$selected.'>'.$t->getName().'</option>';
		}
		$return .= "</select>";

		return $return;
	}

	private function getEventSelectList()
	{
		$on = "";
		$off = "";
		$fade = "";

		$e = $this->event->getEvent();
		$$e= 'selected="selected"';

		$return = '<select onchange="editEvent('.$this->event->getId().')" name="event">';
		$return .= "<option $on value=\"on\">On</option>";
		$return .= "<option $off value=\"off\">Off</option>";
		if($this->event->getTelldus()->getDimable())
		{
			$return .= "<option $fade value=\"fade\">Fade</option>";
		}
		$return .= '</select>';

		return $return;
	}

	private function getFadeValue()
	{
		if($this->event->getTelldus()->getDimable())
		{
			$value = $this->event->getValue();
			return "<input onchange=\"editEvent(".$this->event->getId().")\" type=\"text\" size=\"2\" name=\"value\" maxlength=\"3\" value=\"$value\" />";
		}
		else
		{
			return "";
		}
	}

	public function __toString()
	{
		$telldusId = $this->event->getTelldus()->getId();
		$eventid = $this->event->getId();
		$return =  '<form id="event'.$eventid.'">';
		$return .= '<input type="hidden" name="eventid" value="'.$eventid.'" />';
		$return .= '<div class="inputEventEdit">'.$this->getTelldusSelectList($telldusId).'</div>';
		$return .= '<div class="inputEventEdit">'.$this->getEventSelectList().'</div>';
		$return .= '<div class="inputEventEdit">'.$this->getFadeValue().'</div>';
		$return .= '<div class="right"><img class="onclick" onclick="deleteEvent('.$eventid.');" style="padding-top: 15px;" src="img/del.png" /></div>';
		$return .= '<div class="footer" style="margin: 10px; height: 1px;"></div>';
		$return .= '</form>';
		return $return;
	}
}

class vSceneEdit implements view
{
	private $scenes;
	public function __construct()
	{
		$this->scenes = getScenes();
	}

	public function display()
	{
		print $this;
	}

	public function __toString()
	{
		$return = '<div style="width: 500px; margin: 0 auto;">';
		$return .= '<fieldset style="width: 500px;" id="addScene">';
		$return .= '<legend class="h3">Create new scene</legend>';
		$return .= '<input style="margin-right: 10px;" type="text" name="name" /><button onclick="addScene();">Create</button>';
		$return .= '</fieldset>';
		foreach($this->scenes as $scene)
		{
			$events = $scene->getEvents();
			$divId = "scene".$scene->getId();
			$return .= '<fieldset style="width: 500px;">';
			$return .= '<legend align="left" class="h3 onclick" onclick="$(\'#'.$divId.'\').slideToggle();">'.$scene->getName().'</legend>';
			$return .= "<div class=\"\" id=\"$divId\">";
			$return .= '<div class="left" style="width: 125px;">Device:</div><div class="left" style="width: 80px;">Event:</div><div class="left">Fade level:</div>';
			$return .= '<div class="footer"></div>';
			foreach($events as $event)
			{
				$return .= (new vEventEdit($event));
			}
			$return .= "</div>";
			$return .= '<div style="text-align: center; margin-bottom: 10px;">';
			$return .= '<a class="onclick" onclick="addEventToScene('.$scene->getId().');">Add event</a> | ';
			$return .= '<a class="onclick" onclick="deleteScene('.$scene->getId().');">Remove scene</a>';
			$return .= '</div>';
			$return .= '</fieldset>';
		}

		$return .= "</div>";
		return $return;
	}
}

class vScene implements view
{
	private $width=300;
	private $scene;

	public function __construct($scene)
	{
		$this->scene=$scene;
	}

	public function __toString()
	{
		$w = $this->width;
		$s = $this->scene;

		$return = '<fieldset style="width: '.$w.'px;">';
		$return .= '<legend>'.$s->getName().'</legend>';

		$return .= '<div class="left" style="width:'.($w*0.2).' px;">';
		$return .= '<button onclick="setScene(\''.$s->getId().'\')">Activate</button>';
		$return .= '</div>';

		$return .= '</fieldset>';

		return $return;
	}

	public function display()
	{
		print $this;
	}

	public function setWidth($width)
	{
		$this->width = (int) $width;
	}
}

class vTelldusSchedules
{
	protected $events;
	protected $tellduses;
	protected $scenes;
	protected $hashes;

	public function __construct()
	{
		$eventSchedules = new telldusSchedules();
		$sceneSchedules = new telldusSchedules();
		$eventSchedules->fetchEvents();
		$sceneSchedules->fetchScenes();
		$events = $eventSchedules->get();
		$scenes = $sceneSchedules->get();

		$tellduses = new tellduses();
		$tellduses->getById(0);
		$this->tellduses = $tellduses->getTellduses();
		$this->hashes = [md5("*****")];

		$this->events = $this->sortByTime($events);
		$this->scenes = $this->sortByTime($scenes);
	}

	private function sortByTime($events)
	{
		$sorted = [];
		$eventsSortByHash = [];
		foreach($events as $event)
		{
			$id = $this->getHashId($event);
			$eventsSortByHash[$id][] = $event;
		}

		foreach($eventsSortByHash as $eventsGrouped)
		{
			foreach($eventsGrouped as $event)
			{
				$sorted[] = $event;
			}
		}

		return $sorted;
	}

	private function getHashId($event)
	{
		if($event->sun != "none")
		{
			$hash = md5("".$event->sun.$event->daysOfMonth.$event->months.$event->daysOfWeek);
		}
		else
		{
			$hash = md5("".$event->minutes.$event->hours.$event->daysOfMonth.$event->months.$event->daysOfWeek);
		}
		foreach($this->hashes as $key=>$value)
		{
			if($hash == $value)
			{
				$id = $key;
			}
		}

		if(isset($id))
		{
			return $id;
		}
		else
		{
			$this->hashes[] = $hash;
			return count($this->hashes)-1;
		}
	}

	private function getColor($event)
	{
		$id = $this->getHashId($event);
		$red = (($id*10+$id*70)%255);
		$green = (($id*20+$id*30)%255);
		$blue = (($id*20+$id*90)%255);

		if(strlen($red) == 1)
			$red = "0".$red;

		if(strlen($green) == 1)
			$green = "0".$green;

		if(strlen($blue) == 1)
			$blue = "0".$blue;

		return "rgba($red, $green, $blue, 0.5)";
	}

	protected function timeTable($events)
	{
		$print = "<div style=\"margin: 10px;\">";
		$print .= "<select>";
		foreach($this->tellduses as $target)
			$print .= "<option value=\"".$target->getId()."\" ".($events[0]->tid == $target->getId() ? "selected=\"selected\"" : "").">".$target->getName()."</option>";
		$print .= "</select>";
		$print .= "<div class=\"right\" style=\"margin-top: 4px;\">";

		$daysOfWeek = $events[0]->getDaysOfWeek();
		$i = 0;
		foreach(array("Mån","Tis","Ons","Tors","Fre","Lör","Sön") as $day)
		{
			$print .= " <input ".($daysOfWeek[$i] ? "checked=\"checked\"" : "")." type=\"checkbox\" /> $day";
			$i++;
		}

		$print .= "</div>";
		$print .= "</div>";

		$print .= "<div class=\"footer\" style=\"height: 30px;\">";
		$print .= "<div style=\"height: 13px; font-size: 10px; text-align: left;\">";
		for($i=0;$i<24;$i++)
		{
			$print .= "<div style=\"float: left; width: 26px; height: 13px;\">$i</div>";
		}

		$print .= "</div><div style=\"height: 13px;\">";

		$class = "";
		$style = "";
		$classes = array();
		$lastOn = 0;
		for($i=0;$i<48;$i++)
		{
			foreach($events as $event)
			{
				if($i == ($event->hours*2 + ($event->minutes == 30 ? 1 : 0)))
				{
					if($event->event == "on")// && $class == "")
					{
                        $classes[$i] = "telldus_turn_on";
						$lastOn = $i;
					}
					elseif($event->event == "off")// && $class == "telldus_on")
					{
                        $classes[$lastOn] = "telldus_turn_on";
                        for($j=$lastOn+1; $j<$i; $j++)
                        {
                            $classes[$j] = "telldus_on";
                        }

						$classes[$i] = "telldus_turn_off";
					}
					break;
				}
				else
				{
                    $classes[$i] = "";
				}
			}
        }



        for($i=0;$i<48;$i++)
		{
			$time = (($i/2)%24).":".($i%2 == 0 ? "00" : "30");
			$print .= "<div halfhour=\"$i\" onclick=\"toggleClass(this)\" title=\"$time\" class=\"".$classes[$i]."\" style=\"cursor: pointer; width: 13px; height: 13px; float: left;\"></div>";
		}

		$print .= "</div></div>";
		return $print;
	}

	public function __toString()
	{
		$print = '<h2>Schedule</h2>';
		$print .= '<form>';
		$print .= '<table width="100%" cellspacing="0" cellpadding="5">';
		$print .= '<tr><td>Id</td><td>Minute</td><td>Hour</td><td>Day of month</td><td>Month</td><td>Day of week</td><td>Sun</td><td>Event</td><td>Fade value</td><td></td><td></td></tr>';
		$print .= $this->getRow(new telldusSchedule());
		$print .= '<tr><td colspan="10"><h2>Lamps</h2></td></tr>';

		foreach($this->events as $event)
		{
			$print .= $this->getRow($event);
		}

		$print .= '<tr><td colspan="10"><h2>Scenes</h2></td></tr>';

		foreach($this->scenes as $event)
		{
			$print .= $this->getRow($event);
		}

		$print .= '</table>';
		$print .= '</form>';

		return $print;
	}

	private function getRow($schedule)
	{
		if(!$schedule->id)
			$id = "newTelldusSchedule";
		else
			$id = "telldusSchedule".$schedule->id;

		$event = "";
		$value = "";
		$tid = 0;

		if(isset($schedule->event) && $schedule->type == "event")
		{
			$event = $schedule->event->getEvent();
			$value = $schedule->event->getValue();
			$tid = $schedule->event->getTelldus()->getId();
		}

		$print = '<tr style="background-color: '.$this->getColor($schedule).';" id="'.$id.'">';
		$print .= '<td><input class="telldusSchedules" type="hidden" name="id" value="'.$schedule->id.'" />'.$this->selectTellduses($schedule).'</td>';
		$print .= '<td><input class="telldusSchedules" type="text" style="width: 90%;" name="minutes" value="'.$schedule->minutes.'" /></td>';
		$print .= '<td><input class="telldusSchedules" type="text" style="width: 90%;" name="hours" value="'.$schedule->hours.'" /></td>';
		$print .= '<td><input class="telldusSchedules" type="text" style="width: 90%;" name="daysOfMonth" value="'.$schedule->daysOfMonth.'" /></td>';
		$print .= '<td><input class="telldusSchedules" type="text" style="width: 90%;" name="months" value="'.$schedule->months.'" /></td>';
		$print .= '<td><input class="telldusSchedules" type="text" style="width: 90%;" name="daysOfWeek" value="'.$schedule->daysOfWeek.'" /></td>';
		$print .= '<td>'.$this->selectSun($schedule->sun).'</td>';

		if($schedule->type == "event")
		{
			$print .= '<td>'.$this->selectEvents($event).'</td>';
			$print .= '<td><input id="telldusSchedulesEventValue" class="telldusSchedules" type="text" style="width: 90%;" name="value" value="'.$value.'" /></td>';
		}
		else
		{
			$print .= '<td colspan="2"></td>';
		}


		if(!$schedule->id)
		{
			$print .= '<td><a onclick="addOrEditTelldusSchedule(\''.$id.'\')"><img src="img/add.png" /></a></td>';
			$print .= '<td></td>';
		}
		else
		{
			$print .= '<td><a onclick="addOrEditTelldusSchedule(\''.$id.'\')"><img src="img/edit.png" /></a></td>';
			$print .= '<td><a onclick="delTelldusSchedule(\''.$id.'\')"><img src="img/del.png" /></a></td>';
		}
		$print .= '</tr>';

		return $print;
	}

	private function selectSun($event)
	{
		$print = '<select id="telldusSchedulesSunAction" class="telldusSchedules" name="sun">';
		$print .= '<option '.($event == 'none' ? 'selected="selected"' : '').' value="none"></option>';
		$print .= '<option '.($event == 'rise' ? 'selected="selected"' : '').' value="rise">Rise</option>';
		$print .= '<option '.($event == 'set' ? 'selected="selected"' : '').' value="set">Set</option>';
		$print .= '</select>';
		return $print;
	}

	private function selectEvents($event)
	{
		$print = '<select id="telldusSchedulesEventAction" class="telldusSchedules" name="event">';
		$print .= '<option '.($event == 'on' ? 'selected="selected"' : '').' value="on">On</option>';
		$print .= '<option '.($event == 'off' ? 'selected="selected"' : '').' value="off">Off</option>';
		$print .= '<option '.($event == 'fade' ? 'selected="selected"' : '').' value="fade">Fade</option>';
		$print .= '</select>';
		return $print;
	}

	private function selectTellduses($schedule)
	{
		if(!$schedule->event)
		{
			$print = '<select onchange="telldusSchedulesSelect(this);" class="telldusSchedules" name="tid">';
			$print .= '<optgroup label="Lamps">';
			foreach($this->tellduses as $telldus)
				$print .= '<option value="event:'.$telldus->getId().'">'.$telldus->getName().'</option>';

			$print .= '</optgroup>';
			$print .= '<optgroup label="Scenes">';

			foreach(getScenes() as $scene)
				$print .= '<option value="scene:'.$scene->getId().'">'.$scene->getName().'</option>';

			$print .= '</optgroup>';
			$print .= '</select>';
		}
		elseif($schedule->type == "event")
		{
			$id = $schedule->event->getId();
			$print = $schedule->event->getTelldus()->getName();
			$print .= "<input type=\"hidden\" name=\"tid\" value=\"event:$id\" />";
		}
		elseif($schedule->type == "scene")
		{
			$id = $schedule->event->getId();
			$print = $schedule->event->getName();
			$print .= "<input type=\"hidden\" name=\"tid\" value=\"scene:$id\" />";
		}

		return $print;
	}
}

class vTelldusScheduleVisualize extends vTelldusSchedules
{
    public function __construct()
    {
        parent::__construct();
    }

    public function __toString()
    {
		$prevTid = 0;
		$events = array();
		$prevTid = 0;
		$prevDaysOfWeek = 0;
		$i = 0;
		foreach($this->events as $event)
		{
			if($event->tid != $prevTid)
				$i=0;
			elseif($event->daysOfWeek != $prevDaysOfWeek)
				$i++;

			$events[$event->tid][$i][] = $event;
			$prevTid = $event->tid;
			$prevDaysOfWeek = $event->daysOfWeek;
		}

		$print = "<h2>Schedule</h2>";
		foreach($events as $event)
		{
			foreach($event as $daysOfWeek)
			{
				$print .= $this->timeTable($daysOfWeek);
			}
		}


		$print .= "<p><input class=\"footer\" type=\"button\" onclick=\"generateCrontab(document.getElementById('apa'));\" value=\"Generera\" /></p>";

		return $print;
    }
}

class vTelldusConfigRow
{
	private $telldus;

	public function __construct($telldus)
	{
		$this->telldus = $telldus;
	}

	public function __toString()
	{
		$telldus = $this->telldus;
		$id = "lamp".$telldus->getId();
		$groups = implode(",",$telldus->getGroups());
		$unit = $telldus->getUnit();
		$house = $telldus->getHouse();

		$print = '<tr id="'.$id.'">';
		$print .= '<input type="hidden" name="id" value="'.$telldus->getId().'" />';
		$print .= '<input type="hidden" name="groups" value="'.$groups.'" />';
		$print .= '<td><input onchange="editTelldus(\''.$id.'\');" type="text" name="name" value="'.htmlentities($telldus->getName()).'" /></td>';
// 		$print .= '<td><input onchange="editTelldus(\''.$id.'\');" type="text" style="width: 50%;" name="groups" value="'.$groups.'" /></td>';
		$print .= '<td><input onchange="editTelldus(\''.$id.'\');" type="text" size="8" name="house" maxlength="8" value="'.$house.'" /></td>';
		$print .= '<td><input onchange="editTelldus(\''.$id.'\');" type="text" size="2" maxlength="2" name="unit" value="'.$unit.'" /></td>';
		$print .= '<td><input onchange="editTelldus(\''.$id.'\');" type="checkbox" name="dimable" '.($telldus->getDimable() ? 'checked="checked"' : '').' /></td>';
		$print .= '<td><a onclick="programTelldus(\''.$telldus->getId().'\',this)" title="Program"><img src="img/program.png" /></a></td>';
		$print .= '<td><a onclick="delTelldus(\''.$id.'\')" title="Delete"><img src="img/del.png" /></a></td>';
		$print .= '</tr>';

		return $print;
	}
}

class vTelldusConfig
{
	public function __construct()
	{
	}

	public function __tostring()
	{
		$tellduses = new tellduses();
		$tellduses->getById(0);

		$print = '<fieldset>';
		$print .= '<legend class="h3">Devices</legend>';
		$print .= '<table id="telldusConfig" width="100%" cellspacing="5" border="0">';
		$print .= '<tr>';
		//$print .= '<td>Id</td>';
		$print .= '<td width="20%">Name</td>';;
		//$print .= '<td>Groups</td>';
		$print .= '<td width="10%">House</td>';
		$print .= '<td width="5%">Unit</td>';
		$print .= '<td width="30%">Dimable</td>';
		$print .= '<td width="3%"></td>';
		$print .= '<td width="3%"></td>';
		$print .= '</tr>';

		$print .= '<tr id="newTelldus">';
		$print .= '<input type="hidden" name="groups" value="" />';
		$print .= '<td><input type="text" name="name" value="" /></td>';
		$print .= '<td><input type="text" size="8" name="house" /></td>';
		$print .= '<td><input type="text" size="2" name="unit" /></td>';
		$print .= '<td><input type="checkbox" name="dimable" /></td>';
		$print .= '<td></td>';
		$print .= '<td><a onclick="addTelldus()" title="Add"><img src="img/add.png" /></a></td>';
		$print .= '</tr>';

		$print .= '<tr><td colspan="7"><hr style="margin: 5px;" /></td></tr>';

		foreach($tellduses->getTellduses() as $telldus)
		{
			$print .= new vTelldusConfigRow($telldus);
		}

		$print .= '</table>';
		$print .= '</fieldset>';
		$print .= '<fieldset>';
		$print .= '<legend class="h3">Listen for code</legend>';
		$print .= '<p>Press listen below and then click the button on your remote you want to listen for.</p>';
		$print .= '<button onclick="listenCode(this)">Listen</button>';
		$print .= '<p id="listenInfo"></p>';
		$print .= '</fieldset>';
		$print .= '<fieldset>';
		$print .= '<legend class="h3">Reload configuration</legend>';
		$print .= '<p>After adding or making changes to existing devices, reload the configuration in order for changes to take effect.</p>';
		$print .= '<button onclick="reloadConfiguration()">Reload configuration</button>';
		$print .= '<p id="reloadInfo"></p>';
		$print .= '</fieldset>';

		if(gotChanges())
		{
			$print .= '<fieldset id="fetchChanges">';
			$print .= '<legend class="h3">Fetch latest changes</legend>';
			$print .= '<p>Press below button to fetch latest changes.</p>';
			$print .= '<button onclick="fetchChanges()">Fetch</button>';
			$print .= '<p id="fetchChanges"></p>';
			$print .= '</fieldset>';
		}
		return $print;
	}
}

class submenu
{
	private $content = array();

	public function __construct()
	{
	}

	public function add($name,$site,$columnId)
	{
		$this->content[] = "<li><a onclick=\"changePage('$columnId','$site');\">$name</a></li>";
	}

	public function __toString()
	{
		$return = '<ul class="submenu">';
		$return .= implode('<div class="horisontal_separator"></div>',$this->content);
		$return .= '</ul>';
		return $return;
	}

	public function display()
	{
		print $this->__toString();
	}
}

class column
{
	private $content;
	private $classes;
	private $width;
	private $id;

	public function __construct($id)
	{
		$this->width = 100;
		$this->classes = array();
		if($id == "rightColumn")
			$this->id = $id;
		else
			$this->id = "leftColumn";
	}

	public function setWidth($width)
	{
		$this->width = (int) $width;
	}

	public function addClass($class)
	{
		$this->classes[] = $class;
	}

	public function getWidth()
	{
		return $this->width;
	}

	public function getClasses()
	{
		return $this->classes;
	}

	public function delClass($class)
	{
		if(in_array($class,$this->classes))
			for($i=0;$i<count($this->classes);$i++)
			{
				if($this->classes[$i] == $class)
					unset($this->classes[$i]);
			}
	}

	public function add($add)
	{
		$this->content .= $add;
	}

	public function display()
	{
		print $this;
	}

	public function __toString()
	{
		$classes = implode(" ",$this->classes);
		$return = '<div class="'.$classes.'" id="'.$this->id.'" style="width: '.$this->width.'px">';
		$return .= $this->content;
        $return .= '</div>';
		return $return;
	}
}

class homepage implements view
{
	private $menu;
	private $user;

	public function __construct($main)
	{
		$this->main = $main;
	}

	public function display()
	{
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
		"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html>
			<head>
				<title></title>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
				<link href="favicon.png" rel="shortcut icon" type="image/x-icon" />
				<script src="include/js/jquery-1.9.1.js"></script>
				<script src="include/js/javascript.js"></script>
				<link href="include/style.css" type="text/css" rel="stylesheet" />
			</head>
			<body>
				<div id="popup"></div>
				<div id="container">
					<div>
						<div id="main_top"></div>
						<div class="" id="main_middle">
							<div id="content">
							<?=$this->main->display();?>
							</div>
						</div>
						<div id="main_bottom"></div>
					</div>
				</div>
			</body>
		</html>
<!--
https://jquery.org/license/
https://tldrlegal.com/license/mit-license
-->
		<?php
	}
}
