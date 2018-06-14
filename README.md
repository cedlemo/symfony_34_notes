# Symfony 3.4 notes


# Creation d'un simple blog.

## Installation et configuration

```
mkdir blog
cd blog
composer create-project symfony/framework-standard-edition . "3.4.*"
```

Lors de l'installation, certaines informations doivent être renseignées:

```
Some parameters are missing. Please provide them.
database_host (127.0.0.1):
database_port (null):
database_name (symfony):
database_user (root):
database_password (null):
mailer_transport (smtp):
mailer_host (127.0.0.1):
mailer_user (null):
mailer_password (null):
secret (ThisTokenIsNotSoSecretChangeIt): azerlkjazerlkjazerlkjazerlmkjazer
```

Utiliser la version "dev" pour avoir accès à l'inferface de débug de symfony:
Dans le fichier *web/.htaccess*, il faut remplacer toutes les occurences de
`app.php` par `app_dev.php`:

```bash
grep "app_dev" web/.htaccess                                                                                                     public_html/blog
DirectoryIndex app_dev.php
    RewriteRule ^app_dev\.php(?:/(.*)|$) %{ENV:BASE}/$1 [R=301,L]
    RewriteRule ^ %{ENV:BASE}/app_dev.php [L]
```

## generation d'un bundle

un projet a au moins un bundle, les bundles sont créés en ligne de commande:

```
php bin/console generate:bundle
```
