# BszGrouping module for VuFind

This module offers a simple and performant but not perfect way to recognize 
duplicate records. It uses [Apache Solr's grouping feature](https://solr.apache.org/guide/8_1/result-grouping.html). 

Solr groups all records with the same matchkey, when called with the grouping 
params below. The first of this similar records in each group is called "master
record" the others are referred as "subrecords". 

Deduplication can optionally be turned on or off by end-users. The last state is 
being saved as cookie. This makes it easy to compare and evaluate deduplication. 

This README describes the steps to get it working and provides some
troubleshooting hints. 

## Quick steps

### Indexing
Index a matchkey field in Solr. A good starting point would be to index 
   `format:isbn:year`
as matchkey, with
- format: this should only be simple formats (about 10 different terms)
- isbn: this should be normalized isbn13
- year: year of publication (yyyy)

In case you don't have an isbn in the metadata, you could use 
    `format:author:title:year:publisher`
or
    `format:author:title:year`
or
    `format:author:title`
as matchkey, with
- format: this should only be simple formats (maybe about 10 different terms)
- author: this should be normalized to lowercase lastname
- title: this should be normalized (lowercase)
- year: year of publication (yyyy)
- publisher: this should be normalized (lowercase, handling of abbreviations and punctuation)


### Enable this module
Enable this module in your `httpd-vufind.conf` & restart Apache.

### Use trait
In your record driver, use the `SubrecordTrait` by adding
~~~php
class Your RecordDriver extends SolrMarc {
    ...
    use BszGrouping/RecordDriver/SubrecordTrait;
    ...   
}
~~~
This just adds some accessor methods. It will not interfere with your 
custom code. 

### Add params to config
In your local `config.ini` add the following to `[Index]` section`
~~~ini
[Index]
...
group = true
group.field = "enter the name of your matchkey here"
group.limit = 10
~~~

### Template work
Take a look in our example template to see how it's working. We are using 
Bootstraps collapsible classes to show a button that opens all the 
duplicates.

## User interface

### JavaScript
Add the following JS code to your custom JS file to avoid conflicts
with VuFinds JS. 

~~~javascript
function duplicates() {
    // handle collapse of the duplicate records in result-list
    $('.duplicates-toggle').click(function(e){
       $(this).parent().toggleClass('active');
       $(this).children('i').toggleClass('fa-arrow-down');
       $(this).children('i').toggleClass('fa-arrow-up');
    });
    
    // handle checkbox to enable/disable grouping
    $('#dedup-checkbox').change(function(e) {
        var status = this.checked;
        $.ajax({
           dataType: 'json',
           method: 'POST',
           url: VuFind.path + '/AJAX/JSON?method=dedupCheckbox',
           data: { 'status': status },
           success: function() {
               // reload the page
               window.location.reload(true);
           }
        });
    });
}
$(document).ready(function() {    
    // other custom code goes here
    duplicates();
});
~~~

### HTML / Templates
Put the following hTML snippet where you want the checkbox 
the enable / disable grouping, for example in`search/results.phtml`

~~~php
<form class="form-inline search-dedup" action="<?= $this->currentPath() ?>"
   method="POST" id="dedup">
   <div class="form-group">
       <label for="dedup-checkbox"><?=$this->transEsc('group hits') ?></label>
       <input type="checkbox" name="dedup-enabled" value=""
              id="dedup-checkbox"
              <?php if ($this->dedup): ?>checked<?php endif; ?>/>
   </div>
   <noscript><p>Please enable JavaScript</p></noscript>
</form>
~~~


### Collapse subrecords

still to do

## Troubleshooting

Test if the backend code in this module is being executed. The simplest way to 
find out is to put a `die(__CLASS__),` in the `search()` function. If this does not
pop up when searching, the module is not being loaded correctly.

## External Sources
* [German presentation by Stefan Winkler](https://www.vufind.de/wp-content/uploads/2018/09/2-1-Grouping-Deduplizierung-mit-Matchkeys-in-BOSS3-VuFind-AWT-2018.pdf) 
