<?php
	global $path;
?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/device/Views/dialog/dialog.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/muc/Views/config/config.js"></script>

<style>
	#deviceconfig-device-table td:nth-of-type(1) { width:10%; }
	#deviceconfig-device-table td:nth-of-type(2) { width:5%; }
	#deviceconfig-device-table td:nth-of-type(3) { width:10%; }
</style>

<div id="deviceConfigModal" class="modal hide keyboard modal-adjust" tabindex="-1" role="dialog" aria-labelledby="deviceConfigModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="deviceConfigModalLabel"></h3>
	</div>
	<div id="deviceConfigBody" class="modal-body">
		<table id="deviceconfig-device-table" class="table">
			<tr>
				<th><?php echo _('Driver'); ?></th>
				<th><?php echo _('Node'); ?></th>
				<th><?php echo _('Name'); ?></th>
				<th><?php echo _('Location description'); ?></th>
			</tr>
			<tr>
				<td>
					<label id="deviceconfig-driver" style="height:auto; vertical-align: middle; display:none;"></label>
					<select id="deviceconfig-driver-select" class="input-large"></select>
				</td>
				<td><input id="deviceconfig-node" class="input-small" type="text"></td>
				<td><input id="deviceconfig-name" class="input-medium" type="text"></td>
				<td><input id="deviceconfig-description" class="input-large" type="text" style="width:97%;"></td>
			</tr>
		</table>
		
		<p id="deviceconfig-info" style="display:none;"></p>
		
		<div class="modal-container">
			<table id="deviceconfig-template-table" class="table table-hover">
				<tr>
					<th id="deviceconfig-template-header">
						<i class="toggle-header icon-plus-sign" group="template" style="cursor:pointer"></i>
						<a class="toggle-header" group="template" style="cursor:pointer"><?php echo _(' Template '); ?></a>
					</th>
				</tr>
			</table>
			<div id="deviceconfig-template" style="display:none;">
				<span>
					<div class="input-prepend">
						<select id="deviceconfig-template-select" class="input-large"></select>
					</div>
					<div class="info">
						<span class="info-text">
							<em>
								<?php echo _('Default channels, inputs and associated feeds will be automaticaly created together with the device, according to the selected template.'); ?><br><br>
								<?php echo _('Initializing a device usualy should only be done once on installation.'); ?><br>
								<?php echo _('If the template was already applied, only missing channels, inputs and feeds will be created.'); ?>
							</em>
						</span>
					</div>
				</span>
				<p id="deviceconfig-template-description" style="display:none;"></p>
			</div>
			<div id="deviceconfig-template-overlay" class="modal-overlay"></div>
		</div>
		<div class="modal-container">
			<div id="deviceconfig-container" style="display:none;"></div>
			
			<div id="deviceconfig-config-overlay" class="modal-overlay"></div>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
		<button id="deviceconfig-delete" class="btn btn-info" style="display:none;"><?php echo _('Delete'); ?></button>
		<button id="deviceconfig-save" class="btn btn-primary"><?php echo _('Save'); ?></button>
	</div>
	<div id="deviceconfig-loader" class="ajax-loader" style="display:none"></div>
</div>

<div id="deviceDeleteModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="deviceDeleteModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="deviceDeleteModalLabel"><?php echo _('Delete device'); ?></h3>
	</div>
	<div class="modal-body">
		<p><?php echo _('Deleting a device is permanent.'); ?>
		<br><br>
		<?php echo _('If the representing device is active and is using a device key, it will no longer be able to post data.'); ?>
		<br><br>
		<?php echo _('All corresponding channels and their configuration will be removed, while inputs, feeds and all historic data is kept. To remove them, delete them manually afterwards.'); ?>
		<br><br>
		<?php echo _('Are you sure you want to delete?'); ?>
		</p>
		<div id="devicedelete-loader" class="ajax-loader" style="display:none;"></div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
		<button id="devicedelete-confirm" class="btn btn-primary"><?php echo _('Delete permanently'); ?></button>
	</div>
</div>

<script>
	$(window).resize(function(){
		device_dialog.adjustModal()
	});
	
	$('#deviceconfig-container').load('<?php echo $path; ?>Modules/muc/Views/config/view.php');
</script>