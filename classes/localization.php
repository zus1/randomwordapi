<?php

class Localization
{

    const STATE_ACTIVE = 1;
    const STATE_INACTIVE = 0;

    public function addLocal(string $tag) {
        $active = 0;
        $exitingAll = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT tag FROM local", array(), array());
        if(!$exitingAll) {
            $active = 1;
        }

        $exiting = array_filter($exitingAll, function($value) use($tag) {
           return $value['tag'] === $tag;
        });
        if(!empty($exiting)) {
            throw new Exception("Local already exists");
        }
        Factory::getObject(Factory::TYPE_DATABASE, true)->execute("INSERT INTO local (tag, active) VALUES (?,?)", array("string", "integer"),
            array($tag, $active));
    }

    public function removeLocal(string $tag) {
        $existingAll = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT tag, active FROM local", array(), array());
        if(count($existingAll) === 1) {
            throw new Exception("Cant remove last local");
        }

        $localToCheck = array_values(array_filter($existingAll, function($value) use($tag) {
            return $value["tag"] === $tag;
        }));

        if(empty($localToCheck)) {
            throw new Exception("Local not found");
        }
        if((int)$localToCheck[0]['active'] === 1) {
            throw new Exception("Can't remove active local");
        }

        Factory::getObject(Factory::TYPE_DATABASE)->execute("DELETE FROM local WHERE tag = ?", array("string"), array($tag));
    }

    public function getLocalActiveState(string $tag) {
        $local = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT active FROM local WHERE tag = ?", array("string"), array($tag));
        if(!$local) {
            throw new Exception("No local to change");
        }

        return $local[0]["active"];
    }

    public function changeActive(string $tag, int $active) {
        $current = Factory::getObject(Factory::TYPE_DATABASE)->select("SELECT active FROM local  WHERE tag = ?", array("string"), array($tag));
        if(!$current) {
            throw new Exception("Local not found");
        }
        if($active === (int)$current[0]["active"]) {
            throw new Exception("No change");
        }
        if($active === self::STATE_INACTIVE) {
            throw new Exception("Please only activate local, deactivation will be done automatically");
        }

        Factory::getObject(Factory::TYPE_DATABASE, true)->execute("UPDATE local SET active = ?", array("integer"), array(self::STATE_INACTIVE));
        Factory::getObject(Factory::TYPE_DATABASE, true)->execute("UPDATE local SET active = ? WHERE tag = ?",
            array("integer", "string"), array($active, $tag));
    }
}