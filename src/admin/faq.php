<?php

use Wruczek\TSWebsite\Utils\DatabaseUtils;
use Wruczek\TSWebsite\Utils\TemplateUtils;

require_once __DIR__ . "/loader.php";

$db = DatabaseUtils::i()->getDb();
$successMessage = null;
$errorMessage = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $question = $_POST['question'];
            $answer = $_POST['answer'];
            if ($question && $answer) {
                $db->insert("faq", [
                    "question" => $question,
                    "answer" => $answer,
                    "langid" => 1 // Default to langid 1 for now
                ]);
                $successMessage = "FAQ added successfully.";
            } else {
                $errorMessage = "Question and answer are required.";
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = (int)$_POST['faqid'];
            $question = $_POST['question'];
            $answer = $_POST['answer'];
            if ($question && $answer) {
                $db->update("faq", [
                    "question" => $question,
                    "answer" => $answer
                ], ["faqid" => $id]);
                $successMessage = "FAQ updated successfully.";
            } else {
                $errorMessage = "Question and answer are required.";
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['faqid'];
            $db->delete("faq", ["faqid" => $id]);
            $successMessage = "FAQ deleted successfully.";
        }
    }
}

$faqList = $db->select("faq", "*");

$data = [
    "navActiveIndex" => 3,
    "paneltitle" => '<i class="fas fa-question-circle"></i> FAQ Management',
    "faqList" => $faqList,
    "successMessage" => $successMessage,
    "errorMessage" => $errorMessage
];

TemplateUtils::i()->renderTemplate("admin/faq", $data);
