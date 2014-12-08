<?php


class zigcookie_admincallbacks
{


	public function __construct($zigaction) {
		if ($zigaction == 'zigcookie-admin-options-update') { $this->update_options(); }
	}


	public function update_options() {
		if (!current_user_can('manage_options')) { wp_die('You are not allowed to do this.'); }
		global $zigcookie;
		check_admin_referer('zigpress_nonce');
		$zigcookie->options['theme'] = htmlspecialchars(@$zigcookie->params['theme']);
		$zigcookie->options['position'] = htmlspecialchars(@$zigcookie->params['position']);
		$zigcookie->options['notice'] = htmlspecialchars(@$zigcookie->params['notice']);
		$zigcookie->options['privacy'] = htmlspecialchars(@$zigcookie->params['privacy']);
		$zigcookie->options['privacyurl'] = htmlspecialchars(@$zigcookie->params['privacyurl']);
		$zigcookie->options['accept'] = htmlspecialchars(@$zigcookie->params['accept']);
		$zigcookie->options['delete_options_next_deactivate'] = (@$zigcookie->params['delete_options_next_deactivate'] == '1') ? '1' : '0';
		# re-save options
		update_option("zigcookie", $zigcookie->options);
		$zigcookie->result = 'OK|Options saved.'; 
		if (ob_get_status()) ob_clean();
		wp_redirect($_SERVER['PHP_SELF'] . '?page=zigcookie-options&r=' . base64_encode($zigcookie->result));
		exit();
	}


}


# EOF
