<?php
	global $path;
?>

<link href="<?php echo $path; ?>Modules/muc/Lib/style.css" rel="stylesheet">
<script type="text/javascript" src="<?php echo $path; ?>Modules/muc/Views/muc.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>

<style>
	#table input[type="text"] {
		width: 88%;
	}
	#table td:nth-of-type(1) { width:20%;}
	#table td:nth-of-type(2) { width:10%;}
	#table td:nth-of-type(4) { width:14px; text-align: center; }
	#table td:nth-of-type(5) { width:14px; text-align: center; }
</style>

<div>
	<div id="apihelphead" style="float:right;"><a href="api"><?php echo _('MUC API Help'); ?></a></div>
	<div id="localheading"><h2><?php echo _('Multi Utility Communication controller'); ?></h2></div>
	
	<div id="table"><div align='center'></div></div>
	
	<div id="nomucs" class="alert alert-block hide">
		<h4 class="alert-heading"><?php echo _('No Multi Utility Communication controller configured'); ?></h4>
			<p>
				<?php echo _('Multi Utility Communication (MUC) controller handle the communication protocols to a variety of devices and are the main entry point to configure metering units.'); ?>
				<br><br>
				<?php echo _('A MUC controller registers several drivers (see the drivers tab) and is needed to configure the communication protocol they implement.'); ?>
				<br>
				<?php echo _('Several MUC controllers may be added, but it is recommended to use the local platform, if geographically possible.'); ?>
				<br>
				<?php echo _('You may want the next link as a guide for generating your request: '); ?><a href="api"><?php echo _('MUC API helper'); ?></a>
			</p>
	</div>
	
	<div id="bottomtoolbar"><hr>
		<button id="newmuc" class="btn btn-small"><i class="icon-plus-sign" ></i>&nbsp;<?php echo _('New controller'); ?></button>
	</div>
	
	<div id="muc-loader" class="ajax-loader"></div>
</div>

<?php require "Modules/muc/Views/dialog/view.php"; ?>

<script>
	var path = "<?php echo $path; ?>";
	
	// Extend table library field types
	for (z in customtablefields) table.fieldtypes[z] = customtablefields[z];
	table.element = "#table";
	table.deletedata = false;
	table.fields = {
		'address':{'title':'<?php echo _("Address"); ?>','type':"text"},
		'description':{'title':'<?php echo _('Location'); ?>','type':"text"},
		'password':{'title':'<?php echo _('Password'); ?>','type':"text"},
		// Actions
		'edit-action':{'title':'', 'type':"edit"},
		'delete-action':{'title':'', 'type':"delete"}
	}

	update();

	function update() {
		var requestTime = (new Date()).getTime();
		$.ajax({ url: path+"muc/list.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
			table.timeServerLocalOffset = requestTime-(new Date(xhr.getResponseHeader('Date'))).getTime(); // Offset in ms from local to server time
			table.data = data;

			table.draw();
			if (table.data.length == 0) {
				$("#nomucs").show();
				$("#localheading").hide();
			} else {
				$("#nomucs").hide();
				$("#localheading").show();
			}
			$('#muc-loader').hide();
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

	$("#table").bind("onEdit", function(e) {
		updaterStart(update, 0);
	});

	$("#table").bind("onSave", function(e,id,fields_to_update) {
		$('#muc-loader').show();
		
		var result = muc.set(id,fields_to_update);
		update();
		
		$('#muc-loader').hide();

		if (!result.success) {
			alert('Unable to update muc:\n'+result.message);
			return false;
		}
	});

	$("#table").bind("onResume", function(e) {
		updaterStart(update, 10000);
	});

	$("#table").bind("onDelete", function(e,id,row) {
		$('#muc-loader').show();
		muc_dialog.loadDelete(id, row);
		$('#muc-loader').hide();
	});

	$('#newmuc').on('click', function() {
		muc_dialog.loadAdd();
	});
</script>
