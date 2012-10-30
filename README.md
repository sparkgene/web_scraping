web_scraping
============

Add site information box by scraping HTML. (It works like facebook "Share")

This plugin works with 

* JQuery (http://jquery.com/) tested with JQuery v1.8.*
* simple_html_dom.php (http://simplehtmldom.sourceforge.net/)

### Usage
1. Put the "get_url_info.php" file on your web server.

2. You need to load the jQuery Library, the scraping Library and the scrapingJS stylesheet in the header of your html document:

```javascript
<script src="src/jquery.js"></script>
<script src="js/jquery.scraping.0.1.js"></script>
<link href="css/jquery.scraping.0.1.css" rel="stylesheet">
```

3. Call the initialize for the input.
Set the url of "get_url_info.php" to "phpUrl" option.  

```javascript
$(function() {
    $('#url_input').Scraping({
        phpUrl : './get_url_info.php'
    });
});
```
