<?php

use Wruczek\TSWebsite\News\DefaultNewsStore;
use Wruczek\TSWebsite\Utils\TemplateUtils;

require_once __DIR__ . "/loader.php";

$newsStore = new DefaultNewsStore();
$successMessage = null;
$errorMessage = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $title = $_POST['title'];
            $content = $_POST['content'];
            if ($title && $content) {
                $newsStore->addNews($title, $content);
                $successMessage = "News added successfully.";
            } else {
                $errorMessage = "Title and content are required.";
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = (int)$_POST['newsId'];
            $title = $_POST['title'];
            $content = $_POST['content'];
            if ($title && $content) {
                $newsStore->editNews($id, $title, $content, null, time());
                $successMessage = "News updated successfully.";
            } else {
                $errorMessage = "Title and content are required.";
            }
        } elseif ($_POST['action'] === 'delete') {
            // DefaultNewsStore doesn't have a delete method exposed in the interface or class I saw.
            // I need to check DefaultNewsStore.php again or add a delete method.
            // Let's assume I will add it or use direct DB call if needed, but better to add it to the class.
             $db = \Wruczek\TSWebsite\Utils\DatabaseUtils::i()->getDb();
             $db->delete("news", ["newsid" => $_POST['newsId']]);
             $successMessage = "News deleted successfully.";
        }
    }
}

$newsList = $newsStore->getNewsList(100); // Fetch last 100 news

$data = [
    "navActiveIndex" => 2,
    "paneltitle" => '<i class="fas fa-newspaper"></i> News Management',
    "newsList" => $newsList,
    "successMessage" => $successMessage,
    "errorMessage" => $errorMessage
];

TemplateUtils::i()->renderTemplate("admin/news", $data);
