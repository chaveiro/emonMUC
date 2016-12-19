<?php
	global $path;
?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/driver/Views/dialog/dialog.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/muc/Views/config/config.js"></script>

<div id="driverConfigModal" class="modal hide keyboard modal-adjust" tabindex="-1" role="dialog" aria-labelledby="driverConfigModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="driverConfigModalLabel"></h3>
	</div>
	<div id="driverConfigBody" class="modal-body">
		<div id="driverconfig-muc" style="display:none">
			<label>Controller to register driver for: </label>
			<select id="driverconfig-muc-select" class="input-large"></select>
		</div>
		
		<div class="modal-container">
			<h4 id="driverconfig-header"><?php echo _('Driver'); ?></h4>
			
			<select id="driverconfig-select" class="input-large" style="display:none" disabled></select>
			<p id="driverconfig-description"></p>
			
			<div class="modal-container">
				<div id="driverconfig-container"></div>
				<div id="driverconfig-container-overlay" class="modal-overlay" style="display:none"></div>
			</div>
			
			<div id="driverconfig-overlay" class="modal-overlay"></div>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
		<button id="driverconfig-delete" class="btn btn-info" style="display:none;"><?php echo _('Delete'); ?></button>
		<button id="driverconfig-save" class="btn btn-primary"><?php echo _('Save'); ?></button>
	</div>
	<div id="driverconfig-loader" class="ajax-loader" style="display:none"></div>
</div>

<div id="driverDeleteModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="driverDeleteModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="driverDeleteModalLabel"><?php echo _('Delete driver'); ?></h3>
	</div>
	<div class="modal-body">
		<p><?php echo _('Deleting a driver is permanent.'); ?>
			<br><br>
			<?php echo _('If this driver is enabled and has devices configured, they will no longer be sampled or listened to.'); ?>
			<br><br>
			<?php echo _('All corresponding devices and their configuration will be removed, while feeds and all historic data is kept. To remove them, delete them manually afterwards.'); ?>
			<br><br>
			<?php echo _('Are you sure you want to delete?'); ?>
		</p>
		<div id="driverdelete-loader" class="ajax-loader" style="display:none;"></div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
		<button id="driverdelete-confirm" class="btn btn-primary"><?php echo _('Delete permanently'); ?></button>
	</div>
</div>

<script>
	$(window).resize(function(){
		driver_dialog.adjustModal()
	});
	
	$('#driverconfig-container').load('<?php echo $path; ?>Modules/muc/Views/config/view.php');
</script>