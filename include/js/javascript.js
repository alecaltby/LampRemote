function checkChanges()
{
	$.ajax(
	{
		url: 'ajax.php',
		data: 'action=checkChanges',
		dataType: 'html',
		success: function (data)
		{
			if(data)
			{
				$("#fetchChanges").display();
			}
		}
	});
}


function fetchChanges()
{
	$.ajax(
	{
		url: 'ajax.php',
		data: 'action=fetchChanges',
		dataType: 'html',
		success: function (data)
		{
			//window.location.reload();
		}
	});
}

function listenCode(obj)
{
	var div = $('#newTelldus');
	//$($(obj).children()[0]).attr('src','img/program.gif');
	var house = $(div.find('input[name="house"]'));
	var unit = $(div.find('input[name="unit"]'));
	$('#listenInfo').html('<img src="img/loading.gif" /> Listening');

	$.ajax(
	{
		url: 'ajax.php',
		data: 'action=listenCode',
		dataType: 'json',
		success: function (data)
		{
			//$($(obj).children()[0]).attr('src','img/program.png')
			$('#listenInfo').html('Unit: '+data.unit+', House: '+data.house)
			$('#house').html()
		}
	});
}

function changePage(column,action)
{
//	$('#'+column).html('<img src="include/images/loading.gif" />')
	$.ajax(
	{
		url: 'ajax.php',
		data: 'action='+action,
		dataType: 'html',
		success: function (data)
		{
			$('#'+column).html(data);
			$('#'+column).slideDown("fast");
		}
	});
}

function updateContent(id,url)
{
	$.ajax(
	{
		url: url,
		dataType: 'html',
		success: function(data)
		{
			$('#'+id).html(data);
		}
	})
}

function telldus(id,action,value)
{
	var url_data = 'action='+action+'&id='+id
	if(value != undefined)
		url_data += '&dimlevel='+value
	$.ajax(
	{
		url: 'telldus_ajax.php',
		data: url_data,
		dataType: 'html',
		success: function (data)
		{}
	});
}

function setScene(id)
{
	$.ajax(
	{
		url: 'telldus_ajax.php',
		data: 'sceneid='+id,
		dataType: 'html',
		success: function (data)
		{}
	});
}

function addScene()
{
	var data = 'action=addScene';
	data += '&'+$('#addScene').serialize();

	$.ajax(
	{
		url: 'ajax.php',
		data: data,
		dataType: 'html',
		success: function (data)
		{
			$('#addScene').parent().append(data);
		}
	});
}

function addEventToScene(id)
{
	var data = 'action=addEventToScene&sceneid='+id;
	$.ajax(
	{
		url: 'ajax.php',
		data: data,
		dataType: 'html',
		success: function (data)
		{
			$('#scene'+id).append(data);
		}
	});
}

function deleteScene(id)
{
	var data = 'action=deleteScene&sceneid='+id;
	var parent = $('#scene'+id).parent();

	$.ajax(
	{
		url: 'ajax.php',
		data: data,
		dataType: 'html',
		success: function (data)
		{
			parent.fadeOut("fast", function(){parent.remove()});
		}
	});
}

function editEvent(id)
{
	var data = 'action=editEvent';
	data += '&'+$('#event'+id).serialize();

	$.ajax(
	{
		url: 'ajax.php',
		data: data,
		dataType: 'html',
		success: function (data)
		{
			$('#scene'+id).append(data);
		}
	});
}

function deleteEvent(id)
{
	var data = 'action=deleteEvent&eventid='+id;
	var event = $('#event'+id);

	$.ajax(
	{
		url: 'ajax.php',
		data: data,
		dataType: 'html',
		success: function (data)
		{
			event.fadeOut("fast", function(){event.remove()});
		}
	});
}

function addTelldus()
{
	var id = "newTelldus";
	var tid = $('#'+id+' [name=id]').val()
	var name = $('#'+id+' [name=name]').val()
	var dimable = ($('#'+id+' [name=dimable]').is(':checked') ? 1 : 0)
	var groups = $('#'+id+' [name=groups]').val()
	var unit = $('#'+id+' [name=unit]').val()
	var house = $('#'+id+' [name=house]').val()
	var url = 'action=addTelldus&name='+encodeURIComponent(name)+'&dimable='+dimable+'&groups='+groups+'&unit='+unit+'&house='+house;

	$.ajax(
	{
		url: 'ajax.php',
		data: url,
		dataType: 'html',
		success: function (data)
		{
			$('#telldusConfig').append(data);
		}
	});
}

function editTelldus(id)
{
	var tid = $('#'+id+' [name=id]').val()
	var name = $('#'+id+' [name=name]').val()
	var dimable = ($('#'+id+' [name=dimable]').is(':checked') ? 1 : 0)
	var groups = $('#'+id+' [name=groups]').val()
	var unit = $('#'+id+' [name=unit]').val()
	var house = $('#'+id+' [name=house]').val()
	var url = 'action=editTelldus&id='+tid+'+&name='+encodeURIComponent(name)+'&dimable='+dimable+'&groups='+groups+'&unit='+unit+'&house='+house;

	$.ajax(
	{
		url: 'ajax.php',
		data: url,
		dataType: 'html',
		success: function (data)
		{
			if(data != "")
				alert(data)
		}
	});
}

function reloadConfiguration()
{
	$('#reloadInfo').html('Reloading <img src="img/loading.gif" />');
	$.ajax(
	{
		url: 'ajax.php',
		data: 'action=reloadConfiguration',
		dataType: 'html',
		success: function (data)
		{
			if(data != "")
				$('#reloadInfo').html(data);
			else
				$('#reloadInfo').html('Done');
		}
	});
}

function programTelldus(id,obj)
{
	$($(obj).children()[0]).attr('src','img/program.gif')
	$.ajax(
	{
		url: 'ajax.php',
		data: 'action=programTelldus&id='+id,
		dataType: 'html',
		success: function (data)
		{
			if(data != "")
				alert(data)
			$($(obj).children()[0]).attr('src','img/program.png')
		}
	});
}

function delTelldus(id)
{
	tid=$('#'+id+' [name=id]').val()

	$.ajax(
	{
		url: 'ajax.php',
		data: 'action=delTelldus&id='+tid,
		dataType: 'html',
		success: function (data)
		{
			if(data != "")
				alert(data)
			$('#'+id).fadeOut()
		}
	});
}

function addOrEditTelldusSchedule(rowId)
{
	var id = $('#'+rowId+' [name=id]').val()
	var tid = $('#'+rowId+' [name=tid]').val()
	var minutes = $('#'+rowId+' [name=minutes]').val()
	var hours = $('#'+rowId+' [name=hours]').val()
	var daysOfMonth = $('#'+rowId+' [name=daysOfMonth]').val()
	var months = $('#'+rowId+' [name=months]').val()
	var daysOfWeek = $('#'+rowId+' [name=daysOfWeek]').val()
	var event = $('#'+rowId+' [name=event]').val()
	var value = $('#'+rowId+' [name=value]').val()

	var url = [];

	if(id)
	{
		url.push("id="+id)
	}

	url.push("action=telldusSchedule")
	url.push('tid='+tid)
	url.push('minutes='+minutes)
	url.push('hours='+hours)
	url.push('daysOfMonth='+daysOfMonth)
	url.push('months='+months)
	url.push('daysOfWeek='+daysOfWeek)
	url.push('event='+event)
	url.push('value='+value)

	url_string = url.join('&')

	$.ajax(
	{
		url: 'ajax.php',
		data: url_string,
		dataType: 'html',
		success: function (data)
		{
			if(data != "")
				alert(data)
			changePage('rightColumn','vTelldusSchedules')
		}
	});
}

function delTelldusSchedule(rowId)
{
	id=$('#'+rowId+' [name=id]').val()

	$.ajax(
	{
		url: 'ajax.php',
		data: 'action=delTelldusSchedule&id='+id,
		dataType: 'html',
		success: function (data)
		{
			if(data != "")
				alert(data)
			$('#'+rowId).fadeOut()
		}
	});
}

function telldusSchedulesSelect(obj)
{
	var id = obj.value;
	if(id.includes("scene"))
	{
		$("#telldusSchedulesType").val("scene");
		$("#telldusSchedulesEventAction").hide();
		$("#telldusSchedulesEventValue").hide();
	}
	else
	{
		$("#telldusSchedulesType").val("event");
		$("#telldusSchedulesEventAction").show();
		$("#telldusSchedulesEventValue").show();
	}
}

function toggleClass(obj)
{
	var current = $(obj)
	var index = parseInt(current.attr("halfhour"),10);
	var prev = undefined
	var next = undefined

	for(i=0;i<current.siblings().length;i++)
	{
		if($(current.siblings()[i]).attr("halfhour") == (index-1))
			prev = $(current.siblings()[i])
		else if($(current.siblings()[i]).attr("halfhour") == (index+1))
			next = $(current.siblings()[i])

		if(next != undefined && prev != undefined)
			break
	}

	current.toggleClass("telldus_on")
    var j = i;
    while(j > 0 && current.siblings()[j] != undefined)
    {
    }

	if(current.hasClass("telldus_on"))
	{
		if(prev != undefined && prev.hasClass("telldus_turn_on"))
		{

		}
		else
		{
		}


		if(next != undefined && next.hasClass("telldus_on"))
		{
			next.css("border-top-left-radius","0px");
			next.css("border-bottom-left-radius","0px");
		}
		else
		{
			current.css("border-top-right-radius",radius_size);
			current.css("border-bottom-right-radius",radius_size);
		}
	}
	else
	{
		current.css("border-top-right-radius","0px");
		current.css("border-bottom-right-radius","0px");
		current.css("border-top-left-radius","0px");
		current.css("border-bottom-left-radius","0px");
		if(prev != undefined && prev.hasClass("telldus_on"))
		{
			prev.css("border-top-right-radius",radius_size);
			prev.css("border-bottom-right-radius",radius_size);
		}

		if(next != undefined && next.hasClass("telldus_on"))
		{
			next.css("border-top-left-radius",radius_size);
			next.css("border-bottom-left-radius",radius_size);
		}
	}

}

function generateCrontab(row)
{
	var obj = $(row)
	var status = false
	var events = []
	obj.children().each(function(i)
	{
		if(!status && $(this).hasClass("telldus_on"))
		{
			events.push([i,"on"])
			status = true
		}
		else if(status && $(this).hasClass("telldus_off"))
		{
			events.push([i,"off"])
			status = false
		}
	})

	for(i=0;i<events.length;i++)
	{
		console.log(events[i][1]+" "+events[i][0])
	}
}
