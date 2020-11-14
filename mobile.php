<?php
require_once("include/telldus_api.php");
$mysql = new mysql();
?>
<!DOCTYPE html>
<html>
<head>
        <title>Telldus</title>
        <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
        <title>Index</title>
        <link rel="stylesheet" href="include/jquery.mobile-1.4.0.css" />
        <script src="include/js/jquery-1.9.1.js"></script>
        <script src="include/js/jquery.mobile-1.4.0.min.js"></script>
</head>
<body>
<div class="ui-body ui-body-a ui-corner-all">
<p>
<?php
$tellduses = new tellduses();
$tellduses->getById(0);
foreach(getScenes() as $s)
{
	print '<fieldset><div data-role="fieldcontain">';
	print '<div class="ui-grid-solo"><label class="ui-block-a" for="switch-'.$s->getId().'">'.$s->getName().'</label></div>';
	print '<div class="ui-grid-a">';
	print '<button id="scene-'.$s->getId().'" class="scene ui-btn">Activate</button>';
	print '</div>';
	print '</fieldset>';
}
foreach($tellduses->getTellduses() as $t)
{
  print '<fieldset><div data-role="fieldcontain">';
  print '<div class="ui-grid-solo"><label class="ui-block-a" for="switch-'.$t->getId().'">'.$t->getName().'</label></div>';
  print '<div class="ui-grid-a">';
  print '<div class="ui-block-a"><input type="checkbox" class="state" id="switch-'.$t->getId().'" data-role="flipswitch" data-theme="a" '.($t->getState() ? "checked" : "").'></div>';

  if($t->getDimable())
    print '<div class="ui-block-b slider" id="sliderId-'.$t->getId().'"><input type="range" id="slider'.$t->getId().'" value="'.$t->getDimlevel().'" min="0" max="255" data-theme="a" /></div>';

  print '</div>';
  print '</fieldset>';
}
?>

</p>
</div>
<script>
function changeState(id,value)
{
    ajaxGet('id='+id+'&action='+value);
}

function setDimLevel(id,value)
{
  ajaxGet('id='+id+'&action=dim&dimlevel='+value)
}

function ajaxGet(data)
{
  $.ajax({
    url: 'telldus_ajax.php',
    data: data,
    dataType: 'text'
  });
}

$(".scene").click(function()
{
	var id = $(this)[0].id.split("-")[1];
	ajaxGet('sceneid='+id);
});

$( ".state" ).change(function() {
  var id = $(this)[0].id.split("-");
  var action = (this.checked ? "on" : "off");
  changeState(id[1],action);
});

$( ".slider" ).on( 'slidestop', function( event ) {
  var id = this.id.split("-");
  var dimlevel = $("#slider"+id[1]).val();
  setDimLevel(id[1],dimlevel)
  $("#switch-"+id[1])[0].checked=true;
  $("#switch-"+id[1]).flipswitch("refresh");
});
</script>
</body>
</html>
<!--
https://jquery.org/license/
https://tldrlegal.com/license/mit-license
-->
