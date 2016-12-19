/*
	table.js is released under the GNU Affero General Public License.
	See COPYRIGHT.txt and LICENSE.txt.

	Part of the OpenEnergyMonitor project: http://openenergymonitor.org
*/
var muctablefields = 
{
	'icondisable': {
		'draw': function(row,field) {
			var icon = "";
			if (table.data[row][field] == true) icon = 'icon-remove';
			else if (table.data[row][field] == false) icon = 'icon-ok';
			
			if (table.data[row]['mucid'] > 0) {
				return "<i class='"+icon+"' type='input' row='"+row+"' style='cursor:pointer'></i>";
			}
			else return "<i class='"+icon+"' type='input' row='"+row+"' style='cursor:pointer; opacity:0.33'></i>";
		},

		'event': function() {
			// Event code for clickable switch state icon's
			$(table.element).on('click', 'i[type=input]', function() {
				var row = $(this).parent().attr('row');
				var field = $(this).parent().attr('field');
				if (table.data[row]['mucid'] > 0) {
					$(table.element).trigger("onEdit");
					
					table.data[row][field] = !table.data[row][field];
					$(table.element).trigger("onDisable",[table.data[row]['id'],row,table.data[row][field]]);
					if (table.data[row][field]) $(this).attr('class', 'icon-remove'); else $(this).attr('class', 'icon-ok');
					table.draw();
					$(table.element).trigger("onResume");
				}
			});
		}
	},

	'iconmuc': {
		'draw': function(row,field)
		{
			if (table.data[row]['mucid'] > 0) return "<i class='"+table.fields[field].icon+"' type='icon' row='"+row+"' style='cursor:pointer'></i>";
			else return "<i class='"+table.fields[field].icon+"' type='icon' row='"+row+"' style='cursor:pointer; opacity:0.33' disabled></i>";
		}
	},

	'state': {
		'draw': function (row,field) { return list_format_state(table.data[row][field]) }
	},

	'devicelist': {
		'draw': function (row,field) { return list_format_devicelist(table.data[row]['mucid'], table.data[row][field], false) }
	},

	'channellist': {
		'draw': function (row,field) { return list_format_channellist(table.data[row]['mucid'], table.data[row][field], false) }
	},

	'group-state': {
		'draw': function(group,rows,field)
		{
			var errorstate = '';
			for (i in rows) {
				var row=rows[i];
				var state = table.data[row][field];
				if (errorstate !== 'DELETED') {
					if (state === 'DELETED') {
					errorstate = state;
					}
					else if (errorstate !== 'DISABLED') {
						if (state === 'DISABLED') {
						errorstate = state;
						}
						else if (errorstate !== 'DRIVER_UNAVAILABLE') {
							if (state === 'DRIVER_UNAVAILABLE') {
								errorstate = state;
							}
						}
					}
				}
			}
			return list_format_state(errorstate);
		}
	},

	'group-devicelist': {
		'draw': function (group,rows,field)
		{
			var out = "";
			for (i in rows) {
				var row=rows[i];
				out += list_format_devicelist(table.data[row]['mucid'], table.data[row][field], true);
			}
			return out;
		}
	},

	'group-channellist': {
		'draw': function (group,rows,field)
		{
			var out = "";
			for (i in rows) {
				var row=rows[i];
				out += list_format_channellist(table.data[row]['mucid'], table.data[row][field], true);
			}
			return out;
		}
	}
}

function list_format_state(state){

	var color = "rgb(255,0,0)";
	if (state === 'CONNECTED' || state === 'SAMPLING' || state === 'LISTENING') {
		color = "rgb(50,200,50)";
	}
	else if (state === 'READING' || state === 'WRITING' || state === 'STARTING_TO_LISTEN' || state === 'SCANNING_FOR_CHANNELS') {
		color = "rgb(240,180,20)";
	}
	else if (state === 'CONNECTING' || state === 'WAITING_FOR_CONNECTION_RETRY' || state === 'DISCONNECTING') {
		color = "rgb(255,125,20)";
	}
	state = state.toLowerCase().split('_').join(' ');

	return "<span style='color:"+color+";'>"+state+"</span>";
}

function list_format_devicelist(mucid, devicelist, group){

	var out = '';
	if (devicelist != null && device != null && device.states != null) {
		for (var i = 0; i < devicelist.length; i++) {
			
			var name = devicelist[i];
			var label = "<small>"+name+"</small>";
			var title = "Device " + name;
			
			for (var s = 0; s < device.states.length; s++) {
				if (device.states[s]['mucid'] == mucid && device.states[s]['name'] === name) {
					out += list_format_label(device.states[s]['name'], device.states[s]['mucid'], device.states[s]['state'], "device", label, title, group);
				}
			}
		}
	}
	return out;
}

function list_format_channellist(mucid, channellist, group){

	var out = '';
	if (channellist != null && channel != null && channel.states != null) {
		for (var i = 0; i < channellist.length; i++) {
			
			var name = channellist[i];
			var label = "<small>"+name+"</small>";
			var title = "Channel " + name;
			
			for (var s = 0; s < channel.states.length; s++) {
				if (channel.states[s]['mucid'] == mucid && channel.states[s]['name'] === name) {
					out += list_format_label(channel.states[s]['name'], channel.states[s]['mucid'], channel.states[s]['state'], "channel", label, title, group);
				}
			}
		}
	}
	return out;
}

function list_format_label(id, mucid, state, type, label, title, group){

	var labeltype = null;
	if (state === 'CONNECTING' || state === 'WAITING_FOR_CONNECTION_RETRY' || state === 'DISCONNECTING') {
		labeltype = 'warning';
	}
	else if (state === 'DELETED' || state === 'DISABLED' || state === 'DRIVER_UNAVAILABLE') {
		labeltype = 'important';
	}
	state = state.toLowerCase().split('_').join(' ');
	
	title += " (State: "+state+")";
	
	if (labeltype == null) {
		if (group) return '';
		else labeltype = 'info';
	}

	var label = "<span class='label label-"+labeltype+"' title='"+title+"' style='cursor:pointer'>"+label+"</span> ";
	return "<a class='"+type+"-label' mucid='"+mucid+"' "+type+"id='"+id+"'>"+label+"</a>";
}