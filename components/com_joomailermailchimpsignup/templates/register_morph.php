<?php
/**
 * Copyright (C) 2015  freakedout (www.freakedout.de)
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 **/

defined('_JEXEC') or die('Restricted access'); ?>

<div id="user-register">
<script type="text/javascript">
<!--
Window.onDomReady(function(){
    document.formvalidator.setHandler('passverify', function (value) { return ($('password').value == value); }	);
});
// -->
</script>

<?php
if(isset($this->message)){
    $this->display('message');
}
?>
<div id="login-wrap">
<h1><?php echo JText::_('Registration');?></h1>
<p class="login-description"><?php echo JText::_('REGISTER_REQUIRED');?></p>

<form action="<?php echo JRoute::_( 'index.php?option=com_user' ); ?>" method="post" id="josForm" name="josForm" class="form-validate">

<ul>
    <li class="label">
	<label id="namemsg" for="name"><?php echo JText::_( 'Name' ); ?> <span class="req">*</span> </label>
    </li>
    <li>
	<input type="text" name="name" id="name" size="40" value="<?php echo $this->escape($this->user->get( 'name' ));?>" class="inputbox required" maxlength="50" />
    </li>
    <li class="label">
	<label id="usernamemsg" for="username"><?php echo JText::_( 'User name' ); ?> <span class="req">*</span> </label>
    </li>
    <li>
	<input type="text" id="username" name="username" size="40" value="<?php echo $this->escape($this->user->get( 'username' ));?>" class="inputbox required validate-username" maxlength="25" />
    </li>
    <li class="label">
	<label id="emailmsg" for="email"><?php echo JText::_( 'Email' ); ?> <span class="req">*</span> </label>
    </li>
    <li>
	<input type="text" id="email" name="email" size="40" value="<?php echo $this->escape($this->user->get( 'email' ));?>" class="inputbox required validate-email" maxlength="100" />
    </li>
    <li class="label">
	<label id="pwmsg" for="password"><?php echo JText::_( 'Password' ); ?> <span class="req">*</span> </label>
    </li>
    <li>
	<input class="inputbox required validate-password" type="password" id="password" name="password" size="40" value="" />
    </li>
    <li class="label">
	<label id="pw2msg" for="password2"><?php echo JText::_( 'Verify Password' ); ?> <span class="req">*</span> </label>
    </li>
    <li>
	<input class="inputbox required validate-passverify" type="password" id="password2" name="password2" size="40" value="" />
    </li>

<?php echo $this->ihtml; ?>

    <li class="login-btn">
	<button class="button validate" type="submit"><?php echo JText::_('Register'); ?></button>
    </li>
</ul>
<input type="hidden" name="task" value="register_save" />
<input type="hidden" name="id" value="0" />
<input type="hidden" name="gid" value="0" />
<?php echo JHTML::_( 'form.token' ); ?>
</form>

</div>
</div>
