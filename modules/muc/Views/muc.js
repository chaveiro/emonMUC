var muc = {

	'list':function()
	{
		var result = {};
		$.ajax({ url: path+"muc/list.json", dataType: 'json', async: false, success: function(data) {result = data;} });
		return result;
	},

	'get':function(id)
	{
		var result = {};
		$.ajax({ url: path+"muc/get.json", data: "id="+id, dataType: 'json', async: false, success: function(data) {result = data;} });
		return result;
	},

	'set':function(id, fields)
	{
		var result = {};
		$.ajax({ url: path+"muc/set.json", data: "id="+id+"&fields="+JSON.stringify(fields), dataType: 'json', async: false, success: function(data) {result = data;} });
		return result;
	},

	'create':function(address, description)
	{
		var result = {};
		$.ajax({ url: path+"muc/create.json", data: "address="+address+"&description="+description, dataType: 'json', async: false, success: function(data){result = data;} });
		return result;
	},

	'remove':function(id)
	{
		var result = {};
		$.ajax({ url: path+"muc/delete.json", data: "id="+id, dataType: 'json', async: false, success: function(data) {result = data;} });
		return result;
	}

}
