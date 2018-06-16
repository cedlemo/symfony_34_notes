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

Et bien sur cela ne fonctionne pas après. Il y a le message suivant quand on
rafraichi la page:

```
Unable to find template "BlogBundle:Default:index.html.twig" (looked into: /home/cedlemo/public_html/blog/app/Resources/views, /home/cedlemo/public_html/blog/vendor/symfony/symfony/src/Symfony/Bridge/Twig/Resources/views/Form).
```
L'erreur vient du fichier : src/BlogBundle/Controller/DefaultController.php

```php
<?php

namespace BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('BlogBundle:Default:index.html.twig');
    }
}
```

C'est une sorte de bug. En gros depuis la version 3.4 de Symfony, les chemins
de fichiers twig du type `'BlogBundle:Default:index.html.twig'` ne sont plus
supportés, le nouveau format est : `@Blog/Default/index.html.twig`. Il semble
que le probleme vienne de la commande `generate:bundle` qui n'a pas été mise à
jour.

source : https://stackoverflow.com/questions/47832977/symfony-3-4-use-view-inside-my-bundle?rq=1

```diff
diff --git a/src/BlogBundle/Controller/DefaultController.php b/src/BlogBundle/Controller/DefaultController.php
index 0e7ca62..7a062f2 100644
--- a/src/BlogBundle/Controller/DefaultController.php
+++ b/src/BlogBundle/Controller/DefaultController.php
@@ -8,6 +8,6 @@ class DefaultController extends Controller
 {
     public function indexAction()
     {
-        return $this->render('BlogBundle:Default:index.html.twig');
+        return $this->render('@Blog/Default/index.html.twig');
     }
 }
```

## Entités et schema de la base de données

La [configuration de la base de données](http://symfony.com/doc/3.4/best_practices/configuration.html) peut être retrouvée dans le fichier
*app/config/parameters.yml*. Dans mon cas mon fichier ressemble à ceci:

```yaml
# This file is auto-generated during the composer install
parameters:
    database_host: 127.0.0.1
    database_port: null
    database_name: symfony_blog
    database_user: root
    database_password: null
    mailer_transport: smtp
    mailer_host: 127.0.0.1
    mailer_user: null
    mailer_password: null
    secret: azerlkjazerlkjazerlkjazerlmkjazer
```

Avant toutes choses, il faut créer la base de données. Les tables de cette base
et leurs relations base seront ajoutées au fur et à mesure de leurs créations.

```bash
php bin/console doctrine:database:create
Created database `symfony_blog` for connection named default
```

### Création d'entités

En considérant que le blog repose sur une base de données contenant quatre
principales:

* articles
* categories
* comments
* authors

on créera donc 4 "entities" :
* Article
* Categorie
* Comments
* Author

#### Entity Article:

Les champ de la table *articles* sont:
* title
* content
* publicationDate
* status

Donc pour créer une entité *Article*, on execute la commande suivante:

```bash
php bin/console doctrine:generate:entity                                                                                 public_html/blog  master

  Welcome to the Doctrine2 entity generator

This command helps you generate Doctrine2 entities.

First, you need to give the entity name you want to generate.
You must use the shortcut notation like AcmeBlogBundle:Post.

The Entity shortcut name: BlogBundle:Article

Determine the format to use for the mapping information.

Configuration format (yml, xml, php, or annotation) [annotation]:

Instead of starting with a blank entity, you can add some fields now.
Note that the primary key will be added automatically (named id).

Available types: array, simple_array, json_array, object,
boolean, integer, smallint, bigint, string, text, datetime, datetimetz,
date, time, decimal, float, binary, blob, guid.

New field name (press <return> to stop adding fields): title
Field type [string]:
Field length [255]:
Is nullable [false]:
Unique [false]:

New field name (press <return> to stop adding fields): content
Field type [string]: text
Is nullable [false]:
Unique [false]:

New field name (press <return> to stop adding fields): publicationDate
Field type [string]: datetime
Is nullable [false]:
Unique [false]:

New field name (press <return> to stop adding fields): status
Field type [string]: boolean
Is nullable [false]:
Unique [false]:

New field name (press <return> to stop adding fields):


  Entity generation


  created ./src/BlogBundle/Entity/
  created ./src/BlogBundle/Entity/Article.php
> Generating entity class src/BlogBundle/Entity/Article.php: OK!
> Generating repository class src/BlogBundle/Repository/ArticleRepository.php: OK!


  Everything is OK! Now get to work :).
```

Cette commande génère différents fichiers:
* src/BlogBundle/Entity/Article.php
* src/BlogBundle/Repository/ArticleRepository.php

Dans *Article.php*, se trouve une classe `Article` avec des variables privées
nommées selon les champs que l'on a rensignés ainsi que des getters/setters pour
ces variables. De plus on trouve en commentaire des annotations faisant le lien
entre les variables et les tables/colonnes à générer dans la base de données.

L'annotation suivante:

```php
/**
 * Article
 *
 * @ORM\Table(name="article")
 * @ORM\Entity(repositoryClass="BlogBundle\Repository\ArticleRepository")
 */
class Article
{
```
dit que la classe Article correspond à la table "article".

L'annotation:

```php
    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;
```
indique que la variable `$title` de la classe `Article` correspondra à une colonne
nommée "title" dans la table "article".

### Gestion des relations entre entités.

### Création et synchronisation de la base de données.

## Contrôleur et vues twig.
