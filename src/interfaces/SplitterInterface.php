<?php

namespace DppManualParser\interfaces;

interface SplitterInterface
{
    public function handle(string $content) : array;
}