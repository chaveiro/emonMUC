var driver_dialog =
{
	mucid: null,
	driverid: null,
	driverconfig: null,
	
	'loadAdd':function(mucid){
		
		this.mucid = mucid;
		this.driverid = null;
		this.driverconfig = null;

		this.drawAdd();
		
		// Initialize callbacks
		this.registerEvents();
	},

	'loadConfig': function(driver){
		
		this.mucid = driver.mucid;
		this.driverid = driver.name;
		this.driverconfig = driver;

		this.drawConfig();
		
		// Initialize callbacks
		this.registerEvents();
	},

	'loadDelete': function(driver, tablerow){
		
		this.mucid = driver.mucid;
		this.driverid = driver.name;
		this.driverconfig = driver;

		this.drawDelete(tablerow);
	},
	
	'drawAdd':function(){
		$("#driverConfigModal").modal('show');
		this.adjustModal();
		this.clearModal();

		config.init($('#driverconfig-container'), path, 'Driver', true, true);
		
		$('#driverConfigModalLabel').html('New Driver');
		
		if (this.mucid) {
			$('#driverconfig-muc').hide();
			if (!this.drawConfigDrivers()) {
				$("#driverConfigModal").modal('hide');
				return false;
			}
		}
		else {
			// Append MUCs from database to select
			var mucselect = $("#driverconfig-muc-select");
			mucselect.append("<option selected hidden='true' value=''>Select a controller</option>").val('');

			$.ajax({ url: path+"muc/list.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
				$.each(data, function() {
					mucselect.append($("<option />").val(this.id).text(this.description));
				});
			}});
		}
	},
	
	'drawConfig':function(){
		$("#driverConfigModal").modal('show');
		this.adjustModal();
		this.clearModal();
		
		config.init($('#driverconfig-container'), path, 'Driver', true, true);
		
		$('#driverConfigModalLabel').html('Configure Driver: <b>'+this.driverid+'</b>');

		$('#driverconfig-muc').hide();
		$('#driverconfig-select').hide();
		if (!this.drawConfigDrivers() || !this.drawConfigParameters()) {
			
			$("#driverConfigModal").modal('hide');
		}
	},
	
	'drawConfigDrivers':function(){
		if (driver_dialog.mucid) {
			$('#driverconfig-loader').show();
			$('#driverconfig-overlay').hide();
			
			var drivers = driver.unconfigured(driver_dialog.mucid);
			$('#driverconfig-loader').hide();
			$('#driverconfig-container-overlay').show();
			
			var driverselect = $("#driverconfig-select");
			driverselect.prop('disabled', false).find('option').remove().end()
				.append("<option selected hidden='true' value=''>Select a driver</option>").val('');
			$('#driverconfig-description').text('');
			
			if (drivers.success != null && !drivers.success) {
				alert('Driver info could not be retrieved:\n'+drivers.message);
				return false;
			}
			else {
				$.each(drivers, function() {
					driverselect.append($("<option />").val(this).text(this));
				});
			}
			return true;
		}
	},
	
	'drawConfigParameters':function(){
		$('#driverconfig-container-overlay').hide();
		$('#driverconfig-loader').show();

		if (driver_dialog.driverconfig == null && driver_dialog.driverid) {
			var localdriver = driver.get(driver_dialog.mucid, driver_dialog.driverid);
			if (typeof localdriver.name !== 'undefined' && localdriver.name == driver_dialog.driverid) {
				driver_dialog.driverconfig = localdriver;
			}
			else {
				driver_dialog.driverconfig = {};
			}
		}

		var info = driver.info(driver_dialog.mucid, driver_dialog.driverid);
		if (info.success != null && !info.success) {
			alert('Driver info could not be retrieved:\n'+info.message);
			$('#driverconfig-loader').hide();
			$('#driverconfig-container-overlay').show();
			return false;
		}
		else {
			if (info.description != null) {
				$('#driverconfig-description').html('<span style="color:#888">'+info.description+'</span>');
			}
			
			config.load(driver_dialog.driverconfig, info);
		}
		$('#driverconfig-loader').hide();
		return true;
	},
	
	'drawDelete':function(row){
		$('#driverDeleteModal').modal('show');
		
		$('#driverDeleteModalLabel').html('Delete Driver: <b>'+driver_dialog.driverid+'</b>');

		$("#driverdelete-confirm").off('click').on('click', function(){
			$('#driverdelete-loader').show();
			var result = driver.remove(driver_dialog.mucid, driver_dialog.driverid);
			$('#driverdelete-loader').hide();
			
			if (!result.success) {
				alert('Unable to delete driver:\n'+result.message);
				return false;
				
			} else {
				if (row != null) table.remove(row);
			    
				update();
				$('#driverDeleteModal').modal('hide');
			}
			return true;
		});
	},

	'clearModal':function(){
		$('#driverconfig-muc').show();
		$("#driverconfig-muc-select").empty();

		$("#driverconfig-select").empty()
			.prop('disabled', true).show();
	
		$('#driverconfig-description').text('');
		
		$('#driverconfig-overlay').show();
		$('#driverconfig-container-overlay').hide();
	},

	'adjustModal':function(){
		if ($("#driverConfigModal").length) {
			var h = $(window).height() - $("#driverConfigModal").position().top - 180;
			$("#driverConfigBody").height(h);
		}
	},

	'registerEvents':function(){
		
		// Event to scroll to parameter panel at the bottom of the page when editing
		$('#config', '#driverconfig-container').on('click', '.edit-parameter', function() {
			var container = $('#driverConfigBody');
			container.animate({
		    	scrollTop: container.scrollTop() + container.height()
		    });
		});
		
		$('#driverconfig-muc-select').off('change').on('change', function(){
			var mucid = this.value;
			if (mucid.length > 0) {
				driver_dialog.mucid = mucid;
				driver_dialog.drawConfigDrivers();
			}
		});
		
		$('#driverconfig-select').off('change').on('change', function(){
			var driverid = this.value;
			if (driverid.length > 0) {
				driver_dialog.driverid = driverid;
				driver_dialog.driverconfig = null;
				driver_dialog.drawConfigParameters();
			}
		});

		$("#driverconfig-save").off('click').on('click', function (){
			if (driver_dialog.mucid && driver_dialog.driverid) {
				$('#driverconfig-loader').show();
				
				if (config.valid()) {
					var driverconfig = { 'id': driver_dialog.driverconfig.name };
					
					//make sure to JSON.stringify a dict, as arrays will return empty
					if (driver_dialog.driverconfig['config'] != null) {
						var localconfig = $.extend({}, driver_dialog.driverconfig.config);
						if (Object.keys(localconfig).length > 0) {
							driverconfig['config'] = localconfig;
						}
					}
					
					if (typeof driver_dialog.driverconfig.name !== 'undefined') {
						var result = driver.update(driver_dialog.mucid, driver_dialog.driverid, driverconfig);
						$('#driverconfig-loader').hide();
						
						if (result.success != null && !result.success) {
							alert('Driver could not be configured:\n'+result.message);
							return false;
						}
					}
					else {
						var result = driver.create(driver_dialog.mucid, driver_dialog.driverid, driverconfig);
						$('#driverconfig-loader').hide();
						
						if (result.success != null && !result.success) {
							alert('Driver could not be created:\n'+result.message);
							return false;
						}
					}
					
					update();
					$('#driverConfigModal').modal('hide');
				}
				else {
					$('#driverconfig-loader').hide();
					
					alert('Required parameters need to be configured first.');
					return false;
				}
			}
			else {
				alert('Driver needs to be configured first.');
				return false;
			}
		});

		$("#driverconfig-delete").off('click').on('click', function (){
			$('#driverConfigModal').modal('hide');
			
			driver_dialog.drawDelete(null);
			$('#driverDeleteModalLabel').html('Delete Driver: <b>'+driver_dialog.drivername+'</b>');
		});
	}
}
