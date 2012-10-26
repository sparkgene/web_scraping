/**
 * jQuery Scraping plugin
 * This jQuery plugin display thumbnail image and simple introduction of the enterd url.
 * @name jquery.scraping.0.1.js
 * @author Leandro Vieira Pinho - http://leandrovieira.com
 * @version 0.5
 * @date April 11, 2008
 * @category jQuery plugin
 * @copyright (c) 2008 Leandro Vieira Pinho (leandrovieira.com)
 * @license CCAttribution-ShareAlike 2.5 Brazil - http://creativecommons.org/licenses/by-sa/2.5/br/deed.en_US
 * @example Visit http://leandrovieira.com/projects/jquery/lightbox/ for more informations about this jQuery plugin
 */

(function($){

    $.fn.Scraping = function(settings){
    	
    	settings = jQuery.extend({
    		phpUrl:				null,
    		defaultThumbnail:	null
    	},settings);

    	var scrapingInputObj = $(this); 
    	var _imageList = null;
    	var _imageIdx = 0;
    	
    	function _initialize( settings ) {
			if( settings.phpUrl == null ) return;
			
			scrapingInputObj.change(function(){
				_imageList = null;
				_imageIdx = 0;
				$('.scraping_url_info_box').remove();
	
				url = $(this).val();
				if( url == "" ){
					return;
				}
				_load_url($(this).val(), settings);
			});
		}

    	function _load_url( url, settings ){
			$.getJSON(settings.phpUrl, 
					{ target: url }, 
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
							contents += '<span class="scraping_thumbnail_pages">1/' + json.imgs.length + '</span>';
							contents += '</div>';
						}
						contents += '</div><div style="clear:both;"></div>';
						$(contents).insertAfter(scrapingInputObj);
						if( json.imgs.length > 1 ){
							$(".scraping_thumbnail_next").removeAttr('disabled');
							$(".scraping_thumbnail_next").bind("click", _show_next);
							loadImage(_imageList[_imageIdx]);
						}
						else{
							$(".scraping_thumbnail_next").attr("disabled", "disabled");							
						}
						$(".scraping_thumbnail_back").attr("disabled", "disabled");
				});    		
		}
    	
    	function _show_back(){
    		if( _imageList.length <= 1 ){
    			$(this).unbind("click").attr("disabled", "disabled");
    			return;
    		}
    		if( _imageIdx == (_imageList.length - 1)){
    			$(".scraping_thumbnail_next").removeAttr("disabled").bind("click", _show_next);
    		}
    		_imageIdx--;
    		if( _imageIdx < 0 ){
    			_imageIdx = 0;
    			$(this).unbind("click").attr("disabled", "disabled");
    			return;
    		}

    		loadImage(_imageList[_imageIdx]);
    	}

    	function _show_next(){
    		if( _imageList.length <= 1 ){
    			$(this).unbind("click").attr("disabled", "disabled");
    			return;
    		}
    		if( _imageIdx == 0 ){
    			$(".scraping_thumbnail_back").removeAttr("disabled").bind("click", _show_back);
    		}
    		_imageIdx++;
    		if( _imageIdx >= _imageList.length ){
    			_imageIdx = _imageList.length - 1;
    			$(this).unbind("click").attr("disabled", "disabled");
    			return;
    		}

    		loadImage(_imageList[_imageIdx]);
    	}

    	function loadImage(url){
			$(".scraping_url_thumbnail").remove();
			$('<img src="" class="scraping_url_thumbnail">').appendTo($(".scraping_thumbnail_wrapper"));
			
    		$(".scraping_url_thumbnail").load(function() {
    			if( $(this).width() > $(this).height() ){
    				$(this).css("width", 80);
    			}
    			else{
    				$(this).css("height", 80);
    			}
    	    }).attr('src', url);
    	}

    	_initialize(settings);
    };

	
})(jQuery);