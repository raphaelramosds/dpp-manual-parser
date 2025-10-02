<?php

namespace DppManualParser;

use Exception;
use InvalidArgumentException;

class TxtContext
{
    private SplitterInterface $splitter;
    private $content;

    public function __construct(string $file)
    {
        try {
            if (!file_exists($file)) {
                throw new InvalidArgumentException("File not found: $file");
            }
            $this->content = file_get_contents($file);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function parse() : array
    {
        return $this->splitter->handle($this->content);
    }

    public function setSplitter (SplitterInterface $splitter) : void
    {
        $this->splitter = $splitter;
    }
}
