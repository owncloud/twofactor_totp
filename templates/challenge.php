<?php
script('core', 'login');
script('twofactor_totp', 'challenge');
style('twofactor_totp', 'challenge');
?>

<?php if(!$_['isConfigured']): ?>
<div class="grouptop" style="align-items:center;">
	<p class="info"><?php p($l->t('Scan the QR code below with your TOTP app and enter the code')); ?></p>
	<img src="<?php p($_['qr']); ?>" />
	<p class="info"><?php p($l->t('This is your new TOTP secret:')); ?>
		<strong id="totp-secret" class="totp-secret"><?php p($_['secret']); ?></strong>
		<button type="button" id="totp-copy-secret" class="totp-copy-secret" data-copied-label="<?php p($l->t('Copied')); ?>" hidden><?php p($l->t('Copy')); ?></button>
	</p>
</div>
<?php endif; ?>
<form method="POST" name="login">
	<div class="grouptop">
		<input type="text" name="challenge" required="required" autofocus autocomplete="off" autocapitalize="off">
	</div>
    <div class="submit-wrap">
        <button type="submit" id="submit" class="login-button">
            <span><?php p($l->t('Verify')); ?></span>
			<div class="loading-spinner"><div></div><div></div><div></div><div></div></div>
        </button>
    </div>
</form>
