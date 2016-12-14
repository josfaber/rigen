# RIGEN
Responsive Images Generator (PHP)

This software is a simplified version of [PHP Adaptive Images (PHP-AI)](https://github.com/MattWilcox/Adaptive-Images) and was created to work with both [Apache](http://www.apache.org) and [NGINX](http://www.nginx.com) in a quick and easy way.

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
