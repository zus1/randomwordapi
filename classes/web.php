<?php

class Web
{
    private $localization;
    private $cms;

    const PAGE_DOCUMENTATION = "documentation";

    public function __construct(Localization $localization, Cms $cms) {
        $this->localization = $localization;
        $this->cms = $cms;
    }

    private function getPageToDataMethodMapping() {
        return array(
            self::PAGE_DOCUMENTATION => "getDocumentationData"
        );
    }

    public function getPageData(string $page) {
        if(!array_key_exists($page, $this->getPageToDataMethodMapping())) {
            throw new Exception("Page not found", HttpCodes::HTTP_NOT_FOUND);
        }

        return call_user_func([$this, $this->getPageToDataMethodMapping()[$page]]);
    }

    private function getDocumentationData() {
        $activeLocal = $this->localization->getActive();
        $pageCmsData = $this->cms->getPageDataForLocalWithFilter($activeLocal, Cms::PAGE_DATA_FILTER_PAGE, self::PAGE_DOCUMENTATION);

        $returnData = array();
        array_walk($pageCmsData, function ($value) use(&$returnData) {
           $returnData[$value["placeholder"]] = $value["content"];
        });

        return $returnData;
    }
}