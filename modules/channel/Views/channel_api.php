<?php global $path, $session, $user; ?>
<style>
  a.anchor{display: block; position: relative; top: -50px; visibility: hidden;}
</style>

<h2><?php echo _('Channel API'); ?></h2>
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
    <tr><td><?php echo _('The channel list view'); ?></td><td><a href="<?php echo $path; ?>channel/view"><?php echo $path; ?>channel/view</a></td></tr>
    <tr><td><?php echo _('This page'); ?></td><td><a href="<?php echo $path; ?>channel/api"><?php echo $path; ?>channel/api</a></td></tr>
</table>

<h3><?php echo _('Available JSON commands'); ?></h3>
<p><?php echo _('To use the json api the request url needs to include <b>.json</b>'); ?></p>

<p><b><?php echo _('Channel actions'); ?></b></p>
<table class="table">
	<tr><td><?php echo _('Create new channel'); ?></td><td><a href="<?php echo $path; ?>channel/create.json?mucid=1&name=dummy"><?php echo $path; ?>channel/create.json?mucid=1&name=dummy</a></td></tr>
	<tr><td><?php echo _('List channels'); ?></td><td><a href="<?php echo $path; ?>channel/list.json"><?php echo $path; ?>channel/list.json</a></td></tr>
	<tr><td><?php echo _('List channel states'); ?></td><td><a href="<?php echo $path; ?>channel/states.json"><?php echo $path; ?>channel/states.json</a></td></tr>
	<tr><td><?php echo _('Get channel information'); ?></td><td><a href="<?php echo $path; ?>channel/info.json?mucid=1&name=dummy"><?php echo $path; ?>channel/info.json?mucid=1&name=dummy</a></td></tr>
	<tr><td><?php echo _('Get channel details'); ?></td><td><a href="<?php echo $path; ?>channel/get.json?mucid=1&name=dummy"><?php echo $path; ?>channel/get.json?mucid=1&name=dummy</a></td></tr>
	<tr><td><?php echo _('Update channel configuration'); ?></td><td><a href="<?php echo $path; ?>channel/update.json?mucid=1&name=dummy&channel={%22disabled%22:%22true%22}""><?php echo $path; ?>channel/update.json?mucid=1&name=dummy&channel={"disabled":"true"}</a></td></tr>
	<tr><td><?php echo _('Delete existing channel'); ?></td><td><a href="<?php echo $path; ?>channel/delete.json?mucid=1&name=dummy"><?php echo $path; ?>channel/delete.json?mucid=1&name=dummy</a></td></tr>
</table>