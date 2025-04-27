// RDY START
$(document).ready(function()
{
	var default_message_for_dialog = 'Daten senden?';
	
	$("#dialog").dialog(
	{
		modal: true,
		bgiframe: true,
		width: 300,
		height: 200,
		autoOpen: false,
		title: 'Bestätigung'
	});

	// LINK
	$("a.confirm").click(function(link) 
	{
        link.preventDefault();
        var theHREF = $(this).attr("href");
		var theREL = $(this).attr("rel");
		var theMESSAGE = (theREL == undefined || theREL == '') ? default_message_for_dialog : theREL;
		var theICON = '<span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 0 0;"></span>';
		
		// set windows content
		$('#dialog').html('<P>' + theICON + theMESSAGE + '</P>');
		
		$("#dialog").dialog('option', 'buttons', 
		{
			"OK" : function() 
			{
				window.location.href = theHREF;
			},
			"Abbrechen" : function() 
			{
				$(this).dialog("close");
			}
		});
		$("#dialog").dialog("open");
	});

	
	// FORMS
	$('input.confirm').click(function(theINPUT)
	{
		theINPUT.preventDefault();
		var theFORM = $(theINPUT.target).closest("form");
		var theREL = $(this).attr("rel");
		var theMESSAGE = (theREL == undefined || theREL == '') ? default_message_for_dialog : theREL;
		var theICON = '<span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 0 0;"></span>';
		
		$('#dialog').html('<P>' + theICON + theMESSAGE + '</P>');
		$("#dialog").dialog('option', 'buttons', 
		{
			"OK" : function() 
			{
				theFORM.submit();
			},
			"Abbrechen" : function() 
			{
				$(this).dialog("close");
			}
		});
		$("#dialog").dialog("open");
	});

});