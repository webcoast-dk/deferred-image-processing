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

There is nothing to configure.

## Documentation

As the extension does everything itself automatically, there is not need
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
