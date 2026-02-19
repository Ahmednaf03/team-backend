<?php
class Validator {


    public static function email($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json("Invalid email format", 422);
        }
    }


    public static function allRequired(array $data, array $fields) {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                Response::json("All fields are required", 422);
            }
        }
    }

    public static function numeric($value, $field) {
        if (!is_numeric($value)) {
            Response::json("$field must be numeric", 422);
        }
    }

    public static function phone($value) {
        if (!preg_match('/^[0-9]{10}$/', $value)) {
            Response::json("Invalid phone number", 422);
        }
    }

    public static function validId($record) {
        if (!$record) {
            Response::json("Invalid patient id", 404);
        }
    }
}
