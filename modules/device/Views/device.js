var device = 
{
	states: null,

	'create':function(mucid, driver, config)
	{
		var result = {};
		$.ajax({ url: path+"device/create.json", data: "mucid="+mucid+"&driver="+driver+"&config="+JSON.stringify(config), dataType: 'json', async: false, success: function(data){result = data;} });
		return result;
	},

	'list':function()
	{
		var result = {};
		$.ajax({ url: path+"device/list.json", dataType: 'json', async: false, success: function(data){result = data;} });
		return result;
	},

	'states':function()
	{
		var result = {};
		$.ajax({ url: path+"device/states.json", dataType: 'json', async: false, success: function(data){result = data;} });
		return result;
	},

	'info':function(mucid, id, driver)
	{
		var result = {};
		var parameters = "mucid="+mucid+"&id="+id;
		if (driver != null) {
			parameters += "&driver="+driver;
		}
		$.ajax({ url: path+"device/info.json", data: parameters, dataType: 'json', async: false, success: function(data){result = data;} });
		return result;
	},

	'get':function(id)
	{
		var result = {};
		$.ajax({ url: path+"device/get.json", data: "id="+id, dataType: 'json', async: false, success: function(data){result = data;} });
		return result;
	},

	'get':function(mucid, name)
	{
		var result = {};
		$.ajax({ url: path+"device/get.json", data: "mucid="+mucid+"&name="+name, dataType: 'json', async: false, success: function(data){result = data;} });
		return result;
	},

	'update':function(mucid, id, config)
	{
		var result = {};
		$.ajax({ url: path+"device/update.json", data: "mucid="+mucid+"&id="+id+"&config="+JSON.stringify(config), dataType: 'json', async: false, success: function(data){result = data;} });
		return result;
	},

	'remove':function(mucid, id)
	{
		var result = {};
		$.ajax({ url: path+"device/delete.json", data: "mucid="+mucid+"&id="+id, dataType: 'json', async: false, success: function(data){result = data;} });
		return result;
	},

	'listTemplates':function()
	{
		var result = {};
		$.ajax({ url: path+"device/template/list.json", dataType: 'json', async: false, success: function(data){result = data;} });
		return result;
	},

	'getTemplate':function(driver, type)
	{
		var result = {};
		$.ajax({ url: path+"device/template/get.json", data: "driver="+driver+"&type="+type, dataType: 'json', async: false, success: function(data){result = data;} });
		return result;
	},

	'initTemplate':function(mucid, driver, config)
	{
		var result = {};
		$.ajax({ url: path+"device/template/init.json", data: "mucid="+mucid+"&driver="+driver+"&config="+JSON.stringify(config), dataType: 'json', async: false, success: function(data){result = data;} });
		return result;
	}
}
