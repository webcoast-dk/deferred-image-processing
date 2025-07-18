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

## TYPO3 CMS & PHP version compatibility

### TYPO3 CMS

| Extension ↓ / TYPO3 → | 10.4 | 11.5 | 12.4 | 13.4 |
|-----------------------|:----:|:----:|:----:|:----:|
| 1.0.0                 |  ✅  |  ❌  |  ❌  | ❌  |
| 1.1.0                 |  ✅  |  ✅  |  ❌  | ❌  |
| 2.0.0                 |  ❌  |  ✅  |  ✅  | ❌  |
| 3.0.0                 |  ❌  |  ❌  |  ✅  | ✅  |

### PHP

| Extension ↓ / PHP → | 7.2  | 7.3  | 7.4  | 8.0  | 8.1  | 8.2  | 8.3  | 8.4  |
|---------------------|:----:|:----:|:----:|:----:|:----:|:----:|:----:|:----:|
| 1.0.0               |  ✅  |  ✅  |  ✅  |  ❌  |  ❌  |  ❌  |  ❌  |  ❌  |
| 1.1.0               |  ✅  |  ✅  |  ✅  |  ✅  |  ✅  |  ✅  |  ✅  |  ✅  |
| 2.0.0               |  ❌  |  ❌  |  ✅  |  ✅  |  ✅  |  ✅  |  ✅  |  ✅  |
| 3.0.0               |  ❌  |  ❌  |  ❌  |  ❌  |  ✅  |  ✅  |  ✅  |  ✅  |

## Installation & configuration

The extension is available from packagist.org
```sh
composer require webcoast/deferred-image-processing
```
or from TYPO3 extension repository.

A database update is necessary to create the processing column `sys_file_processedfile.processed`.

```sh
./vendor/bin/typo3 database:updateschema
```

### RewriteRule for `apache`

If using the default htaccess file which is shipped with TYPO3,
then there is a rule which stops all further processing
of static files which are not found:

```apacheconf
# Stop rewrite processing, if we are in any other known directory
# NOTE: Add your additional local storages here
RewriteRule ^(?:fileadmin/|typo3conf/|typo3temp/|uploads/) - [L]
```

But for this extension to process the request - if no prepared image was found - needs to be redirected to `index.php` and is then handled by the middleware on the fly.
So make sure to add a rule like this *before* the blocking rule above:

```apacheconf
  # EXT:deferred-image-processing
  # NOTE: Speed up ^Production w/ ..
  #RewriteCond %{HTTP_ACCEPT} ^image/
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteRule /_processed_/.+_([0-9a-f]{10})\.([a-z]+)$ %{ENV:CWD}index.php?dip[chk]=$1&dip[ext]=$2 [END]
```
URL/HASH ref. @ [`Resource/Processing/AbstractTask`](https://github.com/TYPO3/typo3/blob/12.4/typo3/sysext/core/Classes/Resource/Processing/AbstractTask.php#L79-L103)

### RewriteRule for `nginx`

```nginx
  # EXT:deferred-image-processing
  location ~ /_processed_/.+_([0-9a-f]{10})\.([a-z]+)$ {
    try_files $uri /index.php?dip[chk]=$1&dip[ext]=$2;
  }
```

### Processing queue (optional)

The deferred images are marked in the `sys_file_processedfile` table by setting the
`processed` column to `0`. To process deferred images in the background, you can use
the `deferred_image_processing:process` command, which can be run with a cronjob or
be executed via the TYPO3 CMS scheduler.

This step is completely optional and not mandatory for the extension to work.

```shell
# Process limiting to 10 items
./vendor/bin/typo3 deferred_image_processing:process

# Process limiting to  5 items
./vendor/bin/typo3 deferred_image_processing:process 5
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
