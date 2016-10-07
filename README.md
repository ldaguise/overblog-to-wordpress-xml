# Overblog xml to Wordpress xml

Export your Blogger blog thanks to the premium feature. Then, download this script and run it like that :
```
php transform_overblog.php export.xml http://my-blog.com lucile@evhell.fr ldaguise Ldaguise non-classe
```

## Arguments
* `export.xml` : the xml file to transform
* `http://my-blog.com` : the blog URL
* `lucile@evhell.fr` : the Wordpress superadmin mail
* `ldaguise` : the Wordpress superadmin login
* `Ldaguise` : the Wordpress superadmin display name
* `non-classe` : the category name where Wordpress put posts without categories


## Info

This script doesn't dowload files to the media library. You can use [Import External Image](https://fr.wordpress.org/plugins/import-external-images/) plugin to do it after importing the transformed XML file. I also used [Auto Post Thumbnail](https://fr.wordpress.org/plugins/auto-post-thumbnail) to assign a thumbnail to each post.
