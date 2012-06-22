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

file_put_contents('documents/' . date('Ymd-His', time()) . '.html', $finalPageText);

if(preg_match ('/No appointments available/msU', $finalPageText)) {
	echo date('Y-m-d H:i:s', time()) . ": No appointments\n";
} else if(preg_match ('/choose an appointment/msU', $finalPageText)) {
    printAppointmentsFromDocument($finalPageText);
    echo "\n";
} else {
	echo date('Y-m-d H:i:s', time()) . ": Some sort of error\n";
}

function printAppointmentsFromDocument($documentString) {
    $locations = array('Croydon', 'Sheffield', 'Birmingham (Solihull)');
    $document = new DOMDocument();
    $document->loadHTML($documentString);
    // Get collections of all relevant appointment data
    $xpath = new DomXPath($document);
    printAppointmentsForLocations($xpath, $locations);
}

function printAppointmentsForLocations(DomXPath $xpath, $locations) {
    foreach($locations as $location) {
        $appointmentCells = $xpath->query("descendant-or-self::*[@summary = 'Available appointments at " . $location . "']/tr/td");
        
        if($appointmentCells->length > 0) {
            echo $location . " appointments:\n";
            printAppointments($appointmentCells);
        }
    }
}

function printAppointments(DOMNodeList $appointmentCells) {
    // Create array of data
    $appointmentData = array();
    foreach($appointmentCells as $cell) {
        $appointmentData[] = $cell->nodeValue;
    }
    // Split data into chunks of 4
    $appointmentChunks = array_chunk($appointmentData, 4);
    // Print each of the chunks
    foreach($appointmentChunks as $chunk) {
        echo $chunk[0] . ' ' . $chunk[1] . ' at ' .$chunk[2] . "\n";

        if(preg_match('/1[3-7].*August/', $chunk[1])) {
            sendEmailAbout($chunk[1]);
        }
    }
}

function sendEmailAbout($apptDate) {
    mail ( 'robin@robinwinslow.co.uk', 'Appointment: ' . $apptDate, 'https://apply.ukba.homeoffice.gov.uk/secure/protected/account');
}

