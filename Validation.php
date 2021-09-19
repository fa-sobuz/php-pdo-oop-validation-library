<?php


namespace App\Helpers;
error_reporting(0);

use App\Controllers\Controller;
use PDO;

class Validation extends Controller
{
    public array $errors = [];
    public bool $pass = false;

    /**
     * @param $input_field
     * @param string $label
     * @param $rules
     * @return string
     */
    public function validate($input_field, string $label, $rules): string
    {
        /**
         * spilt by pipe line
         */

        $list_rules = explode('|', $rules);

        /**
         * sanitize input data
         */
        $is_field = $this->sanitize($input_field);

        /**
         * check if the attribute is empty
         */
        if (in_array("required", $list_rules)) {
            if (empty($is_field)) {
                return $this->errors[$input_field] = $label . " is required !";
            }
        }

        /**
         * check if the attribute value is not exist in database and specific table
         */
        if (in_array("unique", $list_rules)) {

            $uniqueIndex = array_search("unique", $list_rules);
            $conditionIndex = $uniqueIndex + 1;
            $conditionIndex_selected = $list_rules[$conditionIndex];
            $divideConditionIndex = explode(":", $conditionIndex_selected);

            //table
            $table_name = $divideConditionIndex[0];
            $table_field_name = $divideConditionIndex[1];
            $is_exist_data = $this->link->prepare("SELECT * FROM " . $table_name . " WHERE BINARY " . $table_field_name . "= ?");
            if ($is_exist_data->execute([$is_field])) {
                if ($is_exist_data->rowCount() > 0) {
                    return $this->errors[$input_field] = $label . " is already taken !";
                }
            }


        }

        /**
         * min and max length check
         */
        if (in_array('len', $list_rules)) {
            $lengthIndex = array_search("len", $list_rules);

            $conditionIndex = $lengthIndex + 1;
            $conditionIndex_selected = $list_rules[$conditionIndex];
            $divideConditionIndex = explode(":", $conditionIndex_selected);
            if ($divideConditionIndex[0] == "min") {
                $minLen = $divideConditionIndex[1];
                if (strlen($is_field) < $minLen) {
                    return $this->errors[$input_field] = $label . " should not less than " . $minLen . " char !";
                }
            }
            if ($divideConditionIndex[0] == "max") {
                $maxLen = $divideConditionIndex[1];
                if (strlen($is_field) > $maxLen) {
                    return $this->errors[$input_field] = $label . " should not more than " . $maxLen . " char !";
                }
            }
        }
        /**
         * check min and max length between values
         */
        if (in_array('between', $list_rules)) {
            $betweenIndex = array_search("between", $list_rules);
            $conditionIndex = $betweenIndex + 1;
            $conditionIndex_selected = $list_rules[$conditionIndex];

            $is_present_comma = str_contains($conditionIndex_selected, ',');
            if ($is_present_comma) {
                $findValueIndex = explode(",", $conditionIndex_selected);
                $check_min_len = explode(":", $findValueIndex[0]);
                $_minLen = $check_min_len[1];
                $check_max_len = explode(":", $findValueIndex[1]);
                $_maxLen = $check_max_len[1];
                if ((strlen($is_field) < $_minLen) || (strlen($is_field) > $_maxLen)) {
                    return $this->errors[$input_field] = $label . " must be between " . $check_min_len[1] . " and " . $check_max_len[1] . " char !";
                }
            }


        }

        /**
         * check the input data is only alphabetic
         */
        if (in_array('alpha', $list_rules)) {
            if (!ctype_alpha($is_field)) {
                return $this->errors[$input_field] = $label . " must only letters !";
            }
        }

        /**
         * check the input data is only numeric
         */
        if (in_array('numeric', $list_rules)) {
            if (!is_numeric($is_field)) {
                return $this->errors[$input_field] = $label . " must only numbers !";
            }
        }

        /**
         * takes only string and whitespace
         */
        if (in_array('string', $list_rules)) {
            if (!preg_match("/^[a-zA-Z ]*$/", $is_field)) {
                return $this->errors[$input_field] = $label . " must only string & whitespace !";
            }
        }

        /**
         * email validate check
         */
        if (in_array('email', $list_rules)) {
            $email = strtolower($is_field);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->errors[$input_field] = $label . " must be a valid email address !";
            }
        }

        /**
         * url check
         */
        if (in_array('url', $list_rules)) {
            $url = filter_var($is_field, FILTER_VALIDATE_URL);
            if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $url)) {
                return $this->errors[$input_field] = $label . " must be a valid url !";
            }
        }
        /**
         * validate username
         */
        if (in_array('username', $list_rules)) {
            if (!ctype_alnum($is_field)) {
                return $this->errors[$input_field] = $label . " is not valid !";
            }
        }

        /**
         * validate password
         * Min 8 & max 30 characters, at least one uppercase letter, one lowercase letter, one number and one special character:
         */
        if (in_array('password', $list_rules)) {
            $regx = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[#$@!%&*?])[A-Za-z\d#$@!%&*?]{8,30}$/";
            if (!preg_match($regx, $is_field)) {
                return $this->errors[$input_field] = $label . " must 1 upper,lowercase,number & special char!";
            }
        }

        /**
         * return true if everything takes no error
         */
        return $this->pass = true;
    }

    /**
     * sanitize input before taking any action
     * @param $field
     * @return string
     */
    public function sanitize($field): string
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST" || $_SERVER['REQUEST_METHOD'] == "post") {
            return strip_tags(stripslashes(trim($_POST[$field])));
        } elseif ($_SERVER['REQUEST_METHOD'] == "GET" || $_SERVER['REQUEST_METHOD'] == "get") {
            return strip_tags(stripslashes(trim($_GET[$field])));
        }
    }

}