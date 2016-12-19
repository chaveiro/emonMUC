var muc_dialog =
{
	mucid: 0,

	'loadAdd': function(){
		
		this.drawAdd();
	},

	'loadDelete': function(mucid, tablerow){
		this.mucid = mucid;
		
		this.drawDelete(tablerow);
	},

	'drawAdd':function(){
		$("#mucAddModal").modal('show');
		
		$("#mucadd-save").off('click').on('click', function (){
			$('#mucadd-loader').show();
			
			var mucaddaddress = $('#mucadd-address').val();
			var mucadddescription = $('#mucadd-description').val();
			
			var result = muc.create(mucaddaddress,mucadddescription);
			$('#mucadd-loader').hide();
			
			mucid = result.id;
			if (!result.success || mucid<1) {
				alert('MUC could not be created:\n'+result.message);
				return false;
			}
			else {
				update();
				$('#mucAddModal').modal('hide');
			}
		});
	},

	'drawDelete':function(row){
		$("#mucDeleteModal").modal('show');
		
		$("#mucdelete-confirm").off('click').on('click', function() {
			$('#mucdelete-loader').show();
			var result = muc.remove(muc_dialog.mucid);
			$('#mucdelete-loader').hide();
			
			if (!result.success) {
				alert('Unknown error while deleting muc');
				return false;
			}
			else {
			    table.remove(row);
				update();
				$('#mucDeleteModal').modal('hide');
			}
		});
	}
}
