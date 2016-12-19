<?php
	global $path;
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
	#table td:nth-of-type(2) { width:10%; }
	#table td:nth-of-type(3) { width:5%; }
	#table td:nth-of-type(4) { width:10%; }
	#table td:nth-of-type(5) { width:10%; }
	#table td:nth-of-type(6), th:nth-of-type(6) { font-weight:normal; text-align: left; }
	#table td:nth-of-type(7), th:nth-of-type(7) { text-align: right; }
	#table td:nth-of-type(8), th:nth-of-type(8) { text-align: right; }
	#table th[fieldg="processList"] { font-weight:normal; }
	#table th[fieldg="time"] { font-weight:normal; text-align: right; }
	#table th[fieldg="state"] { font-weight:normal; text-align: right; }
	#table td:nth-of-type(9) { width:14px; text-align: center; }
	#table td:nth-of-type(10) { width:14px; text-align: center; }
</style>

<div>
	<div id="apihelphead" style="float:right;"><a href="api"><?php echo _('Channels API Help'); ?></a></div>
	<div id="localheading"><h2><?php echo _('Channels'); ?></h2></div>

	<div id="table"><div align='center'></div></div>

	<div id="nochannels" class="hide">
		<div class="alert alert-block">
			<h4 class="alert-heading"><?php echo _('No channels'); ?></h4><br>
			<p>
				<?php echo _('Channels are used to configure e.g. different registers of metering units.'); ?>
				<br><br>
				<?php echo _('Several channels may be registered for a device, imlementing the communication to corresponding metering units (see the devices tab).'); ?>
				<br>
				<?php echo _('You may want the next link as a guide for generating your request: '); ?><a href="api"><?php echo _('Channel API helper'); ?></a>
			</p>
		</div>
	</div>

	<div id="bottomtoolbar"><hr>
		<button id="return" class="btn btn-primary btn-small"><i class="icon-chevron-left icon-white" ></i>&nbsp;<?php echo _('Return'); ?></button>
		<button id="newchannel" class="btn btn-small" >&nbsp;<i class="icon-plus-sign" ></i>&nbsp;<?php echo _('New channel'); ?></button>
	</div>
	
	<div id="channel-loader" class="ajax-loader"></div>
</div>

<?php require "Modules/channel/Views/dialog/view.php"; ?>
<?php require "Modules/process/Views/process_ui.php"; ?>

<script>
	var path = "<?php echo $path; ?>";
	
	// Extend table library field types
	for (z in muctablefields) table.fieldtypes[z] = muctablefields[z];
	for (z in customtablefields) table.fieldtypes[z] = customtablefields[z];
	table.element = "#table";
	table.groupprefix = "Device ";
	table.groupby = 'device';
	table.groupfields = {
		'dummy-5':{'title':'', 'type':"blank"},
		'dummy-6':{'title':'', 'type':"blank"},
		'processList':{'title':'<?php echo _("Process list"); ?>','type':"group-processlist"},
		'time':{'title':"<?php echo _('Updated'); ?>", 'type':"group-updated"},
		'state':{'title':'<?php echo _('State'); ?>', 'type':"group-state"},
		'dummy-9':{'title':'', 'type':"blank"},
		'dummy-10':{'title':'', 'type':"blank"}
// 		'dummy-11':{'title':'', 'type':"blank"}
	}
	
	table.deletedata = false;
	table.fields = {
		'disabled':{'title':'', 'type':"icondisable"},
		'device':{'title':'<?php echo _("Device"); ?>','type':"text"},
		'nodeid':{'title':'<?php echo _("Node"); ?>','type':"text"},
		'name':{'title':'<?php echo _("Key"); ?>','type':"text"},
		'description':{'title':'<?php echo _('Name'); ?>','type':"text"},
		'processList':{'title':'<?php echo _("Process list"); ?>','type':"processlist"},
		'time':{'title':'<?php echo _("Updated"); ?>', 'type':"updated"},
		'state':{'title':'<?php echo _("State"); ?>', 'type':"state"},
		// Actions
// 		'edit-action':{'title':'', 'type':"edit"},
		'delete-action':{'title':'', 'type':"delete"},
		'config-action':{'title':'', 'type':"iconconfig", 'icon':'icon-wrench'}
	}

	update();

	channel.states = null;
	function update(){
		var requestTime = (new Date()).getTime();

		$.ajax({ url: path+"channel/states.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
			updateStates(data);
		}});

		$.ajax({ url: path+"channel/list.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
			table.timeServerLocalOffset = requestTime-(new Date(xhr.getResponseHeader('Date'))).getTime(); // Offset in ms from local to server time
			table.data = data;

			updateData();
			if (table.data.length != 0) {
				$("#nochannels").hide();
				$("#localheading").show();
			} else {
				$("#nochannels").show();
				$("#localheading").hide();
			}
			$('#channel-loader').hide();
		}});
	}

	function updateStates(states){
		// Only draw the table new, if a state got updated to avoid unnecessary redraws
		var updated = false;
		for (s in states) {
			for (c in table.data) {
				if (states[s]['mucid'] == table.data[c]['mucid'] && states[s]['name'] == table.data[c]['name']) {
					if (table.data[c]['state'] == null || states[s]['state'] !== table.data[c]['state']) {
						table.data[c]['state'] = states[s]['state'];
						updated = true;
					}
				}
			}
		}
		
		if (updated) table.draw();
		channel.states = states;
	}

	function updateData(){
		for (d in table.data) {
			if (channel.states != null) {
				for (s in channel.states) {
					if (channel.states[s]['mucid'] == table.data[d]['mucid'] && channel.states[s]['name'] == table.data[d]['name']) {
						table.data[d]['state'] = channel.states[s]['state'];
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
	function updaterStart(func, interval)
	{
		clearInterval(updater);
		updater = null;
		if (interval > 0) updater = setInterval(func, interval);
	}
	updaterStart(update, 10000);

	// Process list UI js
	processlist_ui.init(0); // Set input context

	$("#table").bind("onEdit", function(e){
		updaterStart(update, 0);
	});

	$("#table").bind("onDisable", function(e,id,row,disable){
		$('#channel-loader').show();

		// Get device of clicked row
		var localchannel = table.data[row];
		var result = channel.update(localchannel['mucid'], localchannel['name'], localchannel);
		update();

		if (!result.success) {
			alert('Unable to update channel:\n'+result.message);
			return false;
		}
	});

	$("#table").bind("onResume", function(e){
		updaterStart(update, 10000);
	});

	$("#table").bind("onDelete", function(e,id,row){
		// Get channel of clicked row
		var channel = table.data[row];
		
		channel_dialog.loadDelete(channel, row);
	});

	$("#table").on('click', '.icon-wrench', function(){
		// Get driver of clicked row
		var channel = table.data[$(this).attr('row')];

		channel_dialog.loadConfig(channel, false);
	});

	$("#newchannel").on('click', function (){
		channel_dialog.loadAdd(null);
	});

	$('#return').on('click', function() {
		parent.history.back();
		return false;
	});
</script>