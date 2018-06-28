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
 * [Controleur et vues twig](#contrôleur-et-vues-twig)
   * [Creation des contrôleurs : CRUD](#creation-des-contrôleurs-:-crud)
     * [CRUD Article](#crud-article)
     * [CRUD Category](#crud-category)
     * [CRUD Comment et Author](#crud-comment-et-author)
   * [Le routage](#le-routage)
   * [Les vues twig](#les-vues-twig)
     * [Configuration des chemins](#configuration-des-chemins)
     * [Présentation des templates](#présentation-des-templates)
 * [Gestion d'utilisateurs avec FOSUser](#gestion-d-'-utilisateurs-avec-fosuser)
   * [Installation de FOSUser](#installation-de-fosuser)

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

### Creation des contrôleurs : CRUD.

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

Dans cet exemple on considère que toutes les actions que l'on va générer, devront
être accessible via les routes suivantes:
* /admin/article
* /admin/category
* /admin/author
* /admin/comment
Cela permettra de séparer la gestion (suppression, édition, création ...) des
éléments du site afin de faciliter l'authentification.

#### CRUD Category

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

#### CRUD Article.

Même commande que précédement:

```
php bin/console doctrine:generate:crud
```

Si l'erreur suivante apparait:

```
- Import the bundle's routing resource in the bundle routing file
  (/home/cedlemo/public_html/blog/src/BlogBundle/Resources/config/routing.yml).

    blog_admin_article:
        resource: "@BlogBundle/Resources/config/routing/article.yml"
        prefix:   /admin/article
```

C'est que l'import des routes n'a pu se faire. Il suffit de rajouter la partie
manquante dans le `routing.yml` du bundle.

```diff
diff --git a/src/BlogBundle/Resources/config/routing.yml b/src/BlogBundle/Resources/config/routing.yml
index 7ac2d32..ee41b2f 100644
--- a/src/BlogBundle/Resources/config/routing.yml
+++ b/src/BlogBundle/Resources/config/routing.yml
@@ -2,6 +2,10 @@ blog_admin_category:
     resource: "@BlogBundle/Resources/config/routing/category.yml"
     prefix:   /admin/category

+blog_admin_article:
+        resource: "@BlogBundle/Resources/config/routing/article.yml"
+        prefix:   /admin/article
+
 blog_homepage:
     path:     /
     defaults: { _controller: BlogBundle:Default:index }
```

#### CRUD Comment et Author.

La generation peut se faire sans le côté interactif avec:

```
php bin/console doctrine:generate:crud BlogBundle:Comment -n --format=yml --with-write
php bin/console doctrine:generate:crud BlogBundle:Author -n --format=yml --with-write
```

### Le routage:

La gestion des "routes", c'est à dire les urls correspondant aux contrôleurs et
à leurs actions, se fait dans le fichier *BlogBundle/Ressources/config/routing.yml*.
L'ajout des CRUD pour chaque entités a séparer les routes dans différents fichier.

```
tree Resources/config                                                                                                      src/BlogBundle  master
Resources/config
├── routing
│   ├── article.yml
│   ├── author.yml
│   ├── category.yml
│   └── comment.yml
├── routing.yml
```

Pour le cas `Article`, on le retrouve dans le fichier *routing.yml* :

```yaml
blog_admin_article:
    resource: "@BlogBundle/Resources/config/routing/article.yml"
    prefix:   /admin/article

```
On prefixe donc toutes les routes en lien avec la gestion des articles par `/admin/article`
et le fichier contenant le reste de ces routes est indiqué par le champ `resource`. Dans
ce fichier se trouve les routes en liens avec les actions du contrôleur `ArticleController`.:

```yaml
admin_article_index:
    path:     /
    defaults: { _controller: "BlogBundle:Article:index" }
    methods:  GET

admin_article_show:
    path:     /{id}/show
    defaults: { _controller: "BlogBundle:Article:show" }
    methods:  GET

admin_article_new:
    path:     /new
    defaults: { _controller: "BlogBundle:Article:new" }
    methods:  [GET, POST]

admin_article_edit:
    path:     /{id}/edit
    defaults: { _controller: "BlogBundle:Article:edit" }
    methods:  [GET, POST]

admin_article_delete:
    path:     /{id}/delete
    defaults: { _controller: "BlogBundle:Article:delete" }
    methods:  DELETE
```

### Les vues twig

#### Configuration des chemins

Par défaut, la génération des actions CRUD pour l'entité `Article`, a créé des
vues dans *app/Resources/views*:

* app/Resources/views/article/edit.html.twig
* app/Resources/views/article/index.html.twig
* app/Resources/views/article/new.html.twig
* app/Resources/views/article/show.html.twig

Ces vues sont appelées dans l'action associée. Par exemple, la vue *index.html*,
est appelée dans `ArticleController`, par:

```php
   /**
     * Lists all article entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $articles = $em->getRepository('BlogBundle:Article')->findAll();

        return $this->render('article/index.html.twig', array(
            'articles' => $articles,
        ));
    }
```

Il est plus intéressant d'avoir les vues en lien avec un bundle dans ce bundle.
Donc il faut déplacer les vues de *app/Resources/views* à *BlogBundle/Resources/views*.

De plus, l'utilisation d'annotations pour faire le lien entre une vue et un
contrôleur permet de grandement simplifier le code. Exemple avec `ArticleController.indexAction`:

```diff
diff --git a/src/BlogBundle/Controller/ArticleController.php b/src/BlogBundle/Controller/ArticleController.php
index 14a97d5..7ca9ca6 100644
--- a/src/BlogBundle/Controller/ArticleController.php
+++ b/src/BlogBundle/Controller/ArticleController.php
@@ -5,6 +5,7 @@ namespace BlogBundle\Controller;
 use BlogBundle\Entity\Article;
 use Symfony\Bundle\FrameworkBundle\Controller\Controller;
 use Symfony\Component\HttpFoundation\Request;
+use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

 /**
  * Article controller.
@@ -15,6 +16,7 @@ class ArticleController extends Controller
     /**
      * Lists all article entities.
      *
+     * @Template("@Blog/article/index.html.twig")
      */
     public function indexAction()
     {
@@ -22,9 +24,7 @@ class ArticleController extends Controller

         $articles = $em->getRepository('BlogBundle:Article')->findAll();

-        return $this->render('article/index.html.twig', array(
-            'articles' => $articles,
-        ));
+        return ['articles' => $articles];
     }

```

On notera que l'on peut ne pas indiquer d'argument avec `@Template`. Symfony fera
automatiquement le lien entre une action nommée `trucAction` et un fichier de
template nommé `truc.html.twig`.

Si l'on décide de ne pas utiliser l'annotation `@Template`, et que l'on décide
de mettre les vues dans le bundle, il faut utiliser une [notation particulière
pour indiquer le chemin d'accès des vues](https://symfony.com/doc/3.4/templating.html#referencing-templates-in-a-bundle).

Par exemple pour le bundle `BlogBundle` et les fichiers twig mis dans *src/BlogBundle/Resources/views/* :

```diff
diff --git a/src/BlogBundle/Controller/CommentController.php b/src/BlogBundle/Controller/CommentController.php
index a53dac7..dc6d010 100644
--- a/src/BlogBundle/Controller/CommentController.php
+++ b/src/BlogBundle/Controller/CommentController.php
@@ -22,7 +22,7 @@ class CommentController extends Controller

         $comments = $em->getRepository('BlogBundle:Comment')->findAll();

-        return $this->render('comment/index.html.twig', array(
+        return $this->render('@Blog/comment/index.html.twig', array(
             'comments' => $comments,
         ));
     }
@@ -45,7 +45,7 @@ class CommentController extends Controller
             return $this->redirectToRoute('comment_show', array('id' => $comment->getId()));
         }

-        return $this->render('comment/new.html.twig', array(
+        return $this->render('@Blog/comment/new.html.twig', array(
             'comment' => $comment,
             'form' => $form->createView(),
         ));
@@ -59,7 +59,7 @@ class CommentController extends Controller
     {
         $deleteForm = $this->createDeleteForm($comment);

-        return $this->render('comment/show.html.twig', array(
+        return $this->render('@Blog/comment/show.html.twig', array(
             'comment' => $comment,
             'delete_form' => $deleteForm->createView(),
         ));
@@ -81,7 +81,7 @@ class CommentController extends Controller
             return $this->redirectToRoute('comment_edit', array('id' => $comment->getId()));
         }

-        return $this->render('comment/edit.html.twig', array(
+        return $this->render('@Blog/comment/edit.html.twig', array(
             'comment' => $comment,
             'edit_form' => $editForm->createView(),
             'delete_form' => $deleteForm->createView(),
```

##### Présentation des templates

## Gestion d'utilisateurs avec FOSUser

Symfony embarque [des composants liés à la sécurité](http://symfony.com/doc/3.4/security.html)
permettant de facilement mettre en place une gestion d'utilisateur : authenfification et
restriction d'accès à toutes ou parties du site. Il existe un bundle installable
via composer simplifiant reposant entièrement sur le système `security` :
[FOSUserBundle](http://symfony.com/doc/master/bundles/FOSUserBundle/index.html).
Ce bundle permet de mettre en place l'enregistrement d'utilisateur en base de donnée,
les fonctionnalités d'enregistrement/inscription d'utilisateurs, la création de
page de profile et le reset de mot de passe.

### Installation de FOSUser

L'installation se fait via `composer` dans le répertoire *blog*:

```bash
composer require friendsofsymfony/user-bundle "~2.0"
```

Ensuite on l'active dans *app/AppKernel.php* :

```diff
iff --git a/app/AppKernel.php b/app/AppKernel.php
index b6b11fe..20bb563 100755
--- a/app/AppKernel.php
+++ b/app/AppKernel.php
@@ -18,6 +18,7 @@ class AppKernel extends Kernel
             new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
             new AppBundle\AppBundle(),
             new BlogBundle\BlogBundle(),
+           new FOS\UserBundle\FOSUserBundle(),
         ];

         if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
```

Etant donné que l'idée est d'enregistrer les utilisateurs en base de données,
il faut créer une classe `User`, cette classe va étendre la classe `User`
fournie par `FOSUser` dans le fichier *vendor/friendsofsymfony/user-bundle/Model/User.php*.
La classe de base `User` contient déjà la pluspart des champs nécessaires:

```php
abstract class User implements UserInterface, GroupableInterface                                                                             [491/693]
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $usernameCanonical;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $emailCanonical;

    /**
     * @var bool
     */
    protected $enabled;

    /**
     * The salt to use for hashing.
     *
     * @var string
     */
    protected $salt;

    /**
     * Encrypted password. Must be persisted.
     *
     * @var string
     */
    protected $password;
```

La création de la classe suivante dans *src/BlogBundle/Entity/* est suffisante:

```php
<?php
// src/AppBundle/Entity/User.php

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_users")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    public function __construct()
    {
        parent::__construct();
        // your own logic
    }
}
```

Maintenant, il faut adapter le fichier `app/config/security.yml`. Dans ce fichier,
on définit la façon dont on récupère les informations utilisateurs,
les rôles et la hiérarchie existant entre ces rôles ainsi que les règles d'accès
en fonction des roles.

```yaml
security:
    encoders:
        AppBundle\Entity\User:
          algorithm: bcrypt

    # https://symfony.com/doc/current/security.html#b-configuring-how-users-are-loaded
    providers:
        database:
            entity:
                class: AppBundle:User
                property: username

    role_hierarchy:
        ROLE_ADMIN: [ROLE_AUTHOR, ROLE_WRITER]

    firewalls:
    # disables authentication for assets and the profiler, adapt it according to your needs
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        main:
            anonymous: true
            provider: database

            form_login:
                login_path: login
                check_path: login

            logout:
                path: /logout
                target: /

    access_control:
	- { path: ^/login$, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/register, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/resetting, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/admin/, roles: [ROLE_AUTHOR, ROLE_WRITER]}
```
* Les mots de passes sont chiffrés avec bcrypt.
* L'identification se fera sur la propriété username.
* il y a deux role ROLE_AUTHOR et ROLE_WRITER.
* seul ROLE_AUTHOR et ROLE_WRITER pourront avoir accès à la partie d'administration.
* les utilisateurs non authentifiés pourront accèder aux pages de login, d'enregistrement
de "reset" de compte.

