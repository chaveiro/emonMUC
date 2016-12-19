<?php
	global $path;
?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/muc/Views/dialog/dialog.js"></script>

<div id="mucAddModal" class="modal hide keyboard" tabindex="-1" role="dialog" aria-labelledby="mucAddModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="mucAddModalLabel"><?php echo _('New Multi Utility Communication controller'); ?></h3>
	</div>
	<div class="modal-body">
		<p style="margin-bottom: 18px;"><em>Multi Utility Communications (MUC) controller handle the communication protocols to a variety of devices and are the main entry point to configure metering units.</br></br> 
			A MUC controller registers several drivers (see the drivers tab) and is needed to configure their parameters.</br> 
			Several MUC controllers may be added and configured, but it is recommended to use the local platform, if geographically possible.</em></p>
		
		<label>Address: </label>
		<input id="mucadd-address" type="text" value="https://localhost">
		<label>Location description: </label>
		<input id="mucadd-description" type="text" value="Local">
		
		<div id="mucadd-loader" class="ajax-loader" style="display:none;"></div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
		<button id="mucadd-save" class="btn btn-primary"><?php echo _('Save'); ?></button>
	</div>
</div>

<div id="mucDeleteModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="mucDeleteModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="mucDeleteModalLabel"><?php echo _('Delete Multi Utility Communication controller'); ?></h3>
	</div>
	<div class="modal-body">
		<p><?php echo _('Deleting a Multi Utility Communication controller is permanent.'); ?>
			<br><br>
			<?php echo _('If this MUC controller is active and is registered, it will no longer be able to retrieve the configuration.'); ?>
			<br><br>
			<?php echo _('All corresponding drivers and their configuration will be removed, while feeds and all historic data is kept. To remove them, delete them manually afterwards.'); ?>
			<br><br>
			<?php echo _('Are you sure you want to delete?'); ?>
		</p>
		<div id="mucdelete-loader" class="ajax-loader" style="display:none;"></div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
		<button id="mucdelete-confirm" class="btn btn-primary"><?php echo _('Delete permanently'); ?></button>
	</div>
</div>