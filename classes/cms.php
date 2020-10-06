<?php

class Cms
{
    const ACTION_INSERT = "insert";
    const ACTION_UPDATE = "update";

    const PAGE_DATA_FILTER_PAGE = "page_name";

    private $occurredAction;
    private $validator;

    public function __construct(Validator $validator) {
        $this->validator = $validator;
    }

    public function getOccurredAction() {
        return $this->occurredAction;
    }

    public function getPageDataForLocalWithFilter(string $local, string $defaultLocal, string $filterKey="", ?string $filterValue="") {
        $pageData = $this->loadPageData($local);
        if(!$pageData) {
            $pageData = $this->loadPageData($defaultLocal);
        }
        if(!$pageData) {
            return array();
        }
        if($filterKey === "") {
            return $pageData;
        }

        return $this->applyFilter($pageData, $filterKey, $filterValue);
    }

    private function loadPageData(string $local) {
        return Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT placeholder, content, page_name FROM page_content WHERE local = ?",
            array("string"), array($local));
    }

    private function applyFilter(array $pageData, string $filterKey, string $filterValue) {
        return array_filter($pageData, function($value) use($filterValue, $filterKey) {
           return $value[$filterKey] === $filterValue;
        });
    }

    public function addPage(string $name, string $placeholders) {
        $holdersArray = preg_split("/\n|\r\n|,/", $placeholders);
        $this->validatePagePlaceholders($holdersArray);
        $exiting = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT id FROM pages WHERE name = ?", array("string"), array($name));
        if(!empty($exiting)) {
            throw new Exception("Page with this name already exists");
        }

        $holdersStr = implode(",", $holdersArray);
        Factory::getObject(Factory::TYPE_DATABASE, true)->execute("INSERT INTO pages (name, placeholders) VALUES (?,?)",
            array("string", "string"), array($name, $holdersStr));
    }

    public function getPageNameAndPlaceholders(string $pageId) {
        $nameHolders = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT name, placeholders FROM pages WHERE id = ?",
            array("integer"), array($pageId));
        if(!$nameHolders) {
            return array("name" => "", "placeholders" => array());
        }
        $name = $nameHolders[0]["name"];
        $holdersArray = explode(",", $nameHolders[0]["placeholders"]);

        return array("name" => $name, "placeholders" => $holdersArray);
    }

    public function editPage(string $pageId, string $newName, string $newHolders, array $changeHoldersArray) {
        if(!$newHolders) {
            $newHoldersArray = array();
        } else {
            $newHoldersArray = preg_split("/\n|\r\n|,/", $newHolders);
            $newHoldersArray = array_unique($newHoldersArray);
            $this->validatePagePlaceholders($newHoldersArray);
        }

        $oldPage = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT name, placeholders FROM pages WHERE id = ?",
            array("integer"), array($pageId));
        if(!$oldPage) {
            throw new Exception("No page found");
        }

        $oldHoldersArray = explode("," ,$oldPage[0]["placeholders"]);
        $oldName = $oldPage[0]["name"];
        $editHoldersArray = $oldHoldersArray;

        if(!empty($changeHoldersArray)) {
            $editHoldersArray = array_values(array_diff($oldHoldersArray, $changeHoldersArray));
        }
        if(!empty($newHoldersArray)) {
            $editHoldersArray = array_merge(array_values(array_diff($newHoldersArray, $editHoldersArray)), $editHoldersArray);
        }

        if($editHoldersArray === $oldHoldersArray && $oldName === $newName) {
            throw new Exception("No change");
        }

        $editHoldersStr = implode(",", $editHoldersArray);
        Factory::getObject(Factory::TYPE_DATABASE, true)->execute("UPDATE pages SET name = ?, placeholders = ? WHERE id = ?",
            array("string", "string", "integer"), array($newName, $editHoldersStr, $pageId));
    }

    public function removePage(int $pageId) {
        $pageToRemove = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT id FROM pages WHERE id = ?", array("integer"), array($pageId));
        if(!$pageToRemove) {
            throw new Exception("Page not found");
        }

        Factory::getObject(Factory::TYPE_DATABASE, true)->execute("DELETE FROM pages WHERE id = ?", array("integer"), array($pageId));
    }

    public function getPagePlaceholdersByName(string $pageName) {
        $holders = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT placeholders FROM pages WHERE name = ?",
            array("string"), array($pageName));
        if(!$holders) {
            return array();
        }

        return explode(",", $holders[0]["placeholders"]);
    }

    public function getContentForPlaceholder(string $pageName, string $local, string $placeholder, bool $contentOnly=true) {
        $content = Factory::getObject(Factory::TYPE_DATABASE, true)->select(
            "SELECT content, id FROM page_content WHERE page_name = ? AND local = ? AND placeholder = ?",
            array("string", "string", "string"), array($pageName, $local, $placeholder)
        );
        if(!$content) {
            return "";
        }

        if($contentOnly === true) {
            return $content[0]["content"];
        }

        return $content;
    }

    public function editPagePlaceholderContent(string $pageName, string $local, string $placeholder, string $content) {
        $existing = $this->getContentForPlaceholder($pageName, $local, $placeholder, false);
        if(strip_tags($content) === "") {
            $content = "";
        }
        if($existing) {
            if($content === $existing[0]["content"]) {
                throw new Exception("No change");
            }
            $this->occurredAction = self::ACTION_UPDATE;
            Factory::getObject(Factory::TYPE_DATABASE, true)->execute("UPDATE page_content SET content = ? WHERE id = ?",
                array("string", "integer"), array($content, $existing[0]['id']));
        } else {
            $this->occurredAction = self::ACTION_INSERT;
            Factory::getObject(Factory::TYPE_DATABASE, true)->execute(
                "INSERT INTO page_content (page_name, local, placeholder, content) VALUES (?,?,?,?)",
                array("string", "string", "string", "string"), array($pageName, $local, $placeholder, $content));
        }
    }

    private  function validatePagePlaceholders(array $holdersArray) {
        foreach($holdersArray as $holder) {
            if($this->validator->validate("placeholders", array(Validator::FILTER_ALPHA_NUM_UNDERSCORE), $holder)->isFailed()) {
                throw new Exception("Placeholders malformed");
            }
        }
    }
}