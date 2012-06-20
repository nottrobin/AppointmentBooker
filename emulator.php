<?php

error_reporting (E_ERROR);

require('simpletest/browser.php');

$settingsJson = file_get_contents("credentials.json");
$settings     = json_decode($settingsJson,true);

$browser = new SimpleBrowser();

$browser->get('https://apply.ukba.homeoffice.gov.uk/secure/protected/account');
$browser->setField('j_username', $settings['username']);
$browser->setField('j_password', $settings['password']);
$browser->click('Log in');

$pageText = $browser->getContent();

$numWords = array('first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth');

// Set the memorable word characters
foreach($numWords as $index => $numWord) {
	$matches = array();
	if(preg_match ('/The ' . $numWord . ' character.*<input[^>]+name[=]["]([^"]+)["]/msU', $pageText, $matches)) {
		$browser->setField($matches[1], substr($settings['memorable'], $index, 1));
	}
}

$browser->click('Confirm identity');

$browser->click('Book appointment');

$browser->setField('tabbedQuestions:screens:screen0:control0:j_id184:selectOneListBox', 'T2SPSKWORK');
$browser->click('Next');

$browser->setField('tabbedQuestions:screens:screen0:control1:j_id453:selectOneRadio', 'true');
$browser->click('Next');

//$browser->setField('tabbedQuestions:screens:screen0:control1:j_id490:dateInputControl:day', '31');
//$browser->setField('tabbedQuestions:screens:screen0:control1:j_id490:dateInputControl:month', '6');
//$browser->setField('tabbedQuestions:screens:screen0:control1:j_id490:dateInputControl:year', '2012');
//$browser->setField('tabbedQuestions:screens:screen0:control2:j_id555:dateInputControl:day', '31');
//$browser->setField('tabbedQuestions:screens:screen0:control2:j_id555:dateInputControl:month', '7');
//$browser->setField('tabbedQuestions:screens:screen0:control2:j_id555:dateInputControl:year', '2012');
$browser->setField('tabbedQuestions:screens:screen1:j_id1057:checkbox', 'on');
$browser->setField('tabbedQuestions:screens:screen1:j_id1048:checkbox', 'on');
$browser->setField('tabbedQuestions:screens:screen1:j_id1012:checkbox', 'on');
$browser->click('Next');

$finalPageText = $browser->getContent();

if(preg_match ('/No appointments available/msU', $finalPageText)) {
	echo date('Y-m-d H:i:s', time()) . ": No appointments\n";
} else if(preg_match ('/choose an appointment/msU', $finalPageText)) {
	echo "\n===\n\n" . date('Y-m-d H:i:s', time()) . ": APPOINTMENTS!\n---\n";
	echo $finalPageText;
    echo "\n\n===\n\n";
} else {
	echo date('Y-m-d H:i:s', time()) . ": Some sort of error\n";
}

