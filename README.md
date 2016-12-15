# RIGEN
Responsive Images Generator (PHP)

A script that automatically scales requested image based on the requesting device's screen. It's a cookie based solution with a backup that checks the useragent string for "mobile".  

This software is a simplified version of the awesome [PHP Adaptive Images (PHP-AI)](https://github.com/MattWilcox/Adaptive-Images) (no longer maintained) and was created to work with both [Apache](http://www.apache.org) and [NGINX](http://www.nginx.com) in a quick and easy way.

## How to
1. create cache dir (write access) and update `_rigen.php` with dir name
2. Add rewrite for Apache (`.htaccess`) or NGINX (via site config)
3. Add javascript in header `<script>document.cookie='resolution='+Math.max(screen.width,screen.height)+'; path=/';</script>`

## Example rewrite rules
These are examples for my localhost, using a subfolder called `rigen`.

### Apache
```
RewriteEngine On
RewriteBase /rigen/

RewriteCond %{REQUEST_URI} !img-cache
RewriteRule ^(.*\.(?:jpe?g|jpg|gif|png))$ _rigen.php?$1 [NC,L]
```
### NGINX
```
location ~* /rigen/.*/(.*)\.(gif|jpg|png|jpeg)$ {
    rewrite ^/rigen/(.*)$ /rigen/_rigen.php?$1 last;
}
```
## How it works
The javascript stores the users screen size in a cookie. At the moment an image is requested further down the html, it is redirected through `_rigen.php`, which will handle the request in this order:

1. If there is no cookie found, check the useragent string for "mobile". Send either the biggest or smallest image based on the breakpoints array
2. If the image dimensions are smaller than the users size, serve the original image
3. If the the image is bigger, check the cache dir for a scaled down version for the next (downward) breakpoint (configurable)
4. Compare the cached file timestamp to the original file timestamp
5. Regenerate the cache file if not available or outdated
6. Serve the cache file
