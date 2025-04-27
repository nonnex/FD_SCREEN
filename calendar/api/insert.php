<?php
include("../config.php");

if (isset($_POST['title'])) {

    //collect data
    $error      = null;
    $title      = $_POST['title'];
    $start      = $_POST['startDate'];
    $end        = $_POST['endDate'];
    $color      = $_POST['color'];
    $text_color = $_POST['text_color'];

    //validation
    if ($title == '') {
        $error['title'] = 'Text ist erforderlich';
    }

    if ($start == '') {
        $error['start'] = 'Startdatum ist erforderlich';
    }
	
	// Not mandatory for fullDay
    //if ($end == '') {
    //    $error['end'] = 'End date is required';
    //}

    //if there are no errors, carry on
    if(!isset($error)) {

        //format date
        $start = date('Y-m-d H:i:s', strtotime($start));
        $end = ($end) ? date('Y-m-d H:i:s', strtotime($end)) : null;
        
        $data['success'] = true;
        $data['message'] = 'Success!';

        //store
        $insert = [
            'title'       => $title,
            'start_event' => $start,
            'end_event'   => $end,
            'color'       => $color,
            'text_color'  => $text_color
        ];
        $db->insert(CALENDAR_TABLE, $insert);
      
    } else {
        $data['success'] = false;
        $data['errors'] = $error;
    }

    echo json_encode($data);
}
