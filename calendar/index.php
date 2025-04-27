<?php require('config.php');?>
<!DOCTYPE html>
<html>
<head>
    <title>Calandar</title>
	<link href="https://fonts.googleapis.com/css?family=Inconsolata" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/jquery-ui@1.13.2/dist/themes/base/jquery-ui.min.css" rel="stylesheet">
<!--<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">-->
	<link href="https://cdn.jsdelivr.net/npm/bootswatch@5.2.3/dist/slate/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/jquery-datetimepicker@2.5.21/jquery.datetimepicker.min.css" rel="stylesheet">
<!--<link href='<?=$dir;?>packages/colorselect/colorselect.css' rel='stylesheet' />-->
    <link href='<?=$dir;?>style.css' rel='stylesheet' />
	
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.3/dist/jquery.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/jquery-ui@1.13.2/dist/jquery-ui.min.js'></script>

<!--	<script src='https://cdn.jsdelivr.net/npm/rrule@2.6.4/dist/es5/rrule.min.js'></script><!-- rrule lib -->
<script src="https://cdn.jsdelivr.net/npm/rrule@2.7.1/dist/es5/rrule.min.js"></script>

	<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.4/index.global.min.js"></script>
	<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.4/locales/de.global.min.js'></script>
	<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/rrule@6.1.4/index.global.min.js'></script><!-- rrule-to-fullcalendar connector. must go AFTER the rrule lib -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/jquery-datetimepicker@2.5.21/build/jquery.datetimepicker.full.min.js"></script>
<!--<script src="https://cdn.jsdelivr.net/npm/jquery-ui-timepicker-addon@1.6.3/dist/jquery-ui-timepicker-addon.min.js"></script>-->
<!--<script src='<?=$dir;?>packages/colorselect/colorselect.js'></script>-->
    <script src='<?=$dir;?>calendar.js'></script>
	
	<link href='../css/style.css' rel='stylesheet' />
</head>
<body>
<div class="modal fade" id="addeventmodal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
			<!-- Modal Header -->
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Termin hinzufügen</h1>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
			<!-- Modal Body -->
            <div class="modal-body">
                <div class="container-fluid">
                    <form id="createEvent" class="form-horizontal">
						<div class="row">
							<div class="col-md-6">
								<div id="title-group" class="form-group">
									<label class="control-label" for="title">Text</label>
									<input type="text" class="form-control" name="title">
									<!-- errors will go here -->
								</div>
								<div id="date-group" class="form-group">
									<label class="control-label" for="startDate">Startdatum</label>
									<input type="text" class="form-control datetimepicker" id="startDate" name="startDate" autocomplete="off">
									<!-- errors will go here -->
								</div>
								<div id="enddate-group" class="form-group">
									<label class="control-label" for="endDate">Enddatum (Nur angeben wenn mehrtägig)</label>
									<input type="text" class="form-control datetimepicker" id="endDate" name="endDate" autocomplete="off">
									<!-- errors will go here -->
								</div>
							</div>
							<div class="col-md-6">
								<div id="edit-color-group" class="form-group">
								<!--
									<label class="control-label" for="editColor">Colour</label>
									<input type="text" id="colorpicker" class="form-control colorpicker" id="editColor" name="editColor" value="#6453e9">
								-->
									<input type="hidden" id="colorpicker" class="form-control colorpicker" id="color" name="color" value="#6453e9">
									<!-- errors will go here -->
								</div>
								<div id="edit-textcolor-group" class="form-group">
								<!--
									<label class="control-label" for="editTextColor">Text Colour</label>
									<input type="text" id="colorpicker" class="form-control colorpicker" id="editTextColor" name="editTextColor" value="#ffffff">
								-->
									<input type="hidden" id="colorpicker" class="form-control colorpicker" id="text_color" name="text_color" value="#ffffff">
									<!-- errors will go here -->
								</div>
								
									
<!--									
				<div class="dropdown">
					<button class="btn _select_color dropdown-toggle" type="button" id="dropdownMenu1" data-bs-toggle="dropdown" aria-expanded="false">
						Green<span class="caret _right"></span>
						<span _text_display="Green" class="color green"></span>
					</button>
					<ul class="dropdown-menu _select_color_drop" aria-labelledby="dropdownMenu1">
						<li><span _text_display="Green" class="color green"></span></li>
						<li><span _text_display="Red" class="color red"></span></li>
						<li><span _text_display="Yellow" class="color yellow"></span></li>
						<li><span _text_display="Brown" class="color brown"></span></li>
						<li><span _text_display="Orange" class="color orange"></span></li>
						<li><span _text_display="Pink" class="color pink"></span></li>
						<li><span _text_display="Silver" class="color silver"></span></li>
						<li><span _text_display="Bule" class="color blue"></span></li>
						<li><span _text_display="TEAL" class="color TEAL"></span></li>
						<li><span _text_display="NAVY" class="color NAVY"></span></li>
						<li><span _text_display="PURPLE" class="color PURPLE"></span></li>
						<li><span _text_display="OLIVE" class="color OLIVE"></span></li>
						<li><span _text_display="LIME" class="color LIME"></span></li>
						<input type="hidden" name="_color" value="Green">
					</ul>
				</div>									
-->

							</div>
						</div>
					</div>
				</div>
				<!-- Modal Footer -->
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary btn-block">Speichern</button>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
				</div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" id="editeventmodal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
			<!-- Modal Header -->
            <div class="modal-header">
				<h1 class="modal-title fs-5" id="exampleModalLabel">Termin bearbeiten</h1>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
			<!-- Modal Body -->
            <div class="modal-body">
                <div class="container-fluid">
                    <form id="editEvent" class="form-horizontal">
						<input type="hidden" id="editEventId" name="editEventId" value="">
						<div class="row">
							<div class="col-md-6">
								<div id="edit-title-group" class="form-group">
									<label class="control-label" for="editEventTitle">Text</label>
									<input type="text" class="form-control" id="editEventTitle" name="editEventTitle">
									<!-- errors will go here -->
								</div>
								<div id="date-group" class="form-group">
									<label class="control-label" for="editStartDate">Startdatum</label>
									<input type="text" class="form-control datetimepicker" id="editStartDate" name="editStartDate" autocomplete="off">
									<!-- errors will go here -->
								</div>
								<div id="edit-enddate-group" class="form-group">
									<label class="control-label" for="editEndDate">Enddatum (Nur angeben wenn mehrtägig)</label>
									<input type="text" class="form-control datetimepicker" id="editEndDate" name="editEndDate" autocomplete="off">
									<!-- errors will go here -->
								</div>
							</div>
							<div class="col-md-6">
								<div id="edit-color-group" class="form-group">
								<!--
									<label class="control-label" for="editColor">Colour</label>
									<input type="text" id="colorpicker" class="form-control colorpicker" id="editColor" name="editColor" value="#6453e9">
								-->
									<input type="hidden" id="colorpicker" class="form-control colorpicker" id="editColor" name="editColor" value="#6453e9">
									<!-- errors will go here -->
								</div>
								<div id="edit-textcolor-group" class="form-group">
								<!--
									<label class="control-label" for="editTextColor">Text Colour</label>
									<input type="text" id="colorpicker" class="form-control colorpicker" id="editTextColor" name="editTextColor" value="#ffffff">
								-->
									<input type="hidden" id="colorpicker" class="form-control colorpicker" id="editTextColor" name="editTextColor" value="#ffffff">
									<!-- errors will go here -->
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- Modal Footer -->
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary btn-block">Speichern</button>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
					<button type="button" class="btn btn-danger" id="deleteEvent">Löschen</button>
				</div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="cal-container">
    <div id="calendar"></div>
</div>

</body>
</html>