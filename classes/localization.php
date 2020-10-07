<?php

class Localization
{

    const STATE_ACTIVE = 1;
    const STATE_INACTIVE = 0;
    private $defaultLocal;

    private $session;
    private $user;

    public function __construct(Session $session, User $user){
        $this->defaultLocal = Config::get(Config::LOCAL_DEFAULT, "en");
        $this->session = $session;
        $this->user = $user;
    }

    public function getDefault() {
        return $this->defaultLocal;
    }

    public function getActive() {
        $this->session->startSession();

        $active = "";
        $this->getActiveLocalForAuthenticatedUser($active);
        $this->getLocalForNonAuthenticatedUser($active);
        $this->getAdminActiveLocal($active);

        if($active === "") {
            $active = $this->defaultLocal;
        }

        return $active;
    }

    public function setActive(string $newLocal) {
        $this->session->startSession();

        if($this->user->isAuthenticatedUser()) {
            $this->user->setAuthenticatedUser(array("local" => $newLocal));
        } else {
            $_SESSION["local"] = $newLocal;
        }
    }

    private function getActiveLocalForAuthenticatedUser(&$active) {
        if($this->user->isAuthenticatedUser()) {
            $authUser = $this->user->getAuthenticatedUser(["local"]);
            if(!empty($authUser["local"])) {
                $active = $authUser["local"];
            }
        }
    }

    private function getLocalForNonAuthenticatedUser(&$active) {
        if($active === "") {
            if(isset($_SESSION[Session::LOCAL_KEY])) {
                $active = $_SESSION[Session::LOCAL_KEY];
            }
        }
    }

    private function getAdminActiveLocal(&$active) {
        if($active === "") {
            $activeTag = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT tag FROM local WHERE active = ?",
                array("integer"), array(self::STATE_ACTIVE));
            if(!empty($activeTag)) {
                $active = $activeTag[0]["tag"];
            }
        }
    }

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

    public function adminChangeActive(string $tag, int $active) {
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

    public function getAllLocals() {
        $locals = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT tag, active FROM local", array(), array());
        if(!$locals) {
            return array();
        }

        return $locals;
    }
}