var device_dialog =
{
	mucid: null,
	driverid: null,
	deviceid: null,
	deviceconfig: null,
	devicetype: null,
	
	templates: null,
	templateshow: false,

	'loadAdd':function(driver){
		this.loadTemplates();

		if (driver != null) {
			this.mucid = driver.mucid;
			this.driverid = driver.name;
		}
		else {
			this.mucid = null;
			this.driverid = null;
		}
		
		this.deviceid = null;
		this.deviceconfig = {};
		this.devicetype = '';

		this.drawAdd();
		
		$('#deviceconfig-delete').hide();
		
		// Initialize callbacks
		this.registerEvents();
	},

	'loadConfig': function(deviceconfig, showdelete){
		this.loadTemplates();

		this.mucid = deviceconfig.mucid;
		this.driverid = deviceconfig.driver;
		this.deviceid = deviceconfig.id;
		this.deviceconfig = deviceconfig;
		this.devicetype = deviceconfig.type;

		this.drawConfig();

		$('#deviceConfigModalLabel').html('Configure Device: <b>'+this.deviceconfig.name+'</b>');
		if (showdelete) {
			$('#deviceDeleteModalLabel').html('Delete Device: <b>'+this.deviceconfig.name+'</b>');
			$('#deviceconfig-delete').show();
		}
		else {
			$('#deviceconfig-delete').hide();
		}
		
		// Initialize callbacks
		this.registerEvents();
	},

	'loadDelete': function(deviceconfig, tablerow){

		this.mucid = deviceconfig.mucid;
		this.driverid = deviceconfig.driver;
		this.deviceid = deviceconfig.id;
		this.deviceconfig = deviceconfig;
		
		this.drawDelete(tablerow);
		
		$('#deviceDeleteModalLabel').html('Delete Device: <b>'+this.deviceconfig.name+'</b>');
	},

	'loadTemplates': function(){
		
		$.ajax({ url: path+"device/template/list.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
			if (Object.keys(data).length > 0) {
				device_dialog.templates = data;
				device_dialog.templateshow = true;
			}
			else {
				device_dialog.templateshow = false;
			}
			device_dialog.drawTemplates();
		}});
	},

	'drawAdd':function(){
		$("#deviceConfigModal").modal('show');
		this.adjustModal();
		this.clearModal();

		config.init($('#deviceconfig-container'), path, 'Device', false, false);

		$('#deviceConfigModalLabel').html('New Device');
		$('#deviceconfig-node').val('');
		$('#deviceconfig-name').val('');
		$('#deviceconfig-description').val('');

		device_dialog.drawTemplates();
		

		if (this.mucid != null) {
			$("#deviceconfig-container").show();
			$('#deviceconfig-driver').html('<b>'+this.driverid+'</b>').show();
			
			if (!this.drawConfigParameters()) {
				$("#deviceConfigModal").modal('hide');
				return false;
			}
		}
		else {
			$("#deviceconfig-container").show();

			var mucs = [];
			$.ajax({ url: path+"driver/list.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
				if (data.length > 0) {
					// Append drivers from database to select
					var driverselect = $("#deviceconfig-driver-select").show();
					driverselect.append("<option selected hidden='true' value=''>Select a driver</option>");
					driverselect.append('<optgroup label="No controller">');
					driverselect.append("<option value='standalone' mucid='-1'>standalone</option>");

					$.each(data, function() {
						if (mucs.indexOf(this.mucid) < 0) {
							driverselect.append('<optgroup label="'+this.muc+'">');
							mucs.push(this.mucid)
						}
						driverselect.append('<option value="'+this.name+'" mucid="'+this.mucid+'">'+this.name+'</option>');
					});
					$("#deviceconfig-driver").hide();
				}
				else {
					$('#deviceconfig-driver').html('<b>standalone</b>').show();
					device_dialog.mucid = -1;
					device_dialog.driverid = 'standalone';
					device_dialog.drawTemplates();
				}
			}});
		}
	},
	
	'drawConfig':function(){
		$("#deviceConfigModal").modal('show');
		this.adjustModal();
		this.clearModal();

		config.init($('#deviceconfig-container'), path, 'Device', false, false);

		$('#deviceconfig-driver').html('<b>'+this.deviceconfig.driver+'</b>').show();
		$('#deviceconfig-driver-select').hide();
		$('#deviceconfig-node').val(this.deviceconfig.nodeid);
		$('#deviceconfig-name').val(this.deviceconfig.name);
		$('#deviceconfig-description').val(this.deviceconfig.description);
		
		if (this.mucid > 0) {
			$("#deviceconfig-container").show();
			
			if (!this.drawConfigParameters()) {
				$("#deviceConfigModal").modal('hide');
			}
		}
		else {
			$("#deviceconfig-container").hide();
		}
	},
	
	'drawTemplates':function(){
		var drivertemplates = null;
		if (device_dialog.templates != null) {
			drivertemplates = device_dialog.templates[device_dialog.driverid];
		}
		if (drivertemplates != null) {
			var templateselect = $("#deviceconfig-template-select").empty().show();
			templateselect.append("<option selected value=''>No template</option>");
			
			for (templateid in drivertemplates) {
				templateselect.append('<option value="'+templateid+'">'+drivertemplates[templateid]['name']+'</option>');
			}
			templateselect.val(device_dialog.devicetype);
			$('#deviceconfig-template-overlay').hide();
		}
		else {
			device_dialog.templateshow = false;
			$('#deviceconfig-template-overlay').show();
		}

		if (device_dialog.templateshow) {
			$('#deviceconfig-template-header').html(
					'<i class="toggle-header icon-minus-sign" group="template" style="cursor:pointer"></i>'+
					'<a class="toggle-header" group="template" style="cursor:pointer"> Template</a>');

			$('#deviceconfig-template').show();
		}
		else {
			$('#deviceconfig-template-header').html(
					'<i class="toggle-header icon-plus-sign" group="template" style="cursor:pointer"></i>'+
					'<a class="toggle-header" group="template" style="cursor:pointer"> Template</a>');
			
			$('#deviceconfig-template').hide();
		}
	},

	'drawConfigParameters':function(){
		if (device_dialog.mucid > 0) {
			$('#deviceconfig-loader').show();
			$('#deviceconfig-config-overlay').hide();
			$("#deviceconfig-container").show();

			// This function gets called both for device creation and configuration.
			// If a device id is already defined but the parameters are null, try to fetch them.
			if (device_dialog.deviceconfig == null) {
				if (device_dialog.deviceid != null) {
					var localdevice = device.get(device_dialog.mucid, device_dialog.deviceid);
					if (localdevice.success == null || !localdevice.success) {
						localdevice = {};
					}
					device_dialog.deviceconfig = localdevice;
				}
				else {
					device_dialog.deviceconfig = {};
				}
			}

			var info = device.info(device_dialog.mucid, device_dialog.deviceid, device_dialog.driverid);
			if (info.success != null && !info.success) {
				$('#deviceconfig-loader').hide();
				$('#deviceconfig-config-overlay').show();
				alert('Device info could not be retrieved:\n'+info.message);
				return false;
			}
			else {
				if (info.description != null) {
					$('#deviceconfig-info').html('<span style="color:#888">'+info.description+'</span>').show();
				}
				else {
					$('#deviceconfig-info').hide();
				}
				
				config.load(device_dialog.deviceconfig, info);
			}
			$('#deviceconfig-loader').hide();
		}
		else {
			device_dialog.deviceconfig = {};
			$('#deviceconfig-info').text('').hide();
			$("#deviceconfig-container").hide();
		}
		return true;
	},

	'drawDelete':function(row){
		$('#deviceDeleteModal').modal('show');

		$("#devicedelete-confirm").off('click').on('click', function(){
			$('#devicedelete-loader').show();
			var result = device.remove(device_dialog.mucid, device_dialog.deviceid);
			$('#devicedelete-loader').hide();
			
			if (!result.success) {
				alert('Unable to delete device:\n'+result.message);
				return false;
				
			} else {
				if (row != null) table.remove(row);
			    
				update();
				$('#deviceDeleteModal').modal('hide');
			}
		});
	},

	'clearModal':function(){
        $('#deviceconfig-driver').html('<span style="color:#888"><em>loading...</em></span>').show();
		$("#deviceconfig-driver-select").empty().hide();

		$("#deviceconfig-template-select").empty();
		$("#deviceconfig-template-description").text('').hide();
		$('#deviceconfig-template-overlay').show();
		
		$('#deviceconfig-config-overlay').show();
	},

	'adjustModal':function(){
		if ($("#deviceConfigModal").length) {
			var h = $(window).height() - $("#deviceConfigModal").position().top - 180;
			$("#deviceConfigBody").height(h);
		}
	},

	'registerEvents':function(){
		
		// Event to scroll to parameter panel at the bottom of the page when editing
		$('#config', '#deviceconfig-container').on('click', '.edit-parameter', function() {
			var container = $('#deviceConfigBody');
			container.animate({
		    	scrollTop: container.scrollTop() + container.height()
		    });
		});
		
		// Event: minimise or maximise templates
		$('#deviceconfig-template-header').on('click touchend', '.toggle-header', function(e) {
			e.stopPropagation();
			e.preventDefault();
			var $me=$(this);
			if ($me.data('clicked')) {
				$me.data('clicked', false); // reset
				if ($me.data('alreadyclickedTimeout')) clearTimeout($me.data('alreadyclickedTimeout')); // prevent this from happening

				// Do what needs to happen on double click.
				var state = device_dialog.templateshow;
				device_dialog.templateshow = !state;
				device_dialog.drawTemplates();
			}
			else {
				$me.data('clicked', true);
				var alreadyclickedTimeout=setTimeout(function() {
					$me.data('clicked', false); // reset when it happens
	
					// Do what needs to happen on single click. Use $me instead of $(this) because $(this) is  no longer the element
					var state = device_dialog.templateshow;
					device_dialog.templateshow = !state;
					device_dialog.drawTemplates();
				
				},250); // dblclick tolerance
				$me.data('alreadyclickedTimeout', alreadyclickedTimeout); // store this id to clear if necessary
			}
		});
		
		$('#deviceconfig-template-select').off('change').on('change', function(){
			var desc = null;
			device_dialog.devicetype = this.value;

			if (this.value !== '') {
				var template = device_dialog.templates[device_dialog.driverid][device_dialog.devicetype];
				
				if (device_dialog.mucid > 0) {
					device_dialog.deviceconfig = template;
					device_dialog.drawConfigParameters();
				}
				desc = template['description'];
			}
			
			if (desc != null && desc.length > 0) {
				$("#deviceconfig-template-description").html('<span style="color:#888">'+desc+'</span>').show();
			}
			else {
				$("#deviceconfig-template-description").text('').hide();
			}
		});
		
		$('#deviceconfig-driver-select').off('change').on('change', function(){
			device_dialog.mucid = $('option:selected', this).attr('mucid');
			device_dialog.driverid = this.value;
			device_dialog.deviceconfig = null;
			
			device_dialog.templateshow =true;
			device_dialog.drawTemplates();
			if (!device_dialog.drawConfigParameters()) {
				$("#deviceConfigModal").modal('hide');
				return false;
			}
		});

		$("#deviceconfig-save").off('click').on('click', function (){

			var node = $('#deviceconfig-node').val();
			var name = $('#deviceconfig-name').val();
			
			if (node && name && device_dialog.driverid) {
				
				var desc = $('#deviceconfig-description').val();

				if (device_dialog.mucid < 0 || config.valid()) {
					// Show loading indication only if no standalone device
					if (device_dialog.mucid > 0) {
						$('#deviceconfig-loader').show();
					}
					var deviceconfig = {
							'nodeid': node,
							'name': name,
							'description': desc,
							'type': device_dialog.devicetype
					};
					if (device_dialog.deviceconfig['disabled'] != null) {
						deviceconfig['disabled'] = device_dialog.deviceconfig['disabled'];
					}
					deviceconfig['channels'] = $.extend([], device_dialog.deviceconfig.channels);

					deviceconfig['address'] = config.parseParameters('address');
					deviceconfig['settings'] = config.parseParameters('settings');
					
					// Make sure JSON.stringify gets passed an object, as arrays will return empty
					if (device_dialog.deviceconfig['config'] != null) {
						var localconfig = $.extend({}, device_dialog.deviceconfig.config);
						if (Object.keys(localconfig).length > 0) {
							deviceconfig['config'] = localconfig;
						}
					}
					
					if (typeof device_dialog.deviceconfig.id !== 'undefined') {
						deviceconfig['id'] = device_dialog.deviceconfig.id;
						var result = device.update(device_dialog.mucid, device_dialog.deviceid, deviceconfig);
						
						if (result.success != null && !result.success) {
							$('#deviceconfig-loader').hide();
							alert('Device could not be configured:\n'+result.message);
							return false;
						}
					}
					else {
						var result = device.create(device_dialog.mucid, device_dialog.driverid, deviceconfig);
						
						var id = result.id;
						if (result.success != null && !result.success || id<1) {
							alert('Device could not be created:\n'+result.message);
							return false;
						}
					}
					
					// Initialize the template type, if it changed
					if (device_dialog.deviceconfig.type !== device_dialog.devicetype) {
						var result = device.initTemplate(device_dialog.mucid, device_dialog.driverid, deviceconfig);
						if (result.success != null && !result.success) {
							device_dialog.deviceconfig.id = id;
							alert('Device template could not be initiated:\n'+result.message);
							return false;
						}
					}
					$('#deviceconfig-loader').hide();
				}
				else {
					alert('Required parameters need to be configured first.');
					return false;
				}
				
				update();
				$('#deviceConfigModal').modal('hide');
			}
			else {
				alert('Device needs to be configured first.');
				return false;
			}
		});

		$("#deviceconfig-delete").off('click').on('click', function (){
			$('#deviceConfigModal').modal('hide');
			device_dialog.drawDelete(null);
		});
	}
}