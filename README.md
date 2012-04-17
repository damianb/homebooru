# homebooru

local image booru for managing image tags and ratings and searching a glorious huge local collection of images, for the hidden otaku in all.

## requirements

 * PHP 5.3.2+
 * PHP GD extension
 * MySQL 5.1+ (tested on 5.5) or SQLite3

## installation

 * Set a virtualhost up to point to `/web/` of the application's installation directory
 * Add an alias to point to the image upload directory (by default, `/upload/`) and set its location in the config file `/config/config.json`, under the settings `site.thumburl`, `site.smallurl`, `site.imageurl`
 * Modify the `cookie.path` and `site.urlbase` settings to suit your installation path
