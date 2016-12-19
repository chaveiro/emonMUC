<?php
	global $path;
?>

<link href="<?php echo $path; ?>Modules/muc/Lib/style.css" rel="stylesheet">
<script type="text/javascript" src="<?php echo $path; ?>Modules/driver/Views/driver.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/device/Views/device.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/muc/Lib/muc-table-fields.js"></script>

<style>
	#table input[type="text"] {
	    width: 88%;
	}
	#table td:nth-of-type(1) { width:14px; text-align: center; }
	#table td:nth-of-type(2) { width:10%;}
	#table td:nth-of-type(3) { width:10%;}
	#table th:nth-of-type(4), td:nth-of-type(4) { font-weight:normal; }
	#table th[fieldg="devices"] { font-weight:normal; }
	#table td:nth-of-type(5) { width:14px; text-align: center; }
	#table td:nth-of-type(6) { width:14px; text-align: center; }
	#table td:nth-of-type(7) { width:14px; text-align: center; }
</style>

<div>
	<div id="apihelphead" style="float:right;"><a href="api"><?php echo _('Driver API Help'); ?></a></div>
	<div id="localheading"><h2><?php echo _('Drivers'); ?></h2></div>

	<div id="table"><div align='center'></div></div>

	<div id="nodrivers" class="alert alert-block hide">
		<h4 class="alert-heading"><?php echo _('No drivers created'); ?></h4>
		<p>
			<?php echo _('Drivers are used to configure the basic communication with different devices.'); ?>
			<br><br>
			<?php echo _('A driver implements for example the necessary communication protocol, to read several configured energy metering devices (see the devices tab).'); ?>
			<br>
			<?php echo _('You may want the next link as a guide for generating your request: '); ?><a href="api"><?php echo _('Driver API helper'); ?></a>
		</p>
	</div>

	<div id="bottomtoolbar"><hr>
		<button id="newdriver" class="btn btn-small"><i class="icon-plus-sign" ></i>&nbsp;<?php echo _('New driver'); ?></button>
	</div>

	<div id="driver-loader" class="ajax-loader"></div>
</div>

<?php require "Modules/driver/Views/dialog/view.php"; ?>
<?php require "Modules/device/Views/dialog/view.php"; ?>

<script>
	var path = "<?php echo $path; ?>";
	
	// Extend table library field types
	for (z in muctablefields) table.fieldtypes[z] = muctablefields[z];
	for (z in customtablefields) table.fieldtypes[z] = customtablefields[z];
	table.element = "#table";
	table.groupprefix = "MUC ";
	table.groupby = 'muc';

	table.deletedata = false;
	table.fields = {
		'disabled':{'title':'', 'type':"icondisable"},
		'name':{'title':'<?php echo _("Name"); ?>','type':"fixed"},
		'muc':{'title':'<?php echo _("Controller"); ?>','type':"fixed"},
		'devices':{'title':'<?php echo _("Devices"); ?>','type':"devicelist"},
		// Actions
		'device-action':{'title':'', 'type':"iconmuc", 'icon':'icon-plus-sign'},
		'delete-action':{'title':'', 'type':"delete"},
		'config-action':{'title':'', 'type':"iconconfig", 'icon':'icon-wrench'}
	}

	update();

	device.states = null;
	function update() {
		var requestTime = (new Date()).getTime();
		
		$.ajax({ url: path+"device/states.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
			if (device.states == null) {
				device.states = data;
				table.draw();
			}
			else device.states = data;
		}});
		
		$.ajax({ url: path+"driver/list.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
			table.timeServerLocalOffset = requestTime-(new Date(xhr.getResponseHeader('Date'))).getTime(); // Offset in ms from local to server time
			table.data = data;
			
			table.draw();
			if (table.data.length == 0) {
				$("#nodrivers").show();
				$("#localheading").hide();
			} else {
				$("#nodrivers").hide();
				$("#localheading").show();
			}
			$('#driver-loader').hide();
		}});
		
		$.ajax({ url: path+"muc/list.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
			if (data.length == 0) {
				$("#newdriver").prop('disabled', true);
			} else {
				$("#newdriver").prop('disabled', false);
			}
		}});
	}

	var updater;
	function updaterStart(func, interval)
	{
		clearInterval(updater);
		updater = null;
		if (interval > 0) updater = setInterval(func, interval);
	}
	updaterStart(update, 10000);

	$("#table").bind("onEdit", function(e){
		updaterStart(update, 0);
	});

	$("#table").bind("onDisable", function(e,id,row,disable){
		$('#driver-loader').show();

		// Get driver of clicked row
		var localdriver = table.data[row];
		var result = driver.update(localdriver['mucid'], localdriver['name'], localdriver);
		update();

		if (!result.success) {
			alert('Unable to update driver:\n'+result.message);
			return false;
		}
	});

	$("#table").bind("onResume", function(e){
		updaterStart(update, 10000);
	});

	$("#table").bind("onDelete", function(e,id,row){
		// Get driver of clicked row
		var localdriver = table.data[row];

		driver_dialog.loadDelete(localdriver, row);
	});

	$("#table").on('click', '.icon-wrench', function(){
		// Get driver of clicked row
		var driver = table.data[$(this).attr('row')];

		driver_dialog.loadConfig(driver);
	});

	$("#table").on('click', '.icon-plus-sign', function() {
		// Do not open dialog if the icon-plus-sign is used on a group header
		if(!$(this).attr('group')) {
			// Get driver of clicked row
			var driver = table.data[$(this).attr('row')];
			
			device_dialog.loadAdd(driver);
		}
	});

	$("#table").on('click', '.device-label', function() {
		$('#driver-loader').show();
		
		// Get the ids of the clicked lable
		var mucid = $(this).attr('mucid');
		var deviceid = $(this).attr('deviceid');
		
		var localdevice = device.get(mucid, deviceid);
		device_dialog.loadConfig(localdevice, true);
		
		$('#driver-loader').hide();
	});

	$("#newdriver").on('click', function (){
		driver_dialog.loadAdd(null);
	});
</script>
