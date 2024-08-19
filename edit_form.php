<?php

class block_mad2api_edit_form extends block_edit_form {
	protected function specific_definition($mform) {
		// Section header title according to language file.
		// $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

		// // It set's the API url that plugin will send data.
		// $mform->addElement('text', 'api_url', get_string('apiurl', 'block_mad2api'));
		// $mform->setDefault('api_url', 'http://api.lanse.prd.apps.kloud.rnp.br/');
		// $mform->setType('api_url', PARAM_TEXT);
	}
}
