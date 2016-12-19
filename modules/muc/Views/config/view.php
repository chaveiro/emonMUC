<style>
	#address-table td:nth-of-type(1), #settings-table td:nth-of-type(1), #config-table td:nth-of-type(1) { width:20%;}
	#address-table td:nth-of-type(3), #settings-table td:nth-of-type(3), #config-table td:nth-of-type(3) { text-align: right; }
	#address-table td:nth-of-type(4), #settings-table td:nth-of-type(4), #config-table td:nth-of-type(4) { width:14px; text-align: center; }
	#address-table td:nth-of-type(5), #settings-table td:nth-of-type(5), #config-table td:nth-of-type(5) { width:14px; text-align: center; }
</style>

<div id="config">
	<table id="address-table" class="table table-hover">
		<tr>
			<th id="address-header" colspan="5">
				<i class="toggle-header icon-plus-sign" group="address" style="cursor:pointer"></i>
				<a class="toggle-header" group="address" style="cursor:pointer"><?php echo _(' Address '); ?></a>
			</th>
		</tr>
		<tr id="address-parameter-header" style="display:none" colspan="5">
			<th><?php echo _('Parameter'); ?></th>
			<th><?php echo _('Value'); ?></th>
			<th></th>
			<th colspan="2"><?php echo _('Actions'); ?></th>
		</tr>
		<tbody id="address-parameter-table"></tbody>
	</table>
	<div id="address-parameter-none" class="alert" style="display:none"><?php echo _('You have no parameter configured'); ?></div>
	
	<table id="settings-table" class="table table-hover">
		<tr>
			<th id="settings-header" colspan="5">
				<i class="toggle-header icon-plus-sign" group="settings" style="cursor:pointer"></i>
				<a class="toggle-header" group="settings" style="cursor:pointer"><?php echo _(' Settings '); ?></a>
			</th>
		</tr>
		<tr id="settings-parameter-header" style="display:none" colspan="5">
			<th><?php echo _('Parameter'); ?></th>
			<th><?php echo _('Value'); ?></th>
			<th></th>
			<th colspan="2"><?php echo _('Actions'); ?></th>
		</tr>
		<tbody id="settings-parameter-table"></tbody>
	</table>
	<div id="settings-parameter-none" class="alert" style="display:none"><?php echo _('You have no parameter configured'); ?></div>
	
	<table id="config-table" class="table table-hover">
		<tr>
			<th id="config-header" colspan="5">
				<i class="toggle-header icon-plus-sign" group="config" style="cursor:pointer"></i>
				<a class="toggle-header" group="config" style="cursor:pointer"><?php echo _(' Settings '); ?></a>
			</th>
		</tr>
		<tr id="config-parameter-header" style="display:none" colspan="5">
			<th><?php echo _('Parameter'); ?></th>
			<th><?php echo _('Value'); ?></th>
			<th></th>
			<th colspan="2"><?php echo _('Actions'); ?></th>
		</tr>
		<tbody id="config-parameter-table"></tbody>
	</table>
	<div id="config-parameter-none" class="alert" style="display:none"><?php echo _('You have no parameter configured'); ?></div>
	
	<div id="parameter-panel" style="display:none">
		<h4 id="parameter-header-add" style="display:none"><?php echo _('Add parameter'); ?>:</h4>
		<h4 id="parameter-header-edit" style="display:none"><?php echo _('Edit parameter'); ?>:</h4>
		
		<select id="parameter-select" class="input-large" disabled></select>
		
		<span id="parameter-text" style="display:none">
			<div class="input-prepend">
				<span class="add-on text-select-label">Text</span>
				<input type="text" id="text-input" class="input-large" placeholder="Type text..." />
			</div>
		</span>
		
		<span id="parameter-time" style="display:none">
			<div class="input-prepend">
				<span class="add-on text-select-label">Time</span>
				<select id="time-input" class="input-small">
					<option selected hidden='true' value=''><?php echo _('Select'); ?></option>
					<option value=0><?php echo _('None'); ?></option>
					<option value=0.1>0.1<?php echo _('s'); ?></option>
					<option value=0.5>0.5<?php echo _('s'); ?></option>
					<option value=1>1<?php echo _('s'); ?></option>
					<option value=5>5<?php echo _('s'); ?></option>
					<option value=10>10<?php echo _('s'); ?></option>
					<option value=15>15<?php echo _('s'); ?></option>
					<option value=20>20<?php echo _('s'); ?></option>
					<option value=30>30<?php echo _('s'); ?></option>
					<option value=60>60<?php echo _('s'); ?></option>
					<option value=120>2<?php echo _('m'); ?></option>
					<option value=300>5<?php echo _('m'); ?></option>
					<option value=600>10<?php echo _('m'); ?></option>
					<option value=900>15<?php echo _('m'); ?></option>
					<option value=1200>20<?php echo _('m'); ?></option>
					<option value=1800>30<?php echo _('m'); ?></option>
					<option value=3600>1<?php echo _('h'); ?></option>
					<option value=86400>1<?php echo _('d'); ?></option>
				</select>
			</div>
		</span>
		
		<span id="parameter-value" style="display:none">
			<div class="input-prepend">
				<span class="add-on value-select-label">Value</span>
				<input type="text" id="value-input" class="input-medium" placeholder="Type value..." />
			</div>
		</span>
		
		<span id="parameter-boolean" style="display:none">
			<div class="input-prepend">
				<span class="add-on value-select-label">Enabled</span>
				<button type="button" id="boolean-input" class="btn" style="border-radius: 4px;"><?php echo _('False'); ?></button>
			</div>
		</span>
		
		<span id="parameter-btn-add">
			<div class="input-prepend">
				<button id="parameter-add" class="btn btn-info" style="border-radius: 4px;" disabled><?php echo _('Add'); ?></button>
			</div>
		</span>
		
		<span id="parameter-btn-edit" style="display:none">
			<div class="input-prepend">
				<button id="parameter-edit" class="btn btn-info" style="border-radius: 4px;"><?php echo _('Edit'); ?></button>
				<button id="parameter-cancel" class="btn" style="border-radius: 4px;"><?php echo _('Cancel'); ?></button>
			</div>
		</span>
		
		<div id="parameter-description" class="alert alert-info" style="display:none"></div>
	</div>
</div>