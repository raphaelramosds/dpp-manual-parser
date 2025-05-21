<?php

interface ISplitter
{
    public function handle(string $content) : array;
}