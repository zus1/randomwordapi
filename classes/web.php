<?php

class Web
{
    private $localization;
    private $cms;

    const PAGE_DOCUMENTATION = "documentation";
    const NAVIGATION = "navigation";

    public function __construct(Localization $localization, Cms $cms) {
        $this->localization = $localization;
        $this->cms = $cms;
    }

    private function getPageToDataMethodMapping() {
        return array(
            self::PAGE_DOCUMENTATION => "getDocumentationData",
            self::NAVIGATION => "getNavigationData",
        );
    }

    public function getPageData(string $page) {
        if(!array_key_exists($page, $this->getPageToDataMethodMapping())) {
            throw new Exception("Page not found", HttpCodes::HTTP_NOT_FOUND);
        }

        return call_user_func([$this, $this->getPageToDataMethodMapping()[$page]]);
    }

    private function getNavigationData() {
        $pageCmsData = $this->getCmsData(Cms::PAGE_DATA_FILTER_PAGE, self::NAVIGATION);
        $returnData  = array();
        array_walk($pageCmsData, function($value) use(&$returnData) {
            $returnData[$value["placeholder"]] = $value["content"];
        });

        return $returnData;
    }

    private function getDocumentationData() {
        $pageCmsData = $this->getCmsData(Cms::PAGE_DATA_FILTER_PAGE, self::PAGE_DOCUMENTATION);
        $returnData = array("statuses" => array(), "parameters" => array());
        array_walk($pageCmsData, function ($value) use(&$returnData, $pageCmsData) {
           $returnData[$value["placeholder"]] = $value["content"];
           $this->documentationPairValues($pageCmsData,$returnData, "status", "statuses", $value);
           $this->documentationPairValues($pageCmsData,$returnData, "params", "parameters", $value);
        });

        return $returnData;
    }

    private function getCmsData(string $filterKey, string $filterValue) {
        $activeLocal = $this->localization->getActive();
        $defaultLocal = $this->localization->getDefault();
        return $this->cms->getPageDataForLocalWithFilter($activeLocal, $defaultLocal, $filterKey, $filterValue);
    }

    private function documentationPairValues(array $pageCmsData, &$returnData, $prefix, $returnArrayKey, $cmsDataValue) {
        if(substr($cmsDataValue["placeholder"], 0, strlen($prefix)) === $prefix && strpos($cmsDataValue["placeholder"], "_label")) {
            $nextStatuses = array("label" => $cmsDataValue["placeholder"]);
            $forValueSearch = substr($cmsDataValue["placeholder"], 0, strlen($cmsDataValue["placeholder"]) - strlen("_label"));
            $statusValue = array_values(array_filter($pageCmsData, function ($value) use($forValueSearch) {
                return $value["placeholder"] === $forValueSearch;
            }));

            if(!empty($statusValue)) {
                $nextStatuses["content"] = $statusValue[0]["placeholder"];
            } else {
                $nextStatuses["content"] = "no_placeholder";
            }

            $returnData[$returnArrayKey][] = $nextStatuses;
        }
    }
}