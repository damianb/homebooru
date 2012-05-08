# homebooru

local image booru for managing image tags and ratings and searching a glorious huge local collection of images, for the hidden otaku in all.

# This project is currently under heavy development and **is not yet stable enough for public use**.

## requirements

 * PHP 5.3.2+
 * PHP GD extension or Imagemagick & PHP Imagick extension (Imagick recommended; GD has quality issues when resizing)
 * Supported database server
     * MySQL 5.1+ (tested on 5.5)
     * SQLite3
     * PostGres (untested)

## installation

 * Set a virtualhost up to point to `/web/` of the application's installation directory
 * Add an alias to point to the image upload directory (by default, `/upload/`) and set its location in the config file `/config/config.json`, under the settings `site.thumburl`, `site.smallurl`, `site.imageurl`
 * Modify the `cookie.path` and `site.urlbase` settings to suit your installation path
 * chmod `/import/`, `/upload/thumb/`, `/upload/small/`, `/upload/full/`, `/cache/`, `/cache/viewcache/` to be writeable by the webserver (or whatever PHP runs as)
