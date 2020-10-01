<?php

class Cms
{
    private $validator;

    public function __construct(Validator $validator) {
        $this->validator = $validator;
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

    private  function validatePagePlaceholders(array $holdersArray) {
        foreach($holdersArray as $holder) {
            if($this->validator->validate("placeholders", array(Validator::FILTER_ALPHA_NUM_UNDERSCORE), $holder)->isFailed()) {
                throw new Exception("Placeholders malformed");
            }
        }
    }
}