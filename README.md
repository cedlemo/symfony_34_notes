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
C'est une commande interactive, voici les réponses pour ce petit projet de test:

* le nom du bundle : *BlogBundle*
* installation dans le répertoire par défaut: *src/*
* le format de la configuration : *yml*

```bash
php bin/console generate:bundle                                                                                          public_html/blog  master

  Welcome to the Symfony bundle generator!

Are you planning on sharing this bundle across multiple applications? [no]: no

Your application code must be written in bundles. This command helps
you generate them easily.

Give your bundle a descriptive name, like BlogBundle.
Bundle name: BlogBundle

Bundles are usually generated into the src/ directory. Unless you're
doing something custom, hit enter to keep this default!

Target Directory [src/]:

What format do you want to use for your generated configuration?

Configuration format (annotation, yml, xml, php) [annotation]: yml


  Bundle generation
  ...
```

Un message d'erreur à l'issue de la création du bundle indique, que ce dernier
n'est pas chargé par défaut:

```
The command was not able to configure everything automatically.
  You'll need to make the following changes manually.


- Edit the composer.json file and register the bundle
  namespace in the "autoload" section:
```

Il suffit d'ajouter dans la section autoload du fichier *composer.json*, une ligne
pour référencer le nouveau bundle.

```diff
diff --git a/composer.json b/composer.json
index ba17155..06faed5 100755
--- a/composer.json
+++ b/composer.json
@@ -5,7 +5,8 @@
     "description": "The \"Symfony Standard Edition\" distribution",
     "autoload": {
         "psr-4": {
-            "AppBundle\\": "src/AppBundle"
+            "AppBundle\\": "src/AppBundle",
+           "BlogBundle\\": "src/BlogBundle"
         },
         "classmap": [ "app/AppKernel.php", "app/AppCache.php" ]
     },
```

après, il faut lancer la commande suivante afin de regénerer la liste des classes
à charger automatiquement dans le projet:

```
composer dump-autoload
```

Si cette commande n'est pas lancée, toutes tentatives d'utiliser la console
`php bin/console *` ou d'accèder à la page d'accueil du blog, renverra un message
d'erreur du type:

```
Attempted to load class "BlogBundle" from namespace "BlogBundle"
```

## Entités et schema de la base de données

### Création d'entités

### Gestion des relations entre entités.

### Création et synchronisation de la base de données.

## Contrôleur et vues twig.
