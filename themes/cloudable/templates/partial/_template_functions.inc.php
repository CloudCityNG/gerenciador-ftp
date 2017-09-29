<?php

class templateFunctions {

    static function outputSuccess() {
        $html = '';
        $html .= "<script>\n";
        $html .= "$(document).ready(function() {\n";
        $success = notification::getSuccess();
        if (COUNT($success)) {
            $htmlArr = array();
            foreach ($success AS $success) {
                $htmlArr[] = $success;
            }

            $msg = implode("<br/>", $htmlArr);
        }
        $html .= "showSuccessNotification('" . str_replace('\'', '', t('success', 'Success')) . "', '" . str_replace('\'', '', $msg) . "');\n";
        $html .= "});\n";
        $html .= "</script>\n";

        return $html;
    }

    static function outputErrors() {
        $html = '';
        $html .= "<script>\n";
        $html .= "$(document).ready(function() {\n";
        $errors = notification::getErrors();
        if (COUNT($errors)) {
            $htmlArr = array();
            foreach ($errors AS $error) {
                $htmlArr[] = $error;
            }

            $msg = implode("<br/>", $htmlArr);
        }
        $html .= "showErrorNotification('" . str_replace('\'', '', t('error', 'Error')) . "', '" . str_replace('\'', '', $msg) . "');\n";
        $html .= "});\n";
        $html .= "</script>\n";

        return $html;
    }

}
