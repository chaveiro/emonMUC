var config =
{
	'container': null,
	'groupshow': {},
	'context': '',

	'parameters': null,
	'info': null,


	'init':function(parent, path, context, disableAddress, disableSettings) {
		if (!parent) {
			alert('Config has to be loaded to valid container');
			return false;
		}
		this.container = parent;
		this.context = context;
		
		this.parameters = null;
		this.info = null;
		
		// TODO: Dynamically add parameter tables to container
		if (disableAddress) {
			$("#address-table", this.container).hide();
		}
		else {
			$("#address-table", this.container).show();
		}
		if (disableSettings) {
			$("#settings-table", this.container).hide();
		}
		else {
			$("#settings-table", this.container).show();
		}

		this.groupshow['address'] = false;
		this.groupshow['settings'] = false;
		this.groupshow['config'] = false;
		this.draw();
	},

	'load':function(parameters, info) {

		this.parameters = parameters;
		this.info = info;

		// Parse address string depending on syntax info
		if (typeof info['address'] !== 'undefined') {
			this.loadParameters('address');
			this.groupshow['address'] = true;
		}
		// Parse settings string depending on syntax info
		if (typeof info.settings !== 'undefined') {
			this.loadParameters('settings');
			this.groupshow['settings'] = true;
		}
		if (typeof info.config !== 'undefined') {
			this.groupshow['config'] = true;
		}
		
		this.draw();
	},
	
	'loadParameters':function(group) {
		
		if (typeof this.parameters[group] !== 'undefined') {
			var parlist = {};
			
			var pararr = this.parameters[group].split(this.info[group+'Syntax']['listDelimiter']);
			for (var p = 0, i = 0; i < this.info[group].length && p < pararr.length; i++) {
				parinfo = this.info[group][i];

				if (typeof this.info[group+'Syntax']['keyValueSeparator'] !== 'undefined') {
					var keyValue = pararr[p].split(this.info[group+'Syntax']['keyValueSeparator']);
					if (parinfo.id === keyValue[0]) {
						parlist[parinfo.id] = keyValue[1];
						p++;
					}
				}
				else {
					if (parinfo['required'] || pararr.length === this.info[group].length) {
						parlist[parinfo.id] = pararr[p];
						p++;
					}
				}
			}
			this.parameters[group] = parlist;
		}
	},
	
	'draw':function() {
		
		var selectAddress = config.drawGroup('address', config.context + ' address');
		var selectSettings = config.drawGroup('settings', config.context + ' settings');
		var selectConfig = config.drawGroup('config', 'Configuration');
		
		if (config.groupshow['address'] || config.groupshow['settings'] || config.groupshow['config']) {
			$("#parameter-panel", config.container).show();
			
			var selectParameter = '';
			if (selectAddress.length > 0) {
				selectParameter += '<optgroup label="Address">' + selectAddress;
			}
			if (selectSettings.length > 0) {
				selectParameter += '<optgroup label="Settings">' + selectSettings;
			}
			if (selectConfig.length > 0) {
				selectParameter += '<optgroup label="Configuration">' + selectConfig;
			}

			$("#parameter-header-add", config.container).show();
			$("#parameter-header-edit", config.container).hide();
			$('#parameter-btn-add', config.container).show();
			$('#parameter-btn-edit', config.container).hide();
			$('#parameter-add', config.container).prop('disabled', true);
			
			if (selectParameter.length > 0) {
				selectParameter += '<option hidden="true" value="">Select a parameter</option></optgroup>';
				
				$('#parameter-select', config.container).html(selectParameter).prop('disabled', false).val('').change();
			}
			else {
				$('#parameter-select', config.container).find('option').remove().end().prop('disabled', true);
				$('#parameter-text', config.container).hide();
				$('#parameter-time', config.container).hide();
				$('#parameter-value', config.container).hide();
				$('#parameter-boolean', config.container).hide();
				$('#parameter-description', config.container).html('').hide();
			}
		}
		else {
			$('#parameter-panel', config.container).hide();
		}
		
		// Initialize callbacks
		config.registerEvents();
	},
	
	'drawGroup':function(group, header) {

		var select = '';

		if (config.groupshow[group]) {
			$('#'+group+'-header', config.container).html(
					'<i class="toggle-header icon-minus-sign" group="'+group+'" style="cursor:pointer"></i>'+
					'<a class="toggle-header" group="'+group+'" style="cursor:pointer"> '+header+'</a>');

			$('#'+group+'-parameter-header', config.container).show();
			$('#'+group+'-parameter-table', config.container).show();

			var table='';
			var info = config.info[group];
			if (typeof info !== 'undefined') {
				
				for (var i = 0; i < info.length; i++) {
					var parameter = info[i];
					var id = parameter['id'];
					
					var name = parameter['name'];
					if (typeof name === 'undefined') {
						name = id;
					}
					if (typeof config.parameters[group] === 'undefined') {
						config.parameters[group] = {};
					}
					if (typeof config.parameters[group][id] === 'undefined' && parameter['required'] && parameter['default']) {
						config.setParameter(id, 'address', parameter['default']);
					}
					
					var row = config.drawParameter(id, name, group, config.parameters[group][id], 
							parameter['default'], parameter['type'], parameter['required']);
					
					if (row.length > 0) {
						table += row;
					}
					else {
						select += '<option value='+id+' group='+group+'>'+name+'</option>';
					}
				}
			}

			if (table.length > 0) {
				$('#'+group+'-parameter-header', config.container).show();
				$('#'+group+'-parameter-table', config.container).html(table).show();
				$('#'+group+'-parameter-none', config.container).hide();
			}
			else {
				$('#'+group+'-parameter-header', config.container).hide();
				$('#'+group+'-parameter-table', config.container).html('').show();
				$('#'+group+'-parameter-none', config.container).show();
			}
		}
		else {
			$('#'+group+'-header', config.container).html(
					'<i class="toggle-header icon-plus-sign" group="'+group+'" style="cursor:pointer"></i>'+
					'<a class="toggle-header" group="'+group+'" style="cursor:pointer"> '+header+'</a>');

			$('#'+group+'-parameter-header', config.container).hide();
			$('#'+group+'-parameter-table', config.container).hide();
			$('#'+group+'-parameter-none', config.container).hide();
		}
		
		return select;
	},
	
	'drawParameter':function(id, name, group, parValue, defaultValue, type, required) {
		
		var value = '';
		var comment = '';
		if (parValue) {
			value += parValue;

			// Check Parameter Type
			switch(type) {
				case 'time': // TIME
					var time = value/1000;
					if (time <= 60) {
						value = time+' second';
						if (time > 1) {
							value += 's';
						}
					}
					else {
						time = time/60;
						if (time < 60) {
							value = time+' minute';
							if (time > 1) {
								value += 's';
							}
						}
						else {
							time = time/60;
							if (time < 60) {
								value = time+' hour';
								if (time > 1) {
									value += 's';
								}
							}
							else {
								time = time/24;
								value = time+' day';
								if (time > 1) {
									value += 's';
								}
							}
						}
					}
					value = '<span>'+value+'</span>';
					break;
				case 'boolean': // BOOLEAN
					if (value == 'true') {
						value = '<span style="color:#5bb75b">Enabled</span>';
					}
					else {
						value = '<span style="color:#888">Disabled</span>';
					}
					break;
				default:
					break;
			}
			
			if (defaultValue && defaultValue == parValue) {
				value = '<span style="color:#006dcc"><i>Default: </i></span>'+value;
			}
		}
		if (required) {
			if (value.length === 0) {
				value = '<span style="color:#b94a48"><i>Empty</i></span>';
			}
			comment = '<span style="color:#888; font-size:12px"><em>required</em></span>';
		}

		var row = '';
		if (value.length > 0) {
			row += '<tr>';
			row += '<td>'+name+'</td><td>'+value+'</td><td>'+comment+'</td>';
		 
			// Edit and delete buttons (icon)
			row += '<td><a class="edit-parameter" title="Edit" parameterid='+id+' group='+group+'><i class="icon-pencil" style="cursor:pointer"></i></a></td>';
			if (required) {
				row += '<td><i class="icon-trash" style="cursor:pointer; opacity:0.33" disabled></i></td>';
			}
			else {
				row += '<td><a class="delete-parameter" title="Delete" parameterid='+id+' group='+group+'><i class="icon-trash" style="cursor:pointer"></i></a></td>';
			}
			row += '</tr>';
		}
		return row;
	},

	'registerEvents':function() {
		$('#config', config.container).off();

		// Event: minimise or maximise settings
		$('#config', config.container).on('click touchend', '.toggle-header', function(e) {
			e.stopPropagation();
			e.preventDefault();
			var $me=$(this);
			if ($me.data('clicked')) {
				$me.data('clicked', false); // reset
				if ($me.data('alreadyclickedTimeout')) clearTimeout($me.data('alreadyclickedTimeout')); // prevent this from happening

				// Do what needs to happen on double click. 
				var group = $(this).attr('group');
				var state = config.groupshow[group];
				config.groupshow[group] = !state;
				config.draw();
				
			}
			else {
				$me.data('clicked', true);
				var alreadyclickedTimeout=setTimeout(function() {
					$me.data('clicked', false); // reset when it happens
	
					// Do what needs to happen on single click. Use $me instead of $(this) because $(this) is  no longer the element
					var group = $me.attr('group');
					var state = config.groupshow[group];
					config.groupshow[group] = !state;
					config.draw();
				
				},250); // dblclick tolerance
				$me.data('alreadyclickedTimeout', alreadyclickedTimeout); // store this id to clear if necessary
			}
		});

		$('#config', config.container).on('click', '.edit-parameter', function(){

			var id = $(this).attr('parameterid');
			var group = $(this).attr('group');

			$('#parameter-header-add', config.container).hide();
			$('#parameter-header-edit', config.container).show();
			$('#parameter-btn-add', config.container).hide();
			$('#parameter-btn-edit', config.container).show();

			var name = config.getInfo(id, group)['name'];
			if (!name) {
				name = id;
			}
			
			$('#parameter-select', config.container).append('<option hidden="true" value="'+id+'" group="'+group+'">'+name+'</option>').val(id)
			$('#parameter-select', config.container).change(); // Force a refresh
			$('#parameter-select', config.container).prop('disabled', true);
		});

		$('#config', config.container).on('click', '.delete-parameter', function(){

			var id = $(this).attr('parameterid');
			var group = $(this).attr('group');
			delete config.parameters[group][id];

			config.draw();
		});

		$('#config #parameter-select', config.container).off('change').on('change', function() {

			$('#parameter-text', config.container).hide();
			$('#parameter-time', config.container).hide();
			$('#parameter-value', config.container).hide();
			$('#parameter-boolean', config.container).hide();
			$('#parameter-description', config.container).html('').hide();
			
			var parameterid = this.value;
			if (parameterid.length > 0) {
				var group = $('option:selected', this).attr('group');
				
				var parameter = config.getInfo(parameterid, group);
				if (parameter != null) {
					var value = config.getParameter(parameterid, group);
					
					if (value == null && parameter['default']) {
						value = parameter['default'];
					}
					
					if (value == null) value = '';
					
					// Check Parameter Type
					switch(parameter['type']) {
						case 'time': // TIME
							$('#parameter-time', config.container).show();
							if (value) {
								if (value > 0) {
									value = value/1000;
								}
							}
							$('#time-input', config.container).val(value).focus().select();
							break;
						case 'boolean': // BOOLEAN
							$('#parameter-boolean', config.container).show();
							var button = $('#boolean-input', config.container);
							if (value == 'true') {
								button.addClass('btn-success');
								button.text('True');
							}
							else {
								button.removeClass('btn-success');
								button.text('False');
							}
							break;
						case 'value': // VALUE
							$('#parameter-value', config.container).show();
							$('#value-input', config.container).val(value).focus().select();
							break;
						default:
							$('#parameter-text', config.container).show();
							$('#text-input', config.container).val(value).focus().select();
							break;
					}

					if (typeof parameter['description'] !== 'undefined' && parameter['description'].length > 0) {
						$('#parameter-description', config.container).html(parameter['description']);
					}
					else {
						$('#parameter-description', config.container).html('<b style="color: orange">No parameter description available for parameter "'+parameterid+'".');
					}
					$('#parameter-add', config.container).prop('disabled', false);
					$('#parameter-description', config.container).show();
				}
			}
		});

		$('#config #parameter-add, #config #parameter-edit', config.container).off('click').on('click', function(){
			
			var parameterid = $('#parameter-select', config.container).val();
			var group = $('#parameter-select :selected', config.container).attr('group');
			
			var parameter = config.getInfo(parameterid, group);
			if (parameter != null) {
				var value = '';

				// Check Parameter Type
				var input;
				switch(parameter['type']) {
					case 'time': // TIME
						value = $("#time-input", config.container).val()*1000;
						break;
					case 'boolean': // BOOLEAN
						value = $("#boolean-input", config.container).text().toLowerCase();
						break;
					case 'value': // VALUE
						value = $("#value-input", config.container).val();
						value = parseFloat(value.replace(",", "."));
						if (isNaN(value)) {
							alert('Value must be a valid number');
							return false;
						}
						break;
					default:
						value = $("#text-input", config.container).val();
						break;
				}
				config.setParameter(parameterid, group, ''+value);
				
				config.draw();
			}
		});

		$('#config #parameter-cancel', config.container).off('click').on('click', function() {

			$('#parameter-header-add', config.container).show();
			$('#parameter-header-edit', config.container).hide();

			var unconfigured = 0;
			if (typeof config.info.address !== 'undefined') {
				if (typeof config.parameters[config.context.toLowerCase()+'Address'] !== 'undefined') {
					var address = config.parameters[config.context.toLowerCase()+'Address'];
					for (var i = 0; i < config.info.address.length; i++) {
						if (config.info.address[i]['id'] === 'address' && typeof config.parameters[address] === 'undefined') unconfigured++;
						else if (typeof config.parameters[address][config.info.address[i]['id']] === 'undefined') unconfigured++;
					}
				}
				else unconfigured++;
			}
			if (typeof config.info.settings !== 'undefined') {
				if (typeof config.parameters['settings'] !== 'undefined') {
					for (var i = 0; i < config.info.settings.length; i++) {
						if (config.info.settings[i]['id'] === 'settings' && typeof config.parameters['settings'] === 'undefined') unconfigured++;
						else if (typeof config.parameters['settings'][config.info.settings[i]['id']] === 'undefined') unconfigured++;
					}
				}
				else unconfigured++;
			}
			if (typeof config.info.config !== 'undefined') {
				for (var i = 0; i < config.info.config.length; i++) {
					if (typeof config.parameters[config.info.config[i]['id']] === 'undefined') unconfigured++;
				}
			}
			
			if (unconfigured > 0) {
				$('#parameter-select', config.container).prop('disabled', false).val('');
				$('#parameter-add', config.container).prop('disabled', true);
			}
			else {
				$('#parameter-select', config.container).find('option').remove().end().prop('disabled', true);
				$('#parameter-add', config.container).prop('disabled', true);
			}
			
			$('#parameter-btn-edit', config.container).hide();
			$('#parameter-btn-add', config.container).show();
			$('#parameter-text', config.container).hide();
			$('#parameter-time', config.container).hide();
			$('#parameter-value', config.container).hide();
			$('#parameter-boolean', config.container).hide();
			$('#parameter-description', config.container).html('').hide();
		});
		
		$('#config #boolean-input', config.container).off('click').on('click', function() {
			if ($(this).text() == 'False') {
				$(this).addClass('btn-success');
				$(this).text('True');
			}
			else {
				$(this).removeClass('btn-success');
				$(this).text('False');
			}
		});
	},
	
	'getInfo':function(id, group) {
		if (config.info.hasOwnProperty(group)) {
			for (var i = 0; i < config.info[group].length; i++) {
				if (config.info[group][i]['id'] === id) {
					return config.info[group][i];
				}
			}
		}
		return null;
	},
	
	'getParameter':function(id, group) {

		if (typeof config.parameters[group] !== 'undefined' && 
				typeof config.parameters[group][id] !== 'undefined') {

			return config.parameters[group][id];
		}
		return null;
	},
	
	'setParameter':function(id, group, value) {

		if (typeof config.parameters[group] === 'undefined') {
			config.parameters[group] = {};
		}
		config.parameters[group][id] = value;
	},
	
	'parseParameters':function(group) {
		
		if (typeof config.parameters[group] !== 'undefined') {
			var pararr = [];
			// Add parameters in the defined order of the information
			for (var p = 0, i = 0; i < this.info[group].length; i++) {
				parinfo = this.info[group][i];

				if (config.parameters[group].hasOwnProperty(parinfo.id)) {
					var value = config.parameters[group][parinfo.id];
					if (typeof config.info[group+'Syntax']['keyValueSeparator'] !== 'undefined') {
						pararr.push(parinfo.id+config.info[group+'Syntax']['keyValueSeparator']+value);
					}
					else {
						pararr.push(value);
					}
				}
			}
			return pararr.join(config.info[group+'Syntax']['listDelimiter']);
		}
		return null;
	},
	
	'valid':function() {

		for (var group in config.info) {
			if (config.info.hasOwnProperty(group) && group !== 'scanSettings') {
				for (var i in config.info[group]) {
					if (config.info[group].hasOwnProperty(i)) {
						var parameter = config.info[group][i];
						var id = parameter['id'];
						if (parameter['required']) {
							if (!(id in config.parameters[group]) || config.parameters[group][id].length === 0) {
								return false;
							}
						}
					}
				}
			}
		}
		
		return true;
	}
}