var channel = 
{
	states: null,

    'create':function(mucid, device, config)
    {
        var result = {};
        $.ajax({ url: path+"channel/create.json", data: "mucid="+mucid+"&device="+device+"&config="+JSON.stringify(config), dataType: 'json', async: false, success: function(data) {result = data;} });
		return result;
    },

    'list':function()
    {
        var result = {};
        $.ajax({ url: path+"channel/list.json", dataType: 'json', async: false, success: function(data) {result = data;} });
        return result;
    },

	'states':function()
	{
		var result = {};
		$.ajax({ url: path+"channel/states.json", dataType: 'json', async: false, success: function(data) {result = data;} });
		return result;
	},

	'info':function(mucid, name, driver)
	{
		var result = {};
		var parameters = "mucid="+mucid+"&name="+name;
		if (driver != null) {
			parameters += "&driver="+driver;
		}
		$.ajax({ url: path+"channel/info.json", data: parameters, dataType: 'json', async: false, success: function(data){result = data;} });
		return result;
	},

	'get':function(mucid, name)
	{
		var result = {};
		$.ajax({ url: path+"channel/get.json", data: "mucid="+mucid+"&name="+name, dataType: 'json', async: false, success: function(data) {result = data;} });
		return result;
	},

    'update':function(mucid, name, config)
    {
        var result = {};
        $.ajax({ url: path+"channel/update.json", data: "mucid="+mucid+"&name="+name+"&config="+JSON.stringify(config), async: false, success: function(data) {result = data;} });
        return result;
    },

	'remove':function(mucid, name)
	{
		var result = {};
		$.ajax({ url: path+"channel/delete.json", data: "mucid="+mucid+"&name="+name, dataType: 'json', async: false, success: function(data){result = data;} });
		return result;
	}
}
