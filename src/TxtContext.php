<?php

require_once 'Splitter/ISplitter.php';

class TxtContext
{
    private ISplitter $splitter;
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

    public function setSplitter (ISplitter $splitter) : void
    {
        $this->splitter = $splitter;
    }
}
