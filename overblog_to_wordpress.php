<?php

/**
 * Transforms a Blogger export file into a wordpress import file so it can be imported in a Wordpress blog.
 */

$originFileName = $argv[1]; // file to transform
$siteBaseUrl = $argv[2]; // site URL
$adminMail = $argv[3]; // superadmin mail
$authorLogin = $argv[4]; // superadmin login
$authorDisplayName = $argv[5]; // superadmin display name
$noCategoryName = $argv[6]; // no category name

$fileName = explode('.xml', $originFileName)[0];
$originXML = simplexml_load_file($originFileName);

$transformedXML = new SimpleXMLElement('<rss version="2.0"
	xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:wp="http://wordpress.org/export/1.2/"
><channel></channel></rss>');

// Ecriture du nouveau XML à partir des données de l'ancien
$transformedXML->channel->title = $originXML->blog->name;
$transformedXML->channel->description = $originXML->blog->description;
// Formater date de création
$blogCreatedDate = new DateTime($originXML->blog->created_at);
$transformedXML->channel->pubDate = $blogCreatedDate->format('D, d M Y H:i:s O');
$transformedXML->channel->language = 'fr-FR';
$transformedXML->channel->{'wp:wxr_version'} = '1.2';
$transformedXML->channel->{'wp:base_site_url'} = 'http://www.soskuld.com';
$transformedXML->channel->{'wp:base_blog_url'} = 'http://www.soskuld.com';
$transformedXML->channel->generator = 'https://wordpress.org/?v=4.6.1';

$author = $transformedXML->channel->addChild('author', null, 'http://wordpress.org/export/1.2/');
$author->{'author_id'} = 1;
// Add your user settings
addCdata('wp:author_login', $authorLogin, $author, 'http://wordpress.org/export/1.2/');
addCdata('wp:author_email', $adminMail, $author, 'http://wordpress.org/export/1.2/');
addCdata('wp:author_display_name', $authorDisplayName, $author, 'http://wordpress.org/export/1.2/');
$author->{'author_first_name'} = '';
$author->{'author_last_name'} = '';

// Rescence toutes les catégories nommées dans l'ensemble des articles.
$categories = [];

$commentId = 1;

// Boucler sur les posts
foreach ($originXML->posts->children() as $post) {
    // Formater date de création
    $createdDate = new DateTime($post->published_at);
    $formatedCPublicationDate = $createdDate->format('D, d M Y H:i:s O');
    $formatedDate = $createdDate->format('Y-m-d H:i:s');
    $createdDate->setTimezone(new DateTimeZone('UTC'));
    $formatedDateGmt = $createdDate->format('Y-m-d H:i:s');
    $item = $transformedXML->channel->addChild('item');
    $item->title = $post->title;
    $item->link = $siteBaseUrl . $post->slug;
    $item->pubDate = $formatedCPublicationDate;
    addCdata('wp:post_date', $formatedDate, $item, 'http://wordpress.org/export/1.2/');
    addCdata('wp:post_date_gmt', $formatedDateGmt, $item, 'http://wordpress.org/export/1.2/');
    addCdata('dc:creator', $post->author, $item, 'http://purl.org/dc/elements/1.1/');
    addCdata('content:encoded', $post->content, $item, 'http://purl.org/rss/1.0/modules/content/');
    addCdata('wp:post_type', 'post', $item, 'http://wordpress.org/export/1.2/');
    addCdata('wp:comment_status', 'open', $item, 'http://wordpress.org/export/1.2/');
    addCdata('wp:ping_status', 'open', $item, 'http://wordpress.org/export/1.2/');
    addCdata('wp:status', 'publish', $item, 'http://wordpress.org/export/1.2/');

    // Catégorie du post
    $category = addCdata('category', $post->tags, $item);

    $categoryNiceName = sanitize($post->tags);

    // Si catégorie nulle, on met dans "Non classé" et on ne l'ajoute pas au tableau des catégories à créer. (existe déjà)
    if ($categoryNiceName != null) {
        $categories[$categoryNiceName] = $post->tags;
    } else {
        $categoryNiceName = $noCategoryName;
    }

    $category->addAttribute('domain', "category");
    $category->addAttribute('nicename', $categoryNiceName);

    foreach ($post->comments->children() as $comment) {
        // Cas particulier : si le commentateur a l'adresse mail de l'administrateur, on le lie à son compte utilisateur WP.
        $userId = 0;
        $authorEmail = $comment->author_email;
        if ($comment->author_email == $adminMail) {
            $authorEmail = $adminMail;
            $userId = 1; // superadmin userId
        }
        // Formater date de création
        $commentDate = new DateTime($comment->published_at);
        $formatedDate = $commentDate->format('Y-m-d H:i:s');
        $commentDate->setTimezone(new DateTimeZone('UTC'));
        $formatedDateGmt = $commentDate->format('Y-m-d H:i:s');

        $itemComment = $item->addChild('wp:comment', null, 'http://wordpress.org/export/1.2/');
        addCdata('wp:comment_author', $comment->author_name, $itemComment, 'http://wordpress.org/export/1.2/');
        addCdata('wp:comment_author_email', $authorEmail, $itemComment, 'http://wordpress.org/export/1.2/');
        addCdata('wp:comment_author_url', $comment->author_url, $itemComment, 'http://wordpress.org/export/1.2/');
        addCdata('wp:comment_content', $comment->content, $itemComment, 'http://wordpress.org/export/1.2/');
        $itemComment->{'comment_id'} = $commentId;
        addCdata('wp:comment_date', $formatedDate, $itemComment, 'http://wordpress.org/export/1.2/');
        addCdata('wp:comment_date_gmt', $formatedDateGmt, $itemComment, 'http://wordpress.org/export/1.2/');
        addCdata('wp:comment_approved', 1, $itemComment, 'http://wordpress.org/export/1.2/');
        addCdata('wp:comment_type', '', $itemComment, 'http://wordpress.org/export/1.2/');
        $itemComment->{'comment_user_id'} = $userId;
        $itemComment->{'comment_parent'} = 0;
        $itemComment->{'comment_author_IP'} = '';
        $parentCommentId = $commentId;
        $commentId++;

        foreach ($comment->replies->children() as $reply) {
            // Cas particulier : si le commentateur a l'adresse mail de Skuld, on le lie à son compte utilisateur WP.
            $userId = 0;
            $authorEmail = $reply->author_email;
            if ($comment->author_email == $adminMail) {
                $authorEmail = $adminMail;
                $userId = 1; // superadmin userId
            }
            // Formater date de création
            $commentDate = new DateTime($reply->published_at);
            $formatedDate = $commentDate->format('Y-m-d H:i:s');
            $commentDate->setTimezone(new DateTimeZone('UTC'));
            $formatedDateGmt = $commentDate->format('Y-m-d H:i:s');

            $itemReply = $item->addChild('wp:comment', null, 'http://wordpress.org/export/1.2/');
            addCdata('wp:comment_author', $reply->author_name, $itemReply, 'http://wordpress.org/export/1.2/');
            addCdata('wp:comment_author_email', $authorEmail, $itemReply, 'http://wordpress.org/export/1.2/');
            addCdata('wp:comment_author_url', $reply->author_url, $itemReply, 'http://wordpress.org/export/1.2/');
            addCdata('wp:comment_content', $reply->content, $itemReply, 'http://wordpress.org/export/1.2/');
            $itemReply->{'comment_id'} = $commentId;
            addCdata('wp:comment_date', $formatedDate, $itemReply, 'http://wordpress.org/export/1.2/');
            addCdata('wp:comment_date_gmt', $formatedDateGmt, $itemReply, 'http://wordpress.org/export/1.2/');
            addCdata('wp:comment_approved', 1, $itemReply, 'http://wordpress.org/export/1.2/');
            addCdata('wp:comment_type', '', $itemReply, 'http://wordpress.org/export/1.2/');
            $itemReply->{'comment_parent'} = $parentCommentId;
            $itemReply->{'comment_user_id'} = $userId;
            $commentId++;
        }
    }

}

// Ecriture des catégories
$id = 2;
foreach($categories as $niceName => $category) {
    $categoryXml = $transformedXML->channel->addChild('category', '', 'http://wordpress.org/export/1.2/');
    $categoryXml->{'term_id'} = $id;
    addCdata('wp:category_nicename', $niceName, $categoryXml, 'http://wordpress.org/export/1.2/');
    addCdata('wp:category_parent', '', $categoryXml, 'http://wordpress.org/export/1.2/');
    addCdata('wp:cat_name', $category, $categoryXml, 'http://wordpress.org/export/1.2/');


    $termXml = $transformedXML->channel->addChild('term', '', 'http://wordpress.org/export/1.2/');
    addCdata('wp:term_id', $id, $termXml, 'http://wordpress.org/export/1.2/');
    addCdata('wp:term_taxonomy', 'category', $termXml, 'http://wordpress.org/export/1.2/');
    addCdata('wp:term_slug', $niceName, $termXml, 'http://wordpress.org/export/1.2/');
    addCdata('wp:term_parent', '', $termXml, 'http://wordpress.org/export/1.2/');
    addCdata('wp:term_name', $category, $termXml, 'http://wordpress.org/export/1.2/');
    $id++;
}

// Génération du fichier
$transformedXML->asXML($fileName . '_transformed' . '.xml');

/**
  * Adds a CDATA property to an XML document.
  *
  * @param string $name      Name of property that should contain CDATA.
  * @param string $value     Value that should be inserted into a CDATA child.
  * @param object $parent    Element that the CDATA child should be attached too.
  * @param string $namespace Namespace of the element if needed.
  * @return string
  */
function addCdata($name, $value, &$parent, $namespace = null)
{
    if ($namespace) {
        $child = $parent->addChild($name, '', $namespace);
    } else {
        $child = $parent->addChild($name);
    }

    if ($child !== null) {
        $childNode = dom_import_simplexml($child);
        $childOwner = $childNode->ownerDocument;
        $childNode->appendChild($childOwner->createCDATASection($value));
    }

    return $child;
};

/**
 * Sanitize strings (transforms strings in id-strings)
 * @param  string $str the string to sanitize
 * @return string
 */
function sanitize($str)
{
    $str = htmlentities($str, ENT_NOQUOTES, 'utf-8');

    $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
    $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères

    $str = str_replace(' ', '-', $str); // Replaces all spaces with hyphens.
    $str = str_replace('_', '-', $str);
    $str = str_replace(',', '-', $str);
    $str = str_replace('.', '-', $str);
    $str = str_replace(';', '-', $str);

    $str = preg_replace('/[^A-Za-z0-9\-]/', '', $str); // Removes special chars.
    $str = strtolower($str);

    return $str;
}
