document.addEventListener('DOMContentLoaded', function() 
{
    var url ='./';
/*
    $('body').on('click', '.datetimepicker', function() 
	{
        $(this).not('.hasDateTimePicker').datetimepicker({
            controlType: 'select',
            changeMonth: true,
            changeYear: true,
            dateFormat: "dd-mm-yy",
            timeFormat: 'HH:mm:ss',
            yearRange: "1900:+10",
            showOn:'focus',
            firstDay: 1
        }).focus();
    });
*/
	$('body').on('click', '.datetimepicker', function(event) 
	{
        $(this).not('.hasDateTimePicker').datepicker().focus();
    });
/*
	$(function() 
	{
		$("#datepicker").datepicker();
	});
 */
	const calendarEl = document.getElementById('calendar')
	const calendar = new FullCalendar.Calendar(calendarEl, 
	{
		locale: 'de',
        headerToolbar: 
		{
            left: 'prev,today,next',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },
		footerToolbar: 
		{
            left: 'prev,today,next',
            center: '',
            right: ''
        },
        navLinks: true, // can click day/week names to navigate views
        businessHours: true, // display business hours
        editable: true,
        //defaultDate: '2020-04-07', //uncomment to have a default date
        events: url+'api/load.php',
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
            var start = arg.event.start.toDateString()+' '+arg.event.start.getHours()+':'+arg.event.start.getMinutes()+':'+arg.event.start.getSeconds();
            var end = arg.event.end.toDateString()+' '+arg.event.end.getHours()+':'+arg.event.end.getMinutes()+':'+arg.event.end.getSeconds();

            $.ajax({
              url:url+"api/update.php",
              type:"POST",
              data:{id:arg.event.id, start:start, end:end},
            });
        },
        eventClick: function(arg) 
		{
			$('#editEventTitle').val(arg.event.title);
			$('#editStartDate').val(arg.event.start);
			$('#editEndDate').val(arg.event.end);
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
							$('#editeventmodal').modal('hide'); //close dialog    
							//calendar.refetchEvents(); //refresh calendar    
							location.reload();
						}
                    }); 
                }
            });
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
                if (data.errors.date) 
				{
                    $('#date-group').addClass('has-error');
                    $('#date-group').append('<div class="help-block">' + data.errors.date + '</div>');
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
                if (data.errors.date) 
				{
                    $('#date-group').addClass('has-error');
                    $('#date-group').append('<div class="help-block">' + data.errors.date + '</div>');
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