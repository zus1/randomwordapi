<?php

interface MailInterface
{
    public function getBodyContent() : string;

    public function addContentData(string $content) : string;
}