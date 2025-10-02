<?php

namespace DppManualParser;

interface SplitterInterface
{
    public function handle(string $content) : array;
}