<?php

class HtmlParserExtender
{
    public function includeToAllViews() {
        return array(
            "{locals}" => $this->getLocalsForInclude()
        );
    }

    public function includeToSpecificView(string $view) {
        $includes = array();
        $viewParts = explode(":", $view);
        if($viewParts[0] === "admin") {
            $includes["{only_to_admin}"] = "This is included only on admin views";
        }

        return $includes;
    }

    private function getLocalsForInclude() {
        $allLocals = Factory::getObject(Factory::TYPE_LOCALIZATION)->getAllLocals();
        usort($allLocals, function ($value1, $value2) { return $value1["active"] < $value2["active"];});
        $html = "";
        foreach($allLocals as $local) {
            $html .= "<li>";
            $text = $local["tag"];
            $value = $local["tag"];
            $checked = ($local["active"])? "checked" : "";
            $html .= sprintf("<input type='radio' name='localization' value='%s' %s>%s", $value, $checked, $text);
            $html .= "</li>";
        }

        return $html;
    }
}