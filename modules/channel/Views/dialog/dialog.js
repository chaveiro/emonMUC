var channel_dialog =
{
	mucid: null,
	driverid: null,
	nodeid: null,
	deviceid: null,
	channelid: null,
	channelconfig: null,

	'loadAdd':function(device){

		if (device != null) {
			this.mucid = device.mucid;
			this.driverid = device.driver;
			this.nodeid = device.nodeid;
			this.deviceid = device.name;
		}
		else {
			this.mucid = null;
			this.driverid = null;
			this.nodeid = null;
			this.deviceid = null;
		}
		
		this.channelid = null;
		this.channelconfig = {};
		
		this.drawAdd();
		
		// Initialize callbacks
		this.registerEvents();
	},

	'loadConfig': function(channelconfig, showdelete){

		this.mucid = channelconfig.mucid;
		this.deviceid = channelconfig.device;
		this.nodeid = channelconfig.nodeid;
		this.channelid = channelconfig.name;
		this.channelconfig = channelconfig;
		
		this.drawConfig();
		
		$('#channelConfigModalLabel').html('Configure Channel: <b>'+this.channelconfig.name+'</b>');
		if (showdelete) {
			$('#channelDeleteModalLabel').html('Delete Channel: <b>'+this.channelconfig.name+'</b>');
			$('#channelconfig-delete').show();
		}
		
		// Initialize callbacks
		this.registerEvents();
	},

	'loadDelete': function(channelconfig, tablerow){

		this.mucid = channelconfig.mucid;
		this.deviceid = channelconfig.driver;
		this.channelid = channelconfig.name;
		this.channelconfig = channelconfig;
		
		this.drawDelete(tablerow);
		
		$('#channelDeleteModalLabel').html('Delete Channel: <b>'+this.channelconfig.name+'</b>');
	},

	'drawAdd':function(){
		$("#channelConfigModal").modal('show');
		this.adjustModal();
		this.clearModal();
		
		config.init($('#channelconfig-container'), path, 'Channel', false, true);
		
		$('#channelConfigModalLabel').html('New Channel');
		$('#channelconfig-name').val('');
		$('#channelconfig-description').val('')
		
		if (this.mucid != null) {
			$('#channelconfig-device').html('<b>'+this.deviceid+'</b>').show();
			$('#channelconfig-device-select').hide();
			
			$('#channelconfig-node').html(this.nodeid);
			
			if (!this.drawConfigParameters()) {
				$("#channelConfigModal").modal('hide');
				return false;
			}
		}
		else {
			$("#channelconfig-device").hide();
			$('#channelconfig-node').html('');

			// Append devices from database to select
			var deviceselect = $("#channelconfig-device-select").show();
			deviceselect.append("<option selected hidden='true' value=''>Select a device</option>");
						
			$.ajax({ url: path+"device/list.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
				$.each(data, function() {
					if (this.mucid > 0) {
						deviceselect.append('<option value="'+this.name+'" mucid="'+this.mucid+'" driverid="'+this.driver+'" nodeid="'+this.nodeid+'">'+this.name+'</option>');
					}
				});
			}});
		}
	},

	'drawConfig':function(configjson){
		$("#channelConfigModal").modal('show');
		this.adjustModal();
		this.clearModal();

		config.init($('#channelconfig-container'), path, 'Channel', false, true);
		
		$('#channelconfig-device').html('<b>'+this.channelconfig.device+'</b>').show();
		$('#channelconfig-device-select').hide();
		$('#channelconfig-node').html(this.channelconfig.nodeid);
		$('#channelconfig-name').val(this.channelconfig.name);
		$('#channelconfig-description').val(this.channelconfig.description);

		if (!this.drawConfigParameters()) {
			$("#channelConfigModal").modal('hide');
		}
	},

	'drawConfigParameters':function(){
		
		$('#channelconfig-loader').show();
		$('#channelconfig-overlay').hide();

		// This function gets called both for channel creation and configuration.
		// If a channel id is already defined but the parameters are null, try to fetch them.
		if (channel_dialog.channelconfig == null) {
			if (channel_dialog.channelid != null) {
				var localchannel = channel.get(channel_dialog.mucid, channel_dialog.channelid);
				if (localchannel.success == null || !localchannel.success) {
					localchannel = {};
				}
				channel_dialog.channelconfig = localchannel;
			}
			else {
				channel_dialog.channelconfig = {};
			}
		}
		
		var info = channel.info(channel_dialog.mucid, channel_dialog.channelid, channel_dialog.driverid);
		if (info.success != null && !info.success) {
			$('#channelconfig-loader').hide();
			$('#channelconfig-overlay').show();
			alert('Channel info could not be retrieved:\n'+info.message);
			return false;
		}
		else {
			if (info.description != null) {
				$('#channelconfig-info').html('<span style="color:#888">'+info.description+'</span>').show();
			}
			else {
				$('#channelconfig-info').hide();
			}

			config.load(channel_dialog.channelconfig, info);
		}
		$('#channelconfig-loader').hide();
		
		return true;
	},

	'drawDelete':function(row){
		$('#channelDeleteModal').modal('show');

		$("#channeldelete-confirm").off('click').on('click', function(){
			$('#channeldelete-loader').show();
			var result = channel.remove(channel_dialog.mucid, channel_dialog.channelid);
			$('#channeldelete-loader').hide();
			
			if (!result.success) {
				alert('Unable to delete channel:\n'+result.message);
				return false;
				
			} else {
				if (row != null) table.remove(row);
			    
				update();
				$('#channelDeleteModal').modal('hide');
			}
		});
	},

	'clearModal':function(){
        $('#channelconfig-device').html('<span style="color:#888"><em>loading...</em></span>').show();
		$("#channelconfig-device-select").empty().hide();
		
		$('#channelconfig-overlay').show();
	},

	'adjustModal':function(){
		if ($("#channelConfigModal").length) {
			var h = $(window).height() - $("#channelConfigModal").position().top - 180;
			$("#channelConfigBody").height(h);
		}
	},

	'registerEvents':function(){
		
		// Event to scroll to parameter panel at the bottom of the page when editing
		$('#config', '#channelconfig-container').on('click', '.edit-parameter', function() {
			var container = $('#channelConfigBody');
			container.animate({
		    	scrollTop: container.scrollTop() + container.height()
		    });
		});
		
		$('#channelconfig-device-select').off('change').on('change', function(){
			channel_dialog.mucid = $('option:selected', this).attr('mucid');
			channel_dialog.driverid = $('option:selected', this).attr('driverid');
			channel_dialog.nodeid = $('option:selected', this).attr('nodeid');
			channel_dialog.deviceid = this.value;
			channel_dialog.channelconfig = null;
			$('#channelconfig-node').html(channel_dialog.nodeid);
			
			if (!channel_dialog.drawConfigParameters()) {
				$("#channelConfigModal").modal('hide');
				return false;
			}
		});

		$("#channelconfig-save").off('click').on('click', function (){
			
			var name = $('#channelconfig-name').val();
			
			if (name && channel_dialog.deviceid) {

				var desc = $('#channelconfig-description').val();
				
				if (config.valid()) {
					$('#channelconfig-loader').show();
					
					var channelconfig = {
							'nodeid': channel_dialog.nodeid,
							'name': name,
							'description': desc
					};
					if (channel_dialog.channelconfig['disabled'] != null) {
						channelconfig['disabled'] = channel_dialog.channelconfig['disabled'];
					}
					channelconfig['address'] = config.parseParameters('address');
					
					//make sure to JSON.stringify dictionaries, as arrays will return empty
					if (channel_dialog.channelconfig['config'] != null) {
						var localconfig = $.extend({}, channel_dialog.channelconfig.config);
						if (Object.keys(localconfig).length > 0) {
							channelconfig['config'] = localconfig;
						}
					}

					if (typeof channel_dialog.channelconfig.name !== 'undefined') {
						var result = channel.update(channel_dialog.mucid, channel_dialog.channelid, channelconfig);
						$('#channelconfig-loader').hide();
						
						if (result.success != null && !result.success) {
							$('#channelconfig-loader').hide();
							alert('Channel could not be configured:\n'+result.message);
							return false;
						}
					}
					else {
						var result = channel.create(channel_dialog.mucid, channel_dialog.deviceid, channelconfig);
						$('#channelconfig-loader').hide();
						
						if (result.success != null && !result.success) {
							alert('Channel could not be created:\n'+result.message);
							return false;
						}
					}
				}
				else {
					alert('Required parameters need to be configured first.');
					return false;
				}
				
				update();
				$('#channelConfigModal').modal('hide');
			}
			else {
				alert('Channel needs to be configured first.');
				return false;
			}
		});

		$("#channelconfig-delete").off('click').on('click', function (){
			$('#channelConfigModal').modal('hide');
			channel_dialog.drawDelete(null);
		});
	}
}