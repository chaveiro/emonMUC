<?php global $path, $session, $user; ?>
<style>
	a.anchor{display: block; position: relative; top: -50px; visibility: hidden;}
</style>

<h2><?php echo _('Multi Utility Communication Controller API'); ?></h2>
<h3><?php echo _('Apikey authentication'); ?></h3>
<p><?php echo _('If you want to call any of the following actions when you\'re not logged in you have this options to authenticate with the API key:'); ?></p>
<ul><li><?php echo _('Append on the URL of your request: &apikey=APIKEY'); ?></li>
<li><?php echo _('Use POST parameter: "apikey=APIKEY"'); ?></li>
<li><?php echo _('Add the HTTP header: "Authorization: Bearer APIKEY"'); ?></li></ul>
<p><b><?php echo _('Read only:'); ?></b><br>
<input type="text" style="width:255px" readonly="readonly" value="<?php echo $user->get_apikey_read($session['userid']); ?>" />
</p>
<p><b><?php echo _('Read & Write:'); ?></b><br>
<input type="text" style="width:255px" readonly="readonly" value="<?php echo $user->get_apikey_write($session['userid']); ?>" />
</p>

<h3><?php echo _('Available HTML URLs'); ?></h3>
<table class="table">
	<tr><td><?php echo _('The MUC Controller list view'); ?></td><td><a href="<?php echo $path; ?>muc/view"><?php echo $path; ?>muc/view</a></td></tr>
	<tr><td><?php echo _('This page'); ?></td><td><a href="<?php echo $path; ?>muc/api"><?php echo $path; ?>muc/api</a></td></tr>
</table>

<h3><?php echo _('Available JSON commands'); ?></h3>
<p><?php echo _('To use the json api the request url needs to include <b>.json</b>'); ?></p>

<p><b><?php echo _('MUC Controller actions'); ?></b></p>
<table class="table">
	<tr><td><?php echo _('List MUC Controllers'); ?></td><td><a href="<?php echo $path; ?>muc/list.json"><?php echo $path; ?>muc/list.json</a></td></tr>
	<tr><td><?php echo _('Get MUC Controller details'); ?></td><td><a href="<?php echo $path; ?>muc/get.json?id=1"><?php echo $path; ?>muc/get.json?id=1</a></td></tr>
	<tr><td><?php echo _('Register new MUC Controller'); ?></td><td><a href="<?php echo $path; ?>muc/create.json?address=http://localhost/&description=Basement"><?php echo $path; ?>muc/create.json?address=http://localhost/&description=Basement</a></td></tr>
	<tr><td><?php echo _('Delete existing MUC Controller'); ?></td><td><a href="<?php echo $path; ?>muc/delete.json?id=1"><?php echo $path; ?>muc/delete.json?id=1</a></td></tr>
	<tr><td><?php echo _('Update MUC Controller fields'); ?></td><td><a href="<?php echo $path; ?>muc/set.json?id=1&fields={%22address%22:%22http://localhost/%22,%22description%22:%22Basement%22,%22password%22:%22new password%22}"><?php echo $path; ?>muc/set.json?id=0&fields={"address":"http://localhost/","description":"Basement","password":"new password"}</a></td></tr>
</table>