;(function($){
	$(function(){
		var elementWidth;
		var wrapperWidth;
		var items_wrapper = $('.b-popular__items');
		var position = 0; // Базовая позиция = 0, но можно задать

		function resize() {
			wrapperWidth = $('.b-popular__items-wrapper').innerWidth();
			elementWidth = wrapperWidth / window.onPage;
			$('.b-popular__items-item').width(elementWidth);
			items_wrapper.animate({'margin-left': "-" + position * elementWidth},0);
		}
		resize();
		$(window).resize(resize);

		$("ul.b-popular__items img").lazyload({
			threshold : elementWidth,
			event: "scroll:myarusel"
		});

		var prev_control = $('.b-popular__control_prev');
		var next_control = $('.b-popular__control_next');

		prev_control.hover(function(){$(this).addClass('b-popular__control_prev_hover')}, function(){$(this).removeClass('b-popular__control_prev_hover')});
		next_control.hover(function(){$(this).addClass('b-popular__control_next_hover')}, function(){$(this).removeClass('b-popular__control_next_hover')});
		
		var total_items = $('.b-popular__items-item').size();
		
		function movePopular(to){
			if (to != 0) {
				position += to;
				items_wrapper.animate({'margin-left': "-" + position * elementWidth}, 200, function () {
					$(document).trigger('scroll:myarusel');
				});
			}
			
			(position == 0 ? prev_control.hide() : prev_control.show());
			(position >= total_items - window.onPage ?  next_control.hide() : next_control.show());
		}
		
		prev_control.click(function(){
			movePopular(-1);
		});
		next_control.click(function(){
			movePopular(1);
		});
		
		movePopular(0);
	});
})(jQuery);