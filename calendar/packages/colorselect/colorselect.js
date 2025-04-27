document.addEventListener('DOMContentLoaded', function() 
{
	_colors = $('._select_color_drop li');

	for (var i = _colors.length - 1; i >= 0; i--) 
	{
		$(_colors[i]).click(function()
		{
			var color_text = $(this).find('span').attr('_text_display');
			var elemnt = $(this).closest('._select_color_drop').prev();
			elemnt.find('span.color').remove();
			$(this).find('span').clone().appendTo(elemnt);
			var contents = $(elemnt).contents();
			if (contents.length > 0) 
			{
				if (contents.get(0).nodeType == Node.TEXT_NODE) 
				{
					$(elemnt).html(color_text).append(contents.slice(1));
				}
			}
			if($('[name=_color]').val() == undefined)
			{
				elemnt.next().append("<input type='hidden' name='_color' value='"+color_text+"'>");
			}
			else
			{
				$('[name=_color]').val(color_text);
			}
		})
	};
});