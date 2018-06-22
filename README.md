# Symfony 3.4 notes: Creation d'un simple blog.

* [Installation et configuration](#installation-et-configuration)
* [Generation d'un bundle](#generation-d-'-bundle)
* [Entités et schema de la base de données](#entités-et-schema-de-la-base-de-données)
  * [Création d'entités](#création-d'entités)
    * [Entity Article](#entity-article)
    * [Entity Category](#entity-category)
    * [Entity Author](#entity-author)
    * [Entity Comment](#entity-comment)
    * [Générer les tables de la base de données](#générer-les-tables-de-la-base-de-données)
  * [Gestion des relations entre entités](#gestion-des-relations-entre-entités)
    * [Relation bidirectionnelle ManyToOne OneToMany](#relation-bidirectionnelle-manytoone-onetomany)
      * [Relation Article Author](#relation-article-author)
      * [Relation Article Comment](#relation-article-comment)
    * [Relation bidirectionnelle ManyToMany](#relation-bidirectionnelle-manytomany)
      * [Relation Article Category](#relation-article-category)

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

## Generation d'un bundle

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
tables principales:

* article
* category
* comment
* author

on créera donc 4 "entities" :
* Article
* Category
* Comment
* Author

#### Entity Article

Les champs de la table *articles* sont:
* title
* content
* publicationDate
* status

Donc pour créer une entité *Article*, on exécute la commande suivante:

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

#### Entity Category

La création de cette entité se fait comme précédement:

```
New field name (press <return> to stop adding fields): name
Field type [string]:
Is nullable [false]:
Unique [false]:
```

#### Entity Author

```
New field name (press <return> to stop adding fields): name
Field type [string]:
Is nullable [false]:
Unique [false]:
New field name (press <return> to stop adding fields): biography
Field type [string]: text
Is nullable [false]:
Unique [false]:
```

#### Entity Comment

```
New field name (press <return> to stop adding fields): email
Field type [string]:
Is nullable [false]:
Unique [false]:
New field name (press <return> to stop adding fields): content
Field type [string]: text
Is nullable [false]:
Unique [false]:
New field name (press <return> to stop adding fields): datePublication
Field type [string]: datetime
Is nullable [false]:
Unique [false]:
New field name (press <return> to stop adding fields): status
Field type [string]: boolean
Is nullable [false]:
Unique [false]:
```

#### Générer les tables de la base de données

Après chaque création d'"entity" ou après la création de toutes les "entities",
il est possible de générer les tables correspondantes avec la commande suivante:

```bash
php bin/console doctrine:schema:udpate --force
```

### Gestion des relations entre entités
Avant de créer les relations, il faut les identifier:

#### Relation bidirectionnelle ManyToOne OneToMany

##### Relation Article Author

* Un article a un auteur, un auteur peut créer plusieurs articles.

Ici on se retrouve dans le même cas que dans l'exemple de la [documentation](http://symfony.com/doc/3.4/doctrine/associations.html#relationship-mapping-metadata). D'un point de vue de l'entité
`Article`, plusieurs articles peuvent être reliés à un auteur :`ManyToOne`. D'un
point de vue de l'entité `Author`, un auteur est lié à plusieurs articles : `OneToMany`.

Pour mettre en place cette relation bi-directionnelle, il faut:
* créer une variable `$author` et ses getter/setter dans `Article` ainsi que les
annotations nécessaires pour `doctrine`.
* créer une variable `$articles` et ses getter/setter dans `Author` ainsi que les
annotations nécessaires pour `doctrine`.

Un bon diff vieux diff:

```diff
diff --git a/src/BlogBundle/Entity/Article.php b/src/BlogBundle/Entity/Article.php
index 8a86a61..a9ea99c 100644
--- a/src/BlogBundle/Entity/Article.php
+++ b/src/BlogBundle/Entity/Article.php
@@ -49,6 +49,10 @@ class Article
      */
     private $status;

+    /**
+     * @ORM\ManyToOne(targetEntity="BlogBundle\Entity\Author", inversedBy="articles")
+     */
+    private $author;

     /**
      * Get id
@@ -155,5 +159,24 @@ class Article
     {
         return $this->status;
     }
-}

+    /**
+     * Get author
+     * @return Author
+     */
+    public function getAuthor()
+    {
+	return $this->author;
+    }
+
+    /**
+     * Set author
+     * @param Author $author
+     * @return Article
+     */
+    public function setAuthor($author)
+    {
+	$this->author = $author;
+	return $this;
+    }
+}
diff --git a/src/BlogBundle/Entity/Author.php b/src/BlogBundle/Entity/Author.php
index 3eb009f..351f055 100644
--- a/src/BlogBundle/Entity/Author.php
+++ b/src/BlogBundle/Entity/Author.php
@@ -36,6 +36,11 @@ class Author
     private $biography;


+    /**
+     * @ORM\OneToMany(targetEntity="BlogBundle\Entity\Article", mappedBy="author")
+     */
+    private $articles;
+
     /**
      * Get id
      *
@@ -93,5 +98,24 @@ class Author
     {
         return $this->biography;
     }
-}

+    /**
+     * Set articles
+     * @param mixed Article
+     * @return Author
+     */
+    public function setArticles($articles)
+    {
+	$this->articles = $articles;
+	return $this;
+    }
+
+    /**
+     * Get articles
+     * @return mixed Article
+     */
+    public function getArticles()
+    {
+	return $this->articles;
+    }
+}
```
Pour générer la relation, il suffit de mettre à jour la base de données avec:

```
php bin/console doctrine:schema:update --force
```

##### Relation Article Comment

* Un commentaire est lié à un article, un article peut avoir plusieurs commentaires.
On se trouve dans le même cas que précédement.  D'un point de vue du commentaire,
plusieurs commentaires peuvent être liés à un article : `ManyToOne` et un
article peut avoir plusieur commentaires `OneToMany`.

Dans la classe `Comment`, on crée une variable privée `$articles`, ses setter/getter
ainsi que les annotations décrivant la relation avec la classe Article.

```diff
diff --git a/src/BlogBundle/Entity/Comment.php b/src/BlogBundle/Entity/Comment.php
index 9dd1e8b..c7f8034 100644
--- a/src/BlogBundle/Entity/Comment.php
+++ b/src/BlogBundle/Entity/Comment.php
@@ -49,6 +49,11 @@ class Comment
      */
     private $status;

+    /**
+     * @var Article
+     * @ORM\ManyToOne(targetEntity="\BlogBundle\Entity\Article", inversedBy="comments")
+     */
+    private $article;

     /**
      * Get id
@@ -155,5 +160,24 @@ class Comment
     {
         return $this->status;
     }
-}

+    /**
+     * Set article
+     * @param Article $article
+     * @return Comment
+     */
+    public function setArticle(Article $article)
+    {
+       $this->article = $article;
+       return $this;
+    }
+
+    /**
+     * Get article
+     * @return Article
+     */
+    public function getArticle()
+    {
+       return $this->article;
+    }
+}
```

Dans la classe `Article` on ajoute la variable `$comments`, son getter, son
setter ainsi que les annotations nécessaires à la description de la relation
avec la classe `Comment`.


```diff
diff --git a/src/BlogBundle/Entity/Article.php b/src/BlogBundle/Entity/Article.php
index a9ea99c..ac7405b 100644
--- a/src/BlogBundle/Entity/Article.php
+++ b/src/BlogBundle/Entity/Article.php
@@ -54,6 +54,11 @@ class Article
      */
     private $author;

+    /**
+     * @ORM\OneToMany(targetEntity="BlogBundle\Entity\Comment", mappedBy="article")
+     */
+    private $comments;
+
     /**
      * Get id
      *
@@ -179,4 +184,24 @@ class Article
        $this->author = $author;
        return $this;
     }
+
+    /**
+     * Get articles
+     * @return mixed Aricle
+     */
+    public function getArticles()
+    {
+       return $this->articles
+    }
+
+    /**
+     * Set articles
+     * @param mixed Article
+     * @return Article
+     */
+    public function setArticles($articles)
+    {
+       $this->articles = $articles;
+        return $this;
+    }
 }
```

Avant de mettre à jour la base de données, il est possible de valider les
annotations:

```bash
php bin/console doctrine:schema:validate
```
Comme précédement les changements sur la base de données sont générés avec:

```bash
php bin/console doctrine:schema:update --force
```

#### Relation bidirectionnelle ManyToMany

##### La relation Article Category

* un article peut avoir plusieurs catégories, une catégorie peut décrirent
plusieurs articles.

La documentation relative a ces relations `ManyToMany` se trouve [ici](
https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#many-to-many-bidirectional
).
Dans ce type de relation, il va falloir décider quel entité va être responsable
de la relation. Ici c'est l'Article puisque l'on associe obligatoirement
une catégorie lors de la création d'un article.

Donc dans la classe `Article`, on ajoute une variable privée `$categories` avec
les fonctions qui vont bien et l'annotation suivante:

```php
/**
 * @ORM\ManyToMany(targetEntity="\BlogBundle\Entity\Category", inversedBy="articles")
 */
```

Dans la classe `Category`, on ajoute la variables `$articles` avec ses setter/getter
et l'annotation suivante:

```php
/**
 * @ORM\ManyToMany(targetEntity="\BlogBundle\Entity\Article", mappedBy="categories")
 */
```

Après vérification, on lance la mise à jour de la base de données.

```bash
php bin/console doctrine:schema:validate
php bin/console doctrine:schema:update --force
```

## Contrôleur et vues twig.

C'est dans le contrôleur que l'on trouvera la logique applicative concernant les
actions réalisées par l'utilisateur. On peut tout à fait générer un à un les
contrôleurs nécessaires avec la commande:

```
php bin/console doctrine:generate:controleur
```

Mais on peut aussi utiliser générer directement un ensemble de contrôleurs
génériques pour les actions de base:

- Create
- Read
- Update
- Delete.

Avec la commande suivante pour l'entity `BlogBundle:Category`:

```
php bin/console doctrine:generate:crud
```

On choisira de générer les actions permettant d'écrire, d'insérer des données
dans la base de données:

```
Do you want to generate the "write" actions [no]? yes
```

Ensuite les routes seront gérées via des fichiers yml:

```
Determine the format to use for the generated CRUD.
Configuration format (yml, xml, php, or annotation) [annotation]: yml
```

La commande va générer le contrôleur dans :
- src/BlogBundle/Controller/CategoryController.php

Des fichiers de vues twig et des formulaires pour la création et l'édition
de l'entité `Category` dans les répertoires:
- src/BlogBundle/Form/
- app/Resources/views/category/

Rien que avec cela, les urls suivantes sont fonctionnelles:

* http://blog.fr/admin/category/
* http://blog.fr/admin/category/new
* http://blog.fr/admin/category/1/show
* http://blog.fr/admin/category/1/delete

