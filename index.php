<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
		<meta name="description" content="UniBz Classroom checker">
		<meta name="author" content="Matthias Moroder">

		<title>UniBz Classroom checker</title>

		<link rel="stylesheet" href="style.css">
		<link href="bootstrap.min.css" rel="stylesheet">
		<script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

	</head>

	<body>

		<div class="container">
			<div class="header clearfix">
				<h3 class="text-muted">UniBz Classroom checker</h3>
			</div>
			<?php
			
			class Lecture { 
				public $room = null; 
				public $startTime = null; 
				public $endTime = null;
				public $building = null;
				public $location = null;
			} 
			
			date_default_timezone_set("Europe/Rome");

			$formDate = date("Y-m-d",time());
			$formBeginTime = date("H:00",time());
			$formEndTime = date("H:00",time()+7200);

			if(isset($_GET["begin_time"]) && isset($_GET["end_time"]))
			{
				$date = time();
				if(isset($_GET["date"]))
				{
					$date = strtotime($_GET["date"]);
				}
				$today = date("d.m.Y",$date);
				$myStart = strtotime($today.$_GET["begin_time"]);
				$myEnd = strtotime($today.$_GET["end_time"]);
				if($myEnd < $myStart)
					$myEnd = $myStart;
				
				$formDate = date("Y-m-d",$date);
				$formBeginTime = date("H:i",$myStart);
				$formEndTime = date("H:i",$myEnd);

				$data = file_get_contents("http://aws.unibz.it/risweb/timetable.aspx?showtype=0&format=icalDow&start=$today&end=$today");				//http://aws.unibz.it/risweb/
				
				echo "<h3>Occupied rooms from $formBeginTime to $formEndTime on ".date_format((new DateTime())->setTimestamp($date), 'F jS').":</h3>";
				$lectures = explode("BEGIN:VEVENT",$data);		
				$occupiedBz = array();
				$occupiedBx = array();
				$occupiedOther = array();
				
				foreach($lectures as $lecture)
				{
					$start = strtotime(substr($lecture,strpos($lecture,"DTSTART:") + 8,16));
					$end = strtotime(substr($lecture,strpos($lecture,"DTEND:") + 6,16));
					$startPos = strpos($lecture,"LOCATION:")+9;
					$loc = substr($lecture,$startPos,strpos($lecture,"\n",$startPos) - $startPos);
					$loc = trim(str_replace("\\","",$loc));
					
					if(strlen(trim($loc)) != 0 && ($myStart >= $start && $myStart <= $end || $myEnd >= $start && $myEnd <= $end || $myStart < $start && $myEnd > $end))
					{
						$lectureObj = new Lecture();
						$lectureObj->startTime = $start;
						$lectureObj->endTime = $end;
						
						
						if(strpos($lecture,"Dan BX"))
						{
							$loc=substr($loc,0,strlen($loc)-8);
							$lectureObj->location="bx";
						}
						else if(strpos($lecture,"Ser-C"))
						{
							$lectureObj->building="C";
							$loc=substr($loc,0,strlen($loc)-7);
							$lectureObj->location="bz";
						}
						
						else if(strpos($lecture,"Ser-D"))
						{
							$lectureObj->building="D";
							$loc=substr($loc,0,strlen($loc)-7);
							$lectureObj->location="bz";
						}
						
						else if(strpos($lecture,"Ser-E"))
						{
							$lectureObj->building="E";
							$loc=substr($loc,0,strlen($loc)-7);
							$lectureObj->location="bz";
						}
						
						else if(strpos($lecture,"Ser-F"))
						{
							$lectureObj->building="F";
							$loc=substr($loc,0,strlen($loc)-7);
							$lectureObj->location="bz";
						}
						
						$lectureObj->room = $loc;
						
						if($lectureObj->location == "bz")
							array_push($occupiedBz,$lectureObj);
						else if($lectureObj->location == "bx")
							array_push($occupiedBx,$lectureObj);
						else
							array_push($occupiedOther,$lectureObj);
					}
				}
				
				sortByRoom($occupiedBz);
				sortByRoom($occupiedBx);
				sortByRoom($occupiedOther);
				
				?>
				<ul id='list' class='list-group'>
					<ul class="nav nav-tabs">
						<li><a href="#bz" data-toggle="tab">Bolzano (<?php echo sizeof($occupiedBz); ?>)</a></li>
						<li><a href="#bx" data-toggle="tab">Brixen (<?php echo sizeof($occupiedBx); ?>)</a></li>
						<li><a href="#other" data-toggle="tab">Other (<?php echo sizeof($occupiedOther); ?>)</a></li>
					</ul>
					
					<div class="tab-content" id="tabs">
						<div class="tab-pane" id="bz">
						<?php printLocation($occupiedBz); ?>
						</div>
						<div class="tab-pane" id="bx">
						<?php printLocation($occupiedBx); ?>
						</div>
						<div class="tab-pane" id="other">
						<?php printLocation($occupiedOther); ?>
						</div>
					</div>
				</ul>
				<?php
				
			}
			
				
			echo "      
			<div class='jumbotron'>
				<form id='inputForm'>
				<h2>Please, input a timespan</h2>
				<p>To discover which rooms are used at that time</p>
				  <input id='dateInput' type='date' name='date' value='$formDate'>
				  <br>
				  <input class='timeInput' step='900' id='left' type='time' name='begin_time' value='$formBeginTime'>
				  <p id='betweenInput'>to</p>
				  <input class='timeInput' step='900' id='right' type='time' name='end_time' value='$formEndTime'>
				  <input type='hidden' class='slider-input' id='slider' value='41' />
				  <input class='btn btn-lg btn-success' type='submit' value='check'>
				</form>
			</div>";
			
			function sortByRoom (&$array) {
				$sorter=array();
				$ret=array();
				reset($array);
				foreach ($array as $ii => $va) {
					$sorter[$ii]=$va->room;
				}
				asort($sorter);
				foreach ($sorter as $ii => $va) {
					$ret[$ii]=$array[$ii];
				}
				$array=$ret;
			}
			
			function printLocation($occupiedRooms)
			{
				if(empty($occupiedRooms))
				{
					echo "<li class='list-group-item' id='noRooms'>No known occupied rooms.</li>";
				}		
				else
				{					
					echo "<li class='list-group-item' id='listHeader'><div class='room'>Rooms</div><div class='time'>To</div><div class='time'>From</div></li>";
					$building = null;
					foreach($occupiedRooms as $lecture)
					{
						if($building != $lecture->building)
						{
							if(isset($building))
							{
								echo "</ul>";
							}
							$building = $lecture->building;
							echo "<li class='list-group-item' data-toggle='collapse' data-target='#list-building-$building'>Building $building<li>";
							echo "<ul id='list-building-$building' class='collapse'>";
							
						}
						echo "<li class='list-group-item'><div class='room'>".$lecture->room."</div><div class='time'>".date("H:i",$lecture->endTime)."</div><div class='time'>".date("H:i",$lecture->startTime)."</div></li>";
					}
				}
			}

			?>

			<footer class="footer">
				<p>&copy; 2017 - <a href='about.html'>About</a></p>
			</footer>

		</div>
		
		
		<script>		
		
		activaTab('bz');
		
		function activaTab(tab){
			$('.nav-tabs a[href="#' + tab + '"]').tab('show');
		};

		</script>
		
	</body>
</html>
