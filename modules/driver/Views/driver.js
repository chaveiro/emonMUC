var driver = {

	'create':function(mucid, id, config)
	{
		var result = {};
		$.ajax({ url: path+"driver/create.json", data: "mucid="+mucid+"&name="+id+"&config="+JSON.stringify(config), dataType: 'json', async: false, success: function(data){result = data;} });
		return result;
	},

	'list':function()
	{
		var result = {};
		$.ajax({ url: path+"driver/list.json", dataType: 'json', async: false, success: function(data) {result = data;} });
		return result;
	},
	
	'unconfigured':function(mucid)
	{
		var result = {};
		$.ajax({ url: path+"driver/unconfigured.json", data: "mucid="+mucid, dataType: 'json', async: false, success: function(data) {result = data;} });
		return result;
	},

	'info':function(mucid, id)
	{
		var result = {};
		$.ajax({ url: path+"driver/info.json", data: "mucid="+mucid+"&name="+id, dataType: 'json', async: false, success: function(data) {result = data;} });
		return result;
	},

	'get':function(mucid, id)
	{
		var result = {};
		$.ajax({ url: path+"driver/get.json", data: "mucid="+mucid+"&name="+id, dataType: 'json', async: false, success: function(data) {result = data;} });
		return result;
	},

	'update':function(mucid, id, config)
	{
		var result = {};
		$.ajax({ url: path+"driver/update.json", data: "mucid="+mucid+"&name="+id+"&config="+JSON.stringify(config), dataType: 'json', async: false, success: function(data){result = data;} });
		return result;
	},

	'remove':function(mucid, id)
	{
		var result = {};
		$.ajax({ url: path+"driver/delete.json", data: "mucid="+mucid+"&name="+id, dataType: 'json', async: false, success: function(data){result = data;} });
		return result;
	}
}
