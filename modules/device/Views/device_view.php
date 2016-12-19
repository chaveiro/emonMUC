<?php
	global $path;

	$templates = array();
	foreach($device_templates as $key => $value)
	{
		$templates[$key] = ((!isset($value->name) || $value->name == "" ) ? $key : $value->name);
	}
?>

<link href="<?php echo $path; ?>Modules/muc/Lib/style.css" rel="stylesheet">
<script type="text/javascript" src="<?php echo $path; ?>Modules/device/Views/device.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/channel/Views/channel.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/muc/Lib/muc-table-fields.js"></script>

<style>
	#table input[type="text"] {
		width: 88%;
	}
	#table td:nth-of-type(1) { width:14px; text-align: center; }
	#table td:nth-of-type(2) { width:5%;}
	#table td:nth-of-type(3) { width:5%;}
	#table td:nth-of-type(4) { width:10%;}
	#table td:nth-of-type(5) { width:10%;}
	#table th:nth-of-type(6) { font-weight:normal; }
	#table td:nth-of-type(7), th:nth-of-type(7) { text-align: right; }
	#table td:nth-of-type(8), th:nth-of-type(8) { text-align: right; }
	#table td:nth-of-type(9), th:nth-of-type(9) { text-align: right; }
	#table th[fieldg="channels"] { font-weight:normal; }
	#table th[fieldg="time"] { font-weight:normal; text-align: right; }
	#table th[fieldg="state"] { font-weight:normal; text-align: right; }
	#table td:nth-of-type(10) { width:14px; text-align: center; }
	#table td:nth-of-type(11) { width:14px; text-align: center; }
	#table td:nth-of-type(12) { width:14px; text-align: center; }
</style>

<div>
	<div id="apihelphead" style="float:right;"><a href="api"><?php echo _('Devices API Help'); ?></a></div>
	<div id="localheading"><h2><?php echo _('Devices'); ?></h2></div>

	<div id="table"><div align='center'></div></div>

	<div id="nodevices" class="hide">
		<div class="alert alert-block">
			<h4 class="alert-heading"><?php echo _('No devices'); ?></h4><br>
			<p>
				<?php echo _('Devices are used to configure and prepare the communication with different metering units.'); ?>
				<br><br>
				<?php echo _('A device configures and prepares inputs, feeds possible device channels, representing e.g. different registers of defined metering units (see the channels tab).'); ?>
				<br>
				<?php echo _('You may want the next link as a guide for generating your request: '); ?><a href="api"><?php echo _('Device API helper'); ?></a>
			</p>
		</div>
	</div>

	<div id="bottomtoolbar"><hr>
	<button id="showchannels" class="btn btn-small" onclick="location.href = '<?php echo $path; ?>channel/view'"><i class="icon-wrench" ></i>&nbsp;<?php echo _('Channels'); ?></button>
	<button id="newdevice" class="btn btn-small" >&nbsp;<i class="icon-plus-sign" ></i>&nbsp;<?php echo _('New device'); ?></button>
	</div>
	
	<div id="device-loader" class="ajax-loader"></div>
</div>

<?php require "Modules/device/Views/dialog/view.php"; ?>
<?php require "Modules/channel/Views/dialog/view.php"; ?>

<script>
	var path = "<?php echo $path; ?>";
	var templates = <?php echo json_encode($templates); ?>;
	
	// Extend table library field types
	for (z in muctablefields) table.fieldtypes[z] = muctablefields[z];
	for (z in customtablefields) table.fieldtypes[z] = customtablefields[z];
	table.element = "#table";
	table.groupprefix = "Driver ";
	table.groupby = 'driver';
	table.groupfields = {
		'dummy-4':{'title':'', 'type':"blank"},
		'dummy-5':{'title':'', 'type':"blank"},
		'channels':{'title':'<?php echo _("Channels"); ?>','type':"group-channellist"},
		'dummy-7':{'title':'', 'type':"blank"},
		'time':{'title':'<?php echo _('Updated'); ?>', 'type':"group-updated"},
		'state':{'title':'<?php echo _('State'); ?>', 'type':"group-state"},
		'dummy-10':{'title':'', 'type':"blank"},
		'dummy-11':{'title':'', 'type':"blank"},
		'dummy-12':{'title':'', 'type':"blank"}
// 		'dummy-13':{'title':'', 'type':"blank"}
	}
	
	table.deletedata = false;
	table.fields = {
		'disabled':{'title':'', 'type':"icondisable"},
		'driver':{'title':'<?php echo _("Driver"); ?>','type':"fixed"},
		'nodeid':{'title':'<?php echo _("Node"); ?>','type':"text"},
		'name':{'title':'<?php echo _("Name"); ?>','type':"text"},
		'description':{'title':'<?php echo _('Location'); ?>','type':"text"},
		'channels':{'title':'<?php echo _("Channels"); ?>','type':"channellist"},
		'devicekey':{'title':'<?php echo _('Device access key'); ?>','type':"text"},
		'time':{'title':'<?php echo _("Updated"); ?>', 'type':"updated"},
		'state':{'title':'<?php echo _("State"); ?>', 'type':"state"},
		// Actions
		'channel-action':{'title':'', 'type':"iconmuc", 'icon':'icon-plus-sign'},
// 	    'edit-action':{'title':'', 'type':"edit"},
		'delete-action':{'title':'', 'type':"delete"},
		'config-action':{'title':'', 'type':"iconconfig", 'icon':'icon-wrench'}
	}

	update();

	channel.states = null;
	device.states = null;
	function update(){
		var requestTime = (new Date()).getTime();

		$.ajax({ url: path+"channel/states.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
			// Set the channel states for the labels to be colored correctly
			channel.states = data;
		}});

		$.ajax({ url: path+"device/states.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
			updateStates(data);
		}});

		$.ajax({ url: path+"device/list.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
			table.timeServerLocalOffset = requestTime-(new Date(xhr.getResponseHeader('Date'))).getTime(); // Offset in ms from local to server time
			table.data = data;

			updateData();
			if (table.data.length != 0) {
				$("#nodevices").hide();
				$("#localheading").show();
			} else {
				$("#nodevices").show();
				$("#localheading").hide();
			}
			$('#device-loader').hide();
		}});
	}

	function updateStates(states){
		// Only draw the table new, if a state got updated to avoid unnecessary redraws
		var updated = false;
		for (s in states) {
			for (d in table.data) {
				if (states[s]['mucid'] == table.data[d]['mucid'] && states[s]['name'] == table.data[d]['name']) {
					if (table.data[d]['state'] == null || states[s]['state'] !== table.data[d]['state']) {
						table.data[d]['state'] = states[s]['state'];
						updated = true;
					}
				}
			}
		}
		
		if (updated) table.draw();
		device.states = states;
	}

	function updateData(){
		for (d in table.data) {
			if (device.states != null) {
				for (s in device.states) {
					if (device.states[s]['mucid'] == table.data[d]['mucid'] && device.states[s]['name'] == table.data[d]['name']) {
						table.data[d]['state'] = device.states[s]['state'];
					}
				}
			}
			if (table.data[d]['state'] == null) {
				table.data[d]['state'] = '';
			}
		}
		table.draw();
	}

	var updater;
	function updaterStart(func, interval){
		clearInterval(updater);
		updater = null;
		if (interval > 0) updater = setInterval(func, interval);
	}
	updaterStart(update, 10000);

	$("#table").bind("onEdit", function(e){
		updaterStart(update, 0);
	});

	$("#table").bind("onDisable", function(e,id,row,disable){
		$('#device-loader').show();

		// Get device of clicked row
		var localdevice = table.data[row];
		var result = device.update(localdevice['mucid'], localdevice['id'], localdevice);
		update();

		if (!result.success) {
			alert('Unable to update device:\n'+result.message);
			return false;
		}
	});

	$("#table").bind("onResume", function(e){
		updaterStart(update, 10000);
	});

	$("#table").bind("onDelete", function(e,id,row){
		// Get device of clicked row
		var device = table.data[row];
		
		device_dialog.loadDelete(device, row);
	});

	$("#table").on('click', '.icon-wrench', function(){
		// Get device of clicked row
		var device = table.data[$(this).attr('row')];
		
		device_dialog.loadConfig(device, false);
	});

	$("#table").on('click', '.icon-plus-sign', function() {
		// Do not open dialog if the icon-plus-sign is used on a group header
		if(!$(this).attr('group')) {
			// Get device of clicked row
			var device = table.data[$(this).attr('row')];
			
			channel_dialog.loadAdd(device);
		}
	});

	$("#table").on('click', '.channel-label', function() {
		$('#device-loader').show();
		
		// Get the ids of the clicked lable
		var mucid = $(this).attr('mucid');
		var channelid = $(this).attr('channelid');
		
		var localchannel = channel.get(mucid, channelid);
		channel_dialog.loadConfig(localchannel, true);
		
		$('#device-loader').hide();
	});

	$("#newdevice").on('click', function (){
		device_dialog.loadAdd(null);
	});
</script>