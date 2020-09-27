<?php
include_once("include.php");
//$request = Factory::getObject(Factory::TYPE_REQUEST);
//print_r($request->getParsedRequestUrl());
/*$output = array();
$output = $request->getParsedRequestQuery($output);
print_r($output);
print_r($request->getRequestPath());
die();*/
Factory::getObject(Factory::TYPE_ROUTER)->routeAll();

