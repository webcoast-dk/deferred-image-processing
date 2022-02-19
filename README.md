# Deferred Image Processing - TYPO3 CMS extension

## What does it do?

The extension defers the image processing from during page generation to 
when the image is actually requested from the client (browser). When the image
is processed and placed in the storage's processed folder, it will not be 
processed again, until it is deleted or all processed images are cleared.

This is useful on sites with lots of images on one page.
Instead of generating one image after another, leading to a massive delay in
page generation speed, the image processing load is split to the available PHP
processes and thereby to multiple CPU cores.

## Installation & configuration

The extension is available from packagist.org
```sh
composer require webcoast/deferred-image-processing
```
or from TYPO3 extension repository.

A database update is necessary to create the processing queue table.

### Rewrite rules for apache
If using the default htaccess file which is shipped with TYPO3, then there is a rule which stops all further processing
of static files which are not found:

```apacheconf
# Stop rewrite processing, if we are in the typo3/ directory or any other known directory
# NOTE: Add your additional local storages here
RewriteRule ^(?:typo3/|fileadmin/|typo3conf/|typo3temp/|uploads/|favicon\.ico) - [L]
```

But for this extension to work the request needs to be redirected to index.php and is then handled by a middleware.
So make sure to add a rule like this *before* the blocking rule above:

```apacheconf
# For EXT:deferred-image-processing
# If a processed image is not found, then redirect to index.php and let the middleware create one on the fly.
RewriteCond %{DOCUMENT_ROOT}/$1.$3 !-f
RewriteRule ^((fileadmin/_processed_|other-storage/_processed_)/.+)\.(gif|jpg|jpeg|png)$ %{ENV:CWD}index.php [L]
```

### Processing queue (optional)

Internally, a record for every deferred image is stored in the database table `tx_deferredimageprocessing_file`.
To clean up this table or speed up processing of lesser used files the records therein can be processed using a command controller or scheduler task.
This step is completely optional and not mandatory for the extension to work.

```shell
./typo3cms deferred_image_processing:process

# Process limiting to 5 items
./typo3cms deferred_image_processing:process 5
```

## Documentation

As the extension does everything itself automatically, there is no need
for further documentation. If you feel, a documentation could be helpful,
please contact me or open an issue with your question.

## Contributing

If you find a bug of want to improve the extension, you're free to fork it
and provide a pull request with your changes. If you don't have the resources
or knowledge, open an issue.

## License
© 2020, WEBcoast

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this
program. If not, see http://www.gnu.org/licenses/.
