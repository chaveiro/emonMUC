<?php
	global $path;
?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/channel/Views/dialog/dialog.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/muc/Views/config/config.js"></script>

<style>
	#channelconfig-channel-table td:nth-of-type(1) { width:10%; }
	#channelconfig-channel-table td:nth-of-type(2) { width:5%; }
	#channelconfig-channel-table td:nth-of-type(3) { width:10%; }
</style>

<div id="channelConfigModal" class="modal hide keyboard modal-adjust" tabindex="-1" role="dialog" aria-labelledby="channelConfigModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="channelConfigModalLabel"></h3>
	</div>
	<div id="channelConfigBody" class="modal-body">
		<table id="channelconfig-channel-table" class="table">
			<tr>
				<th><?php echo _('Device'); ?></th>
				<th><?php echo _('Node'); ?></th>
				<th><?php echo _('Key'); ?></th>
				<th><?php echo _('Name'); ?></th>
			</tr>
			<tr>
				<td>
					<label id="channelconfig-device" style="height:auto; vertical-align: middle; display:none;"></label>
					<select id="channelconfig-device-select" class="input-large"></select>
				</td>
				<td><label id="channelconfig-node" style="height:auto; vertical-align: middle;"></label></td>
				<td><input id="channelconfig-name" class="input-medium" type="text"></td>
				<td><input id="channelconfig-description" class="input-large" type="text"></td>
			</tr>
		</table>
		
		<p id="channelconfig-info" style="display:none;"></p>
		
		<div class="modal-container">
			<div id="channelconfig-container"></div>
			<div id="channelconfig-overlay" class="modal-overlay"></div>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
		<button id="channelconfig-delete" class="btn btn-info" style="display:none;"><?php echo _('Delete'); ?></button>
		<button id="channelconfig-save" class="btn btn-primary"><?php echo _('Save'); ?></button>
	</div>
	<div id="channelconfig-loader" class="ajax-loader" style="display:none"></div>
</div>

<div id="channelDeleteModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="channelDeleteModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="channelDeleteModalLabel"><?php echo _('Delete channel'); ?></h3>
	</div>
	<div class="modal-body">
		<p><?php echo _('Deleting a channel is permanent.'); ?>
		<br><br>
		<?php echo _('If the representing channel is active and data gets written to an input, it will no longer be able to post data.'); ?>
		<br><br>
		<?php echo _('The corresponding input and its configuration will be removed, while feeds and all historic data is kept. To remove them, delete them manually afterwards.'); ?>
		<br><br>
		<?php echo _('Are you sure you want to delete?'); ?>
		</p>
		<div id="channeldelete-loader" class="ajax-loader" style="display:none;"></div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
		<button id="channeldelete-confirm" class="btn btn-primary"><?php echo _('Delete permanently'); ?></button>
	</div>
</div>

<script>
	$(window).resize(function(){
		channel_dialog.adjustModal()
	});
	
	$('#channelconfig-container').load('<?php echo $path; ?>Modules/muc/Views/config/view.php');
</script>