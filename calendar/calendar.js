document.addEventListener('DOMContentLoaded', function() 
{
    var url ='./';

	//DOC https://xdsoft.net/jqplugins/datetimepicker/
	$('body').on('click', '.datetimepicker', function(event) 
	{
		$.datetimepicker.setLocale('de');
        $(this).not('.hasDateTimePicker').datetimepicker({
			timepicker: 	false,
			format: 		'd.m.Y',
			dayOfWeekStart: 1,
			todayButton: 	true,
			defaultSelect: 	false,
			weeks: 			true,
			theme: 			'dark',
		}).focus();
    });

	// Feiertage BY (JSON) https://feiertage-api.de/api/?jahr=2025&nur_land=By
	
	// DOC https://fullcalendar.io/docs
	const calendarEl = document.getElementById('calendar')
	const calendar = new FullCalendar.Calendar(calendarEl, 
	{
		locale: 				'de',
		timeZone: 				'local',
		height:					680,
		weekNumbers: 			true,
		themeSystem: 			'bootstrap5',
		longPressDelay: 		200,
		eventLongPressDelay: 	200,
		selectLongPressDelay: 	200,
		weekends:				false,
		nowIndicator: 			true,
        navLinks: 				false, // can click day/week names to navigate views
        businessHours: 			false, // display business hours
        editable: 				true,
		selectable: 			false,
        //defaultDate: 			'2020-04-07', //uncomment to have a default date
		events: 				url+'api/load.php',
		customButtons: 
		{
			back: 
			{
				text: 'Zurück',
				click: function() {
					location.href='../index.php';
				}
			},
			add: 
			{
				text: '+ Neu',
				click: function() {
					$('#addeventmodal').modal('show');
				}
			}
		},
        headerToolbar: 
		{
            left: 'back prev,today,next add',
            center: 'title',
            right: ''
        },
		footerToolbar: 
		{
            left: 'back prev,today,next',
            center: '',
            right: ''
        },
        eventDrop: function(arg) 
		{
            var start = arg.event.start.toDateString()+' '+arg.event.start.getHours()+':'+arg.event.start.getMinutes()+':'+arg.event.start.getSeconds();
            if (arg.event.end == null) {
                end = start;
            } else {
                var end = arg.event.end.toDateString()+' '+arg.event.end.getHours()+':'+arg.event.end.getMinutes()+':'+arg.event.end.getSeconds();
            }

            $.ajax({
              url:url+"api/update.php",
              type:"POST",
              data:{id:arg.event.id, start:start, end:end},
            });
        },
        eventResize: function(arg) 
		{
            var start 	= arg.event.start.toDateString()+' '+arg.event.start.getHours()+':'+arg.event.start.getMinutes()+':'+arg.event.start.getSeconds();
            var end 	= arg.event.end.toDateString()+' '+arg.event.end.getHours()+':'+arg.event.end.getMinutes()+':'+arg.event.end.getSeconds();

            $.ajax({
              url:url+"api/update.php",
              type:"POST",
              data:{id:arg.event.id, title:arg.event.title, start:start, end:end},
            });
        },
        eventClick: function(arg) 
		{
			if(arg.el.fcSeg.eventRange.ui.startEditable) {
//console.log('startStr:' + arg.event.startStr + ' | endStr:' + arg.event.endStr);
				$('#editEventId').val(arg.event.id);
				$('#editEventTitle').val(arg.event.title);
				$('#editStartDate').val(arg.event.startStr);
				$('#editEndDate').val(arg.event.endStr);
				$('#editColor').val(arg.event.backgroundColor);
				$('#editTextColor').val(arg.event.textColor);
				$('#editeventmodal').modal('show');

				$('body').on('click', '#deleteEvent', function() 
				{
					if(confirm("Are you sure you want to remove it?")) 
					{
						$.ajax({
							url:url+"api/delete.php",
							type:"POST",
							data:{id:arg.event.id},
							success: function(data) 
							{
								arg.event.remove();
								calendar.refetchEvents(); //refresh calendar
								$('#editeventmodal').modal('hide'); //close dialog
								//location.reload();
							}
						}); 
					}
				});
			}
        }
    });

    calendar.render();

    $('#createEvent').submit(function(event) 
	{
        event.preventDefault(); // stop the form refreshing the page

        $('.form-group').removeClass('has-error'); // remove the error class
        $('.help-block').remove(); // remove the error text

        // process the form
        $.ajax({
            type        : "POST",
            url         : url+'api/insert.php',
            data        : $(this).serialize(),
            dataType    : 'json',
            encode      : true
        })
		.done(function(data) 
		{
            if (data.success) 
			{
                $('#createEvent').trigger("reset"); //remove any form data
                $('#addeventmodal').modal('hide'); //close dialog
                calendar.refetchEvents(); //refresh calendar
            } 
			else 
			{
                //if error exists update html
                if (data.errors.start) 
				{
                    $('#date-group').addClass('has-error');
                    $('#date-group').append('<div class="help-block">' + data.errors.start + '</div>');
                }

                if (data.errors.title) 
				{
                    $('#title-group').addClass('has-error');
                    $('#title-group').append('<div class="help-block">' + data.errors.title + '</div>');
                }
            }
        });
    });

    $('#editEvent').submit(function(event) 
	{
        event.preventDefault(); // stop the form refreshing the page

        $('.form-group').removeClass('has-error'); // remove the error class
        $('.help-block').remove(); // remove the error text

        //form data
        var id 			= $('#editEventId').val();
        var title 		= $('#editEventTitle').val();
        var start 		= $('#editStartDate').val();
        var end 		= $('#editEndDate').val();
        var color 		= $('#editColor').val();
        var textColor	= $('#editTextColor').val();

        // process the form
        $.ajax({
            type        : "POST",
            url         : url+'api/update.php',
            data        : 
			{
                id:id, 
                title:title, 
                start:start,
                end:end,
                color:color,
                text_color:textColor
            },
            dataType    : 'json',
            encode      : true
        })
		.done(function(data) 
		{
            // insert worked
            if (data.success) 
			{
                $('#editEvent').trigger("reset"); //remove any form data
                $('#editeventmodal').modal('hide'); //close dialog

                //refresh calendar
                calendar.refetchEvents();
            }
			else
			{
                //if error exists update html
                if (data.errors.start) 
				{
                    $('#date-group').addClass('has-error');
                    $('#date-group').append('<div class="help-block">' + data.errors.start + '</div>');
                }

                if (data.errors.title) 
				{
                    $('#title-group').addClass('has-error');
                    $('#title-group').append('<div class="help-block">' + data.errors.title + '</div>');
                }
            }
        });
    });
});