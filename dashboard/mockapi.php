<?php

$action = $_GET['action'];

$resp = array();
switch($action) {
    case "/_services":
        $resp = array(
            array("name" => "Users", "desc" => "Managing users", "url" => "/users/_actions")
        );
        break;
    case "/users/_actions":
        $resp = array(
            array("name" => "list", "title" => "List", "icon" => "list", "url" => "/users/_actions/list", "default" => true),
            array("name" => "add", "title" => "Add", "icon" => "plus", "url" => "/users/_actions/add")
        );
        break;
    case "/users/_actions/list":
        $resp = array(
            "type" => "list",
            "url" => "/users",
            "method" => "GET",
            "actions" => array(
                array("name" => "delete", "title" => "Delete", "icon" => "trash", "url" => "/users/_actions/delete")
            ),
            "columns" => array("ID", "First name", "Last name", "Description")
        );
        break;
    case "/users/_actions/add":
        $resp = array(
            "type" => "form",
            "url" => "/users",
            "method" => "POST",
            "fields" => array(
                array("name" => "firstname", "label" => "First name", "type" => "text"),
                array("name" => "lastname", "label" => "Last name", "type" => "text"),
                array("name" => "description", "label" => "Description", "type" => "textarea")
            )
        );
        break;
    case "/users/_actions/delete":
        $resp = array(
            "type" => "call",
            "url" => "/users/delete",
            "method" => "DELETE"
        );
        break;
    case "/users":
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $resp = array(
                array(1, 'max', 'bf', "number one"),
                array(2, 'mat', 'BF', "number two")
            );
        } else {
            $resp = array();
        }
        break;
};

header('content-type', 'application/json');
echo json_encode(array('result' => $resp));
