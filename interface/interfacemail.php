<?php

interface InterfaceMail
{
    public function getBodyContent() : string;

    public function addContentData(string $content) : string;
}