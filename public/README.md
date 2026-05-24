# Public front controller bridge

This directory is the Symfony 4-style document root.

Runtime config, generated Encore assets, LiipImagine cache, Vich uploads, and
static files now target this directory.

Production and local web servers should use this directory as the document root.
For PHP's built-in server, use `public/router.php` so dynamic `.html` routes are
handled by Symfony instead of being treated as static files.
