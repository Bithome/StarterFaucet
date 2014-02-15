<?php
// Starter Faucet index page.
// This is currently the only page, the post request for the coins have been put into this page as well as the form.
// However this page is a little messy coded, maybe could be cleaned up just a little.
require_once('functions/loader.php');
$loader = new loader();
$api = $loader->load('coinarea');
$template = $loader->load('template');
$config = $loader->load('configuration');
if (isset($_GET['next'])) {
	$useraddr = $_POST['address'];
	$terms = $_POST['terms'];
	if (!empty($terms) && !empty($terms)) {
		if ($config->enable_captcha()) {
			require_once('functions/recaptchalib.php');
			$resp = recaptcha_check_answer($config->recaptcha_private_key(), $_SERVER['REMOTE_ADDR'], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
			if ($resp->is_valid) {
				$continue = true;
			}
		} else {
			$continue = true;
		}
		if ($continue) {
			$amount = $config->faucet_amount();
			$address = $config->faucet_address();
			$getbalance = $api->get_balance($address);
			if ($getbalance['balance'] >= $amount) {
				$log = $loader->load('log');
				if ($log->checkWrite()) {
					$send = $api->send_funds($useraddr, $amount);
					if ($send['success']) {
						$msg = 'Successful, you should see the funds in your wallet shortly.';
					} else {
						$msg = 'Your funds were unable to be sent, maybe due to an incorrect address or an issue with the set up of this faucet. Contact the faucet owner for support.';
					}
				} else {
					$msg = 'You must wait more time until you can receive funds again.';
				}
			} else {
				$msg = 'There are currently not enough funds in the faucet.';
			}
		} else {
			$msg = 'The captcha is incorrect, please try again.';
		}
	} else {
			$msg = 'Please fill out the address and agree to the terms of service.';
		}
}
$template->header();
echo '<div>'.$msg.'</div>
<form action="index.php?next" method="post">
<table style="width:75%">
<tr>
<td align="right">'.$config->coin_name().' Balance:</td>';
if ($config->show_balance()) {
	$address = $config->faucet_address();
	$getbalance = $api->get_balance($address);
	if ($getbalance['success']) {
		$balance = $getbalance['balance'];
	} else {
		$balance = 'Unknown';
	}
	echo '<td>'.$balance.' '.$config->coin_code().'</td>';
} else {
	echo '<td></td>';
}
if (!empty($_POST['terms'])) {
	$checked = ' checked="checked"';
}
echo '</tr>
<tr>
<td align="right">'.$config->coin_name().' Address:</td>
<td><input type="text" name="address" maxlength="100" style="width:300px" value="'.htmlspecialchars($useraddr, ENT_QUOTES).'"/></td>
</tr>
<tr>
<td align="right">Terms of Service</td>
<td><input id="terms" type="checkbox" name="terms"'.$checked.'/><label for="terms">I agree to the <a href="terms.php" target="_blank">Terms of Service</a></label></td>
</tr>';
if ($config->enable_captcha()) {
	require_once('functions/recaptchalib.php');
	echo '<tr>
	<td></td>
	<td>'.recaptcha_get_html($config->recaptcha_public_key()).'</td>
	</tr>';
}
echo '<tr>
<td></td>
<td><input type="submit" value="Send '.$config->coin_name().'"/></td>
</tr>
</form>
</table>';
$template->footer();
?>