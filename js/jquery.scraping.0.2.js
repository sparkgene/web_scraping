/**
 * jQuery Scraping plugin
 * This jQuery plugin display thumbnail image and simple introduction of the enterd url.
 * @name jquery.scraping.0.2.js
 * @author Jun Ichikawa
 * @version 0.2
 * @date Nov 9, 2012
 * @category jQuery plugin
 * @copyright (c) 2012 Jun Ichikawa
 * @example Visit https://github.com/sparkgene/web_scraping
 */


;(function($) {
 
    $.fn.Scraping = function(options) {
 
        var elements = this;
        var opts = $.extend({}, $.fn.Scraping.defaults, options);
 
        elements.each(function() {
        	init($(this), opts);
        	return false;
        });
 
        return this;
    };
 
	var _imageList = null,
	_imageIdx = 0;

    function init($obj, opts) {
		if( opts.phpUrl == null ) return;
		
		$obj.on('blur', function(){
			$('.scraping_url_info_box').remove();

			url = $(this).val();
			if( url == "" ){
				return;
			}
			load_url($(this), opts);
		});
    };

	function load_url( el, settings ){
		$.getJSON(settings.phpUrl, 
				{ target: el.val() }, 
				function(json) {
					if( json.url == undefined)
						return;
					
					var contents = '<div class="scraping_url_info_box">';
					if( json.imgs.length > 0 ){
						_imageList = json.imgs;
						contents += '<div class="scraping_thumbnail_wrapper"></div>';
					}
					contents += '<div class="scraping_url_text_info"><div style="clear:both;"></div>';
					contents += '<div class="scraping_url_title"><a href="' + json.url + '">' + json.title + '</a></div>';					
					if( json.title != json.url ){
						contents += '<div class="scraping_url_link"><a href="' + json.url + '">' + json.url + '</a></div>';					
					}
					contents += '<div class="scraping_url_description">' + json.description + '</div>';
					if( json.imgs.length > 1 ){
						contents += '<div class="scraping_thumbnail_selector">';
						contents += '<button type="button" class="scraping_thumbnail_back">&lt;</button>';
						contents += '<button type="button" class="scraping_thumbnail_next">&gt;</button>';
						contents += '<span class="scraping_thumbnail_pages"></span>';
						contents += '</div>';
					}
					contents += '</div><div style="clear:both;"></div>';
					$(contents).insertAfter(el);
					if( json.imgs.length > 1 ){
						$('.scraping_thumbnail_next').removeAttr('disabled');
						$('.scraping_thumbnail_next').bind("click", show_next);
					}
					else{
						$('.scraping_thumbnail_next').attr('disabled', 'disabled');							
					}
					$('.scraping_thumbnail_back').attr('disabled', 'disabled');
					loadImage(_imageList[_imageIdx]);
			});    		
	}
	
	function show_back(){
		if( _imageList.length <= 1 ){
			$(this).unbind('click').attr('disabled', 'disabled');
			return;
		}
		if( _imageIdx == (_imageList.length - 1)){
			$('.scraping_thumbnail_next').removeAttr('disabled').bind('click', show_next);
		}
		_imageIdx--;
		if( _imageIdx < 0 ){
			_imageIdx = 0;
			$(this).unbind('click').attr('disabled', 'disabled');
			return;
		}
		loadImage(_imageList[_imageIdx]);
	}

	function show_next(){
		if( _imageList.length <= 1 ){
			$(this).unbind('click').attr('disabled', 'disabled');
			return;
		}
		if( _imageIdx == 0 ){
			$('.scraping_thumbnail_back').removeAttr('disabled').bind('click', show_back);
		}
		_imageIdx++;
		if( _imageIdx >= _imageList.length ){
			_imageIdx = _imageList.length - 1;
			$(this).unbind('click').attr('disabled', 'disabled');
			return;
		}

		loadImage(_imageList[_imageIdx]);
	}

	function loadImage(url){
		$('.scraping_thumbnail_pages').html((_imageIdx+1) + '/' + _imageList.length);
		$('.scraping_url_thumbnail').remove();
		$('<img src="" class="scraping_url_thumbnail">').appendTo($('.scraping_thumbnail_wrapper'));
		
		$('.scraping_url_thumbnail').load(function() {
			if( $(this).width() > $(this).height() ){
				$(this).css('width', 80);
			}
			else{
				$(this).css('height', 80);
			}
	    }).attr('src', url);
	}

    $.fn.Scraping.defaults = {
    		phpUrl:	null
    };
 
}) (jQuery);