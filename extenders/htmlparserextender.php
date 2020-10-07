<?php

class HtmlParserExtender
{
    public function includeToAllViews() {
        return array(
            "{locals}" => $this->getLocalsForInclude(),
            "{logo}" => $this->getLogo()
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
        $activeLocal = Factory::getObject(Factory::TYPE_LOCALIZATION)->getActive();
        usort($allLocals, function ($value1, $value2) use($activeLocal) {
            if($value1["tag"] === $activeLocal) {
                return -1;
            }
            return 1;
        });
        $html = "";
        foreach($allLocals as $local) {
            $html .= "<li>";
            $text = $local["tag"];
            $value = $local["tag"];
            $checked = ($local["tag"] === $activeLocal)? "checked" : "";
            $html .= sprintf("<input type='radio' name='localization' value='%s' %s>%s", $value, $checked, $text);
            $html .= "</li>";
        }

        return $html;
    }

    private function getLogo() {
        $navigationCmsData = Factory::getObject(Factory::TYPE_WEB)->getPageData(Web::NAVIGATION);
        if(!array_key_exists("logo", $navigationCmsData)) {
            return "";
        }

        return $navigationCmsData["logo"];
    }
}